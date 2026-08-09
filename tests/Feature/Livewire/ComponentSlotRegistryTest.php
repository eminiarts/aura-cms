<?php

use Aura\Base\Livewire\ComponentSlots\ComponentSlotCandidateValidator;
use Aura\Base\Livewire\ComponentSlots\ComponentSlotConflict;
use Aura\Base\Livewire\ComponentSlots\ComponentSlotLifecycleException;
use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Livewire\ComponentSlots\InvalidComponentSlotCandidate;
use Aura\Base\Livewire\ComponentSlots\LivewireComponentSlotBridge;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Livewire\MediaManager;
use Illuminate\Config\Repository;
use Livewire\Component;

class RegistryPluginSearch extends Component {}

class RegistryOtherSearch extends Component {}

class RegistryHostSearch extends Component {}

class RegistryPluginMedia extends Component {}

class RegistryHostMedia extends Component {}

class FakeLivewireComponentSlotBridge implements LivewireComponentSlotBridge
{
    public bool $compatible = false;

    public bool $failCollisionCheck = false;

    /** @var array<string, class-string<Component>> */
    public array $registrations = [];

    public ?Closure $resolver = null;

    /** @var list<string> */
    public array $unclaimedIdentifiers = [];

    public function assertCompatible(): void
    {
        $this->compatible = true;
    }

    public function assertUnclaimed(array $identifiers, Closure $auraResolver): void
    {
        $this->unclaimedIdentifiers = $identifiers;

        if ($this->failCollisionCheck) {
            throw new RuntimeException('collision');
        }
    }

    public function installMissingResolver(Closure $resolver): void
    {
        $this->resolver = $resolver;
    }

    public function register(string $name, string $component): void
    {
        $this->registrations[$name] = $component;
    }

    public function reserve(string $name, string $intrinsicComponent, Closure $auraResolver): void
    {
        // The fake has no conventional class discovery to suppress.
    }

    public function resolve(string $name): array
    {
        $component = $this->registrations[$name] ?? ($this->resolver)($name);

        if (! is_string($component)) {
            throw new RuntimeException("Unable to resolve [{$name}].");
        }

        return [$name, $component];
    }
}

function componentSlotRegistry(array $auraConfig = []): array
{
    $validator = Mockery::mock(ComponentSlotCandidateValidator::class);
    $validator->shouldReceive('validate')
        ->byDefault()
        ->andReturnUsing(fn (string $slot, string $source, mixed $candidate): mixed => $candidate);

    $bridge = new FakeLivewireComponentSlotBridge;
    $config = new Repository([
        'aura' => array_replace_recursive([
            'component-slots' => [
                'global-search' => null,
                'media-manager' => null,
            ],
            'components' => [
                'media-manager' => MediaManager::class,
            ],
        ], $auraConfig),
    ]);

    return [new ComponentSlotRegistry($config, $validator, $bridge), $bridge, $validator];
}

test('registry freezes exactly two default slots and keeps all compatibility aliases on each winner', function () {
    [$registry, $bridge] = componentSlotRegistry();

    $registry->install([
        'aura::dashboard' => RegistryOtherSearch::class,
    ]);

    expect($bridge->compatible)->toBeTrue()
        ->and($registry->state())->toBe('collecting')
        ->and(fn () => $bridge->resolve('aura::global-search'))
        ->toThrow(ComponentSlotLifecycleException::class, 'before finalization');

    $registry->finalize();

    expect($registry->state())->toBe('finalized')
        ->and($registry->winner('global-search'))->toBe(GlobalSearch::class)
        ->and($registry->winner('media-manager'))->toBe(MediaManager::class)
        ->and($bridge->registrations)->toBe([
            ComponentSlotRegistry::GLOBAL_SEARCH_TRANSPORT_ID => GlobalSearch::class,
            'aura::global-search' => GlobalSearch::class,
            'aura.base.livewire.global-search' => GlobalSearch::class,
            ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID => MediaManager::class,
            'aura::media-manager' => MediaManager::class,
            'aura.base.livewire.media-manager' => MediaManager::class,
        ])
        ->and($bridge->resolve('aura::global-search'))->toBe(['aura::global-search', GlobalSearch::class])
        ->and($bridge->resolve('aura.base.livewire.global-search'))->toBe(['aura.base.livewire.global-search', GlobalSearch::class])
        ->and($bridge->resolve('aura::media-manager'))->toBe(['aura::media-manager', MediaManager::class])
        ->and($bridge->resolve('aura.base.livewire.media-manager'))->toBe(['aura.base.livewire.media-manager', MediaManager::class])
        ->and($bridge->resolve('aura::dashboard'))->toBe(['aura::dashboard', RegistryOtherSearch::class]);
});

test('host choices beat valid plugins while a unique plugin beats the Aura default', function () {
    [$registry] = componentSlotRegistry([
        'component-slots' => [
            'global-search' => RegistryHostSearch::class,
            'media-manager' => null,
        ],
    ]);

    $registry->install();
    $registry->register('zeta/plugin', [
        'global-search' => RegistryPluginSearch::class,
        'media-manager' => RegistryPluginMedia::class,
    ]);
    $registry->register('alpha/plugin', [
        'global-search' => RegistryOtherSearch::class,
    ]);
    $registry->finalize();

    expect($registry->winner('global-search'))->toBe(RegistryHostSearch::class)
        ->and($registry->winner('media-manager'))->toBe(RegistryPluginMedia::class);
});

test('duplicate plugin values collapse but distinct values fail with sorted sources', function () {
    [$duplicates] = componentSlotRegistry();
    $duplicates->install();
    $duplicates->register('zeta/plugin', ['global-search' => RegistryPluginSearch::class]);
    $duplicates->register('alpha/plugin', ['global-search' => RegistryPluginSearch::class]);
    $duplicates->finalize();

    expect($duplicates->winner('global-search'))->toBe(RegistryPluginSearch::class);

    [$ambiguous] = componentSlotRegistry();
    $ambiguous->install();
    $ambiguous->register('zeta/plugin', ['global-search' => RegistryOtherSearch::class]);
    $ambiguous->register('alpha/plugin', ['global-search' => RegistryPluginSearch::class]);

    expect(fn () => $ambiguous->finalize())
        ->toThrow(ComponentSlotConflict::class, 'alpha/plugin, zeta/plugin');
});

test('registration rejects malformed sources unknown slots same source conflicts and late changes', function () {
    [$registry] = componentSlotRegistry();
    $registry->install();

    expect(fn () => $registry->register('Vendor/Package', ['global-search' => RegistryPluginSearch::class]))
        ->toThrow(ComponentSlotConflict::class, 'lowercase Composer package')
        ->and(fn () => $registry->register('vendor/package', ['navigation' => RegistryPluginSearch::class]))
        ->toThrow(ComponentSlotConflict::class, 'Unknown component slot')
        ->and(fn () => $registry->winner('global-search'))
        ->toThrow(ComponentSlotLifecycleException::class, 'before finalization');

    $registry->register('vendor/package', ['global-search' => RegistryPluginSearch::class]);
    $registry->register('vendor/package', ['global-search' => RegistryPluginSearch::class]);

    expect(fn () => $registry->register('vendor/package', ['global-search' => RegistryOtherSearch::class]))
        ->toThrow(ComponentSlotConflict::class, 'already declared');

    $registry->finalize();

    expect(fn () => $registry->register('other/package', ['global-search' => RegistryOtherSearch::class]))
        ->toThrow(ComponentSlotLifecycleException::class, 'collecting');
});

test('same source canonical class spellings are idempotent', function () {
    [$registry] = componentSlotRegistry();
    $registry->install();
    $registry->register('vendor/package', ['global-search' => RegistryPluginSearch::class]);
    $registry->register('vendor/package', ['global-search' => '\\'.RegistryPluginSearch::class]);
    $registry->finalize();

    expect($registry->winner('global-search'))->toBe(RegistryPluginSearch::class);
});

test('registry rejects candidates with more than one leading namespace separator', function () {
    [$registry, , $validator] = componentSlotRegistry();
    $candidate = '\\\\'.RegistryPluginSearch::class;
    $validator->shouldReceive('validate')
        ->once()
        ->with('global-search', 'vendor/package', $candidate)
        ->andThrow(new InvalidComponentSlotCandidate('at most one leading slash'));
    $registry->install();
    $registry->register('vendor/package', ['global-search' => $candidate]);

    expect(fn () => $registry->finalize())
        ->toThrow(InvalidComponentSlotCandidate::class, 'at most one leading slash');
});

test('legacy media config remains a host choice and conflicting new and legacy values fail', function () {
    [$legacy] = componentSlotRegistry([
        'components' => ['media-manager' => RegistryHostMedia::class],
    ]);
    $legacy->install();
    $legacy->register('vendor/package', ['media-manager' => RegistryPluginMedia::class]);
    $legacy->finalize();

    expect($legacy->winner('media-manager'))->toBe(RegistryHostMedia::class);

    [$matching] = componentSlotRegistry([
        'component-slots' => ['media-manager' => RegistryHostMedia::class],
        'components' => ['media-manager' => RegistryHostMedia::class],
    ]);
    $matching->install();
    $matching->finalize();

    expect($matching->winner('media-manager'))->toBe(RegistryHostMedia::class);

    [$conflicting] = componentSlotRegistry([
        'component-slots' => ['media-manager' => RegistryHostMedia::class],
        'components' => ['media-manager' => RegistryPluginMedia::class],
    ]);
    $conflicting->install();

    expect(fn () => $conflicting->finalize())
        ->toThrow(ComponentSlotConflict::class, 'legacy');
});

test('every declaration is validated even when a host shadows plugins', function () {
    [$registry, , $validator] = componentSlotRegistry([
        'component-slots' => ['global-search' => RegistryHostSearch::class],
    ]);
    $registry->install();
    $registry->register('vendor/package', ['global-search' => RegistryPluginSearch::class]);

    $validator->shouldReceive('validate')
        ->with('global-search', 'vendor/package', RegistryPluginSearch::class)
        ->once()
        ->andThrow(new RuntimeException('invalid shadowed declaration'));

    expect(fn () => $registry->finalize())
        ->toThrow(RuntimeException::class, 'invalid shadowed declaration');
});

test('collision preflight runs before any transport registration', function () {
    [$registry, $bridge] = componentSlotRegistry();
    $bridge->failCollisionCheck = true;
    $registry->install();

    expect(fn () => $registry->finalize())
        ->toThrow(RuntimeException::class, 'collision')
        ->and($bridge->registrations)->toBe([]);
});

test('separate registry instances do not share lifecycle declarations or winners', function () {
    [$first] = componentSlotRegistry();
    [$second] = componentSlotRegistry();
    $first->install();
    $second->install();
    $first->register('vendor/package', ['global-search' => RegistryPluginSearch::class]);
    $first->finalize();
    $second->finalize();

    expect($first->winner('global-search'))->toBe(RegistryPluginSearch::class)
        ->and($second->winner('global-search'))->toBe(GlobalSearch::class);
});
