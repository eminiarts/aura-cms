<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\ComponentSlots\ComponentSlotLifecycleException;
use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Livewire\MediaManager;
use Aura\Base\Tests\Fixtures\ComponentSlots\HostGlobalSearch;
use Illuminate\Support\Facades\File;
use Livewire\Livewire;

test('Aura boots and freezes both default component slots in Livewire', function () {
    $registry = app(ComponentSlotRegistry::class);
    $factory = app('livewire.factory');

    expect(config('aura.component-slots'))->toBe([
        'global-search' => null,
        'media-manager' => null,
    ])->and($registry->state())->toBe('finalized')
        ->and($registry->winner('global-search'))->toBe(GlobalSearch::class)
        ->and($registry->winner('media-manager'))->toBe(MediaManager::class)
        ->and($factory->resolveComponentNameAndClass(ComponentSlotRegistry::GLOBAL_SEARCH_TRANSPORT_ID))
        ->toBe([ComponentSlotRegistry::GLOBAL_SEARCH_TRANSPORT_ID, GlobalSearch::class])
        ->and($factory->resolveComponentNameAndClass(ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID))
        ->toBe([ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID, MediaManager::class])
        ->and($factory->resolveComponentNameAndClass('aura::global-search'))
        ->toBe(['aura::global-search', GlobalSearch::class])
        ->and($factory->resolveComponentNameAndClass('aura.base.livewire.global-search'))
        ->toBe(['aura.base.livewire.global-search', GlobalSearch::class])
        ->and($factory->resolveComponentNameAndClass('aura::media-manager'))
        ->toBe(['aura::media-manager', MediaManager::class])
        ->and($factory->resolveComponentNameAndClass('aura.base.livewire.media-manager'))
        ->toBe(['aura.base.livewire.media-manager', MediaManager::class])
        ->and($factory->resolveComponentNameAndClass('aura::dashboard')[1])
        ->toBe(config('aura.components.dashboard'));
});

test('Aura registers no Livewire namespace and rejects late declarations', function () {
    $finder = app('livewire.finder');
    $classNamespaces = (fn (): array => $this->classNamespaces)->call($finder);
    $viewNamespaces = (fn (): array => $this->viewNamespaces)->call($finder);

    expect($classNamespaces)->not->toHaveKey('aura')
        ->and($viewNamespaces)->not->toHaveKey('aura')
        ->and(fn () => Aura::registerComponentSlots('vendor/package', [
            'global-search' => GlobalSearch::class,
        ]))->toThrow(ComponentSlotLifecycleException::class, 'collecting');
});

test('default global search aliases hydrate and the frozen map survives Aura worker resets', function () {
    $this->actingAs(createSuperAdmin());
    $registry = app(ComponentSlotRegistry::class);

    foreach ([
        ComponentSlotRegistry::GLOBAL_SEARCH_TRANSPORT_ID,
        'aura::global-search',
        'aura.base.livewire.global-search',
    ] as $identifier) {
        Livewire::test($identifier)
            ->set('search', '')
            ->assertSet('search', '');
    }

    Aura::flushState();

    expect(app(ComponentSlotRegistry::class))->toBe($registry)
        ->and($registry->state())->toBe('finalized')
        ->and($registry->winner('global-search'))->toBe(GlobalSearch::class)
        ->and(app('livewire.factory')->resolveComponentNameAndClass('aura::global-search')[1])
        ->toBe(GlobalSearch::class);
});

test('component slot host class strings survive Laravel config caching', function () {
    $hostConfigPath = config_path('aura.php');
    $bootstrapPath = app()->bootstrapPath('app.php');

    File::put($hostConfigPath, <<<'PHP'
<?php

use Aura\Base\Tests\Fixtures\ComponentSlots\HostGlobalSearch;

return [
    'component-slots' => [
        'global-search' => HostGlobalSearch::class,
        'media-manager' => null,
    ],
];
PHP);
    File::put($bootstrapPath, <<<'PHP'
<?php

use Aura\Base\AuraServiceProvider;
use Illuminate\Foundation\Application;
use Livewire\LivewireServiceProvider;

return Application::configure(basePath: dirname(__DIR__))
    ->withProviders([
        LivewireServiceProvider::class,
        AuraServiceProvider::class,
    ])
    ->create();
PHP);

    try {
        $this->artisan('config:cache')->assertSuccessful();

        $cached = require app()->getCachedConfigPath();

        expect($cached['aura']['component-slots'])->toBe([
            'global-search' => HostGlobalSearch::class,
            'media-manager' => null,
        ]);
    } finally {
        $this->artisan('config:clear');
        File::delete($hostConfigPath);
        File::delete($bootstrapPath);
    }
});
