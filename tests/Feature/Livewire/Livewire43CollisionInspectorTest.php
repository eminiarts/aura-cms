<?php

use Aura\Base\Livewire\ComponentSlots\ComponentSlotCollision;
use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Livewire\ComponentSlots\Livewire43CollisionInspector;
use Aura\Base\Livewire\ComponentSlots\LivewireCollisionInspectorFactory;
use Aura\Base\Livewire\ComponentSlots\UnsupportedLivewireInternals;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Livewire\MediaManager;
use Aura\Base\Tests\Fixtures\ComponentSlots\CollisionFixture;
use Illuminate\Support\Facades\File;
use Livewire\Factory\Factory;
use Livewire\Finder\Finder;

function freshCollisionInspector(?Finder $finder = null, ?Factory $factory = null): array
{
    $finder ??= new Finder;
    $factory ??= new Factory($finder, app('livewire.compiler'));

    return [new Livewire43CollisionInspector($finder, $factory), $finder, $factory];
}

function setLivewireInternal(object $target, string $property, mixed $value): void
{
    (function () use ($property, $value): void {
        $this->{$property} = $value;
    })->call($target);
}

function protectedComponentSlotIdentifiers(): array
{
    return [
        'aura-slot-5d08acbafc1799908d00c98ba128984f725d8bc43d13679c7689c9e24e2c107c',
        'aura-slot-f16ee9c2b47b1df672e85903a69ffc98066ccd885e96e5f18536c737f00c5a88',
        'aura::global-search',
        'aura.base.livewire.global-search',
        'aura::media-manager',
        'aura.base.livewire.media-manager',
    ];
}

test('4.3 inspector validates the exact supported Finder and Factory shape', function () {
    [$inspector] = freshCollisionInspector();

    $inspector->assertCompatible();

    expect(true)->toBeTrue();
});

test('adapter factory accepts 4.3.5 through 4.3 patches and rejects every other range', function (string $version, bool $supported) {
    $finder = new Finder;
    $factory = new Factory($finder, app('livewire.compiler'));
    $adapterFactory = new LivewireCollisionInspectorFactory($finder, $factory);

    if ($supported) {
        expect($adapterFactory->forVersion($version))->toBeInstanceOf(Livewire43CollisionInspector::class);

        return;
    }

    expect(fn () => $adapterFactory->forVersion($version))
        ->toThrow(UnsupportedLivewireInternals::class, $version);
})->with([
    'lowest' => ['4.3.5', true],
    'later patch' => ['4.3.99', true],
    'v prefix' => ['v4.3.5', true],
    'older patch' => ['4.3.4', false],
    'older minor' => ['4.2.9', false],
    'newer minor' => ['4.4.0', false],
    'newer major' => ['5.0.0', false],
]);

test('inspector fails closed when supported internals have an altered runtime shape', function () {
    $finder = new class extends Finder {};
    $factory = new Factory($finder, app('livewire.compiler'));
    [$inspector] = freshCollisionInspector($finder, $factory);

    expect(fn () => $inspector->assertCompatible())
        ->toThrow(UnsupportedLivewireInternals::class, Finder::class);
});

test('inspector rejects malformed protected collection shapes before collision checks', function (string $target, string $property, mixed $value) {
    [$inspector, $finder, $factory] = freshCollisionInspector();

    setLivewireInternal($target === 'finder' ? $finder : $factory, $property, $value);

    expect(fn () => $inspector->assertCompatible())
        ->toThrow(UnsupportedLivewireInternals::class, $property);
})->with([
    'class locations must be strings' => ['finder', 'classLocations', [new stdClass]],
    'view locations must be a list' => ['finder', 'viewLocations', ['named' => '/tmp']],
    'class namespaces have exact records' => ['finder', 'classNamespaces', ['third' => 'invalid']],
    'view namespace paths are strings' => ['finder', 'viewNamespaces', ['third' => 42]],
    'class component maps have string keys' => ['finder', 'classComponents', [CollisionFixture::class]],
    'view component paths are strings' => ['finder', 'viewComponents', ['claimed' => new stdClass]],
    'missing resolvers are callable' => ['factory', 'missingComponentResolvers', ['not-callable']],
    'resolved cache values are classes' => ['factory', 'resolvedComponentCache', ['claimed' => new stdClass]],
]);

test('inspector detects explicit class and view claims for every protected identifier', function (string $identifier, string $claim) {
    [$inspector, $finder] = freshCollisionInspector();

    if ($claim === 'class') {
        $finder->addComponent(name: $identifier, class: CollisionFixture::class);
    } else {
        $finder->addComponent(name: $identifier, viewPath: '/tmp/claimed.blade.php');
    }

    expect(fn () => $inspector->assertUnclaimed([$identifier], static fn (): null => null))
        ->toThrow(ComponentSlotCollision::class, $identifier);
})->with(function (): array {
    $cases = [];

    foreach (protectedComponentSlotIdentifiers() as $identifier) {
        $cases[$identifier.' class'] = [$identifier, 'class'];
        $cases[$identifier.' view'] = [$identifier, 'view'];
    }

    return $cases;
});

test('inspector detects class and view namespace claims', function (string $kind) {
    [$inspector, $finder] = freshCollisionInspector();

    if ($kind === 'class') {
        $finder->addNamespace('aura', classNamespace: 'ThirdParty\\Livewire');
    } else {
        $finder->addNamespace('aura', viewPath: sys_get_temp_dir());
    }

    expect(fn () => $inspector->assertUnclaimed(['aura::global-search'], static fn (): null => null))
        ->toThrow(ComponentSlotCollision::class, $kind.'-namespace');
})->with(['class', 'view']);

test('inspector detects conventional class discovery', function () {
    [$inspector, $finder] = freshCollisionInspector();
    $finder->addLocation(classNamespace: 'Aura\\Base\\Tests\\Fixtures\\ComponentSlots');

    expect(fn () => $inspector->assertUnclaimed(['collision-fixture'], static fn (): null => null))
        ->toThrow(ComponentSlotCollision::class, 'conventional-class');
});

test('inspector detects single and multi file discovery without compiling or mutating Finder state', function (string $kind) {
    $path = storage_path('framework/testing/core20-'.$kind.'-'.uniqid());
    mkdir($path, 0755, true);
    [$inspector, $finder] = freshCollisionInspector();
    $finder->addLocation(viewPath: $path);

    if ($kind === 'single-file') {
        file_put_contents($path.'/claimed.blade.php', "<?php new class extends \\Livewire\\Component {}; ?>\n<div></div>");
    } else {
        mkdir($path.'/claimed');
        file_put_contents($path.'/claimed/claimed.php', '<?php return null;');
        file_put_contents($path.'/claimed/claimed.blade.php', '<div></div>');
    }

    try {
        expect(fn () => $inspector->assertUnclaimed(['claimed'], static fn (): null => null))
            ->toThrow(ComponentSlotCollision::class, $kind);
    } finally {
        File::deleteDirectory($path);
    }
})->with(['single-file', 'multi-file']);

test('inspector invokes third party missing resolvers but excludes only the exact Aura resolver', function (string $identifier) {
    [$inspector, , $factory] = freshCollisionInspector();
    $auraResolver = static fn (): string => CollisionFixture::class;
    $factory->resolveMissingComponent($auraResolver);

    $assertUnclaimed = function () use ($auraResolver, $identifier, $inspector): void {
        $intrinsicComponent = match ($identifier) {
            'aura.base.livewire.global-search' => GlobalSearch::class,
            'aura.base.livewire.media-manager' => MediaManager::class,
            default => null,
        };

        if ($intrinsicComponent !== null) {
            $inspector->assertReservable($identifier, $intrinsicComponent, $auraResolver);

            return;
        }

        $inspector->assertUnclaimed([$identifier], $auraResolver);
    };

    $assertUnclaimed();

    $factory->resolveMissingComponent(static fn (string $name): ?string => $name === $identifier ? CollisionFixture::class : null);

    expect($assertUnclaimed)
        ->toThrow(ComponentSlotCollision::class, 'missing-resolver');
})->with(protectedComponentSlotIdentifiers());

test('inspector detects existing Factory cache entries before normal resolution', function (string $identifier) {
    [$inspector, , $factory] = freshCollisionInspector();
    setLivewireInternal($factory, 'resolvedComponentCache', [$identifier => CollisionFixture::class]);

    expect(fn () => $inspector->assertUnclaimed([$identifier], static fn (): null => null))
        ->toThrow(ComponentSlotCollision::class, 'factory-cache');
})->with(protectedComponentSlotIdentifiers());

test('inspector permits only an explicitly allowed exact owned claim', function () {
    [$inspector, $finder, $factory] = freshCollisionInspector();
    $identifier = 'aura-record-panel-owned';
    $finder->addComponent(name: $identifier, class: CollisionFixture::class);
    setLivewireInternal($factory, 'resolvedComponentCache', [$identifier => CollisionFixture::class]);

    $inspector->assertReservable(
        $identifier,
        CollisionFixture::class,
        static fn (): null => null,
        allowExactClaim: true,
    );

    expect(fn () => $inspector->assertReservable(
        $identifier,
        GlobalSearch::class,
        static fn (): null => null,
        allowExactClaim: true,
    ))->toThrow(ComponentSlotCollision::class, CollisionFixture::class);
});

test('inspector converts errors from third party missing resolvers into collision diagnostics', function () {
    [$inspector, , $factory] = freshCollisionInspector();
    $factory->resolveMissingComponent(static fn () => throw new RuntimeException('resolver failed'));

    expect(fn () => $inspector->assertUnclaimed(
        ['aura::global-search'],
        static fn (): null => null,
    ))->toThrow(ComponentSlotCollision::class, 'missing-resolver-error');
});

test('inspector rechecks livewire state after every third party resolver side effect', function () {
    [$inspector, $finder, $factory] = freshCollisionInspector();
    $factory->resolveMissingComponent(function (string $name) use ($finder): null {
        $finder->addComponent(name: $name, class: CollisionFixture::class);

        return null;
    });

    expect(fn () => $inspector->assertUnclaimed(
        ['aura::global-search'],
        static fn (): null => null,
    ))->toThrow(ComponentSlotCollision::class, 'protected-claim-mutation');
});

test('inspector repeats the full protected identifier sweep after resolver side effects', function () {
    [$inspector, $finder, $factory] = freshCollisionInspector();
    $factory->resolveMissingComponent(function (string $name) use ($finder): null {
        if ($name === 'aura::media-manager') {
            $finder->addComponent(
                name: ComponentSlotRegistry::GLOBAL_SEARCH_TRANSPORT_ID,
                class: CollisionFixture::class,
            );
        }

        return null;
    });

    expect(fn () => $inspector->assertUnclaimed([
        ComponentSlotRegistry::GLOBAL_SEARCH_TRANSPORT_ID,
        'aura::media-manager',
    ], static fn (): null => null))->toThrow(ComponentSlotCollision::class, 'protected-claim-mutation');
});

test('inspector rejects every protected claim snapshot mutation by a resolver', function (string $mutation) {
    [$inspector, $finder, $factory] = freshCollisionInspector();
    $finder->addComponent(name: 'existing-unrelated-claim', class: CollisionFixture::class);

    $factory->resolveMissingComponent(function () use ($finder, $mutation): null {
        $claims = (function (): array {
            return $this->classComponents;
        })->call($finder);

        if ($mutation === 'addition') {
            $claims['added-unrelated-claim'] = CollisionFixture::class;
        } elseif ($mutation === 'removal') {
            unset($claims['existing-unrelated-claim']);
        } else {
            $claims['existing-unrelated-claim'] = GlobalSearch::class;
        }

        setLivewireInternal($finder, 'classComponents', $claims);

        return null;
    });

    expect(fn () => $inspector->assertUnclaimed(
        [ComponentSlotRegistry::GLOBAL_SEARCH_TRANSPORT_ID],
        static fn (): null => null,
    ))->toThrow(ComponentSlotCollision::class, 'protected-claim-mutation');
})->with(['addition', 'removal', 'replacement']);
