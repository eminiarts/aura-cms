<?php

use Aura\Base\Facades\Aura;
use Aura\Base\HookManager;
use Aura\Base\Navigation\Navigation as NavigationRegistry;
use Aura\Base\Resource;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

function serializedNavigationCacheRepository(): Repository
{
    return new Repository(new ArrayStore(serializesValues: true, serializableClasses: false));
}

class InterleavingNavigationArrayStore extends ArrayStore
{
    private ?Closure $beforeNavigationPut = null;

    private bool $interleaved = false;

    public function beforeNextNavigationPut(Closure $callback): void
    {
        $this->beforeNavigationPut = $callback;
        $this->interleaved = false;
    }

    public function put($key, $value, $seconds)
    {
        if (! $this->interleaved
            && $this->beforeNavigationPut
            && (str_starts_with($key, 'aura.navigation.') || str_starts_with($key, 'aura.cache.value.'))) {
            $this->interleaved = true;
            ($this->beforeNavigationPut)();
        }

        return parent::put($key, $value, $seconds);
    }
}

function customNavigationItem(string $resource, string $group = 'Custom Group', array $extra = []): array
{
    return array_merge([
        'icon' => '',
        'resource' => $resource,
        'type' => $resource,
        'name' => $resource,
        'slug' => str($resource)->slug()->toString(),
        'sort' => 100,
        'group' => $group,
        'route' => '#',
        'dropdown' => false,
        'showInNavigation' => true,
    ], $extra);
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new NavigationModel);
});

class NavigationModel extends Resource
{
    public static $pluralName = 'NavigationModels';

    public static ?string $slug = 'navmodel';

    public static string $type = 'NavigationModel';
}
class NavigationHiddenModel extends Resource
{
    public static $pluralName = 'NavigationModels';

    public static ?string $slug = 'navmodel';

    public static string $type = 'NavigationModel';

    protected static bool $showInNavigation = false;
}

class GroupedNavigationModel extends Resource
{
    public static $pluralName = 'NavigationModels';

    public static ?string $slug = 'navmodel';

    public static string $type = 'NavigationModel';

    protected static ?string $group = 'Custom Group';
}
class DropdownNavigationModel extends Resource
{
    public static $pluralName = 'NavigationModels';

    public static ?string $slug = 'navmodel';

    public static string $type = 'NavigationModel';

    protected static $dropdown = 'Custom Dropdown';

    protected static ?string $group = 'Custom Group';
}

test('navigation item is visible', function () {
    Aura::registerResources([
        NavigationModel::class,
    ]);

    $nav = Aura::navigation();

    expect((new NavigationModel)->pluralName())->toBe('NavigationModels');

    // Use firstWhere to find the item. If the item is found, it will not be null.
    $item = collect($nav['Resources'])->firstWhere('resource', 'NavigationModel');

    // Assert that an item was found.
    expect($item)->not->toBeNull();

    // Visit Dashboard and assert that the item is visible.
    $this->get(route('aura.dashboard'))
        ->assertSee('NavigationModels');
});

test('navigation item can be hidden', function () {
    Aura::registerResources([
        NavigationHiddenModel::class,
    ]);

    $nav = Aura::navigation();

    Aura::clear();

    // Visit Dashboard and assert that the item is visible.
    $this->get(route('aura.dashboard'))
        ->assertDontSee('NavigationModels');

});

test('navigation item is hidden when the Role has no access to it', function () {
    Cache::swap(serializedNavigationCacheRepository());

    Aura::registerResources([
        NavigationModel::class,
    ]);

    $nav = Aura::navigation();

    expect((new NavigationModel)->pluralName())->toBe('NavigationModels');

    // Use firstWhere to find the item. If the item is found, it will not be null.
    $item = collect($nav['Resources'])->firstWhere('resource', 'NavigationModel');

    // Assert that an item was found.
    expect($item)->not->toBeNull();

    // Visit Dashboard and assert that the item is visible.
    $this->get(route('aura.dashboard'))
        ->assertSee('NavigationModels');

    // Create a role with no access to the resource
    $this->actingAs(createAdmin());

    // Visit Dashboard and assert that the item is not visible.
    $this->get(route('aura.dashboard'))
        ->assertDontSee('NavigationModels');

});

test('navigation items can be grouped', function () {
    Aura::registerResources([
        GroupedNavigationModel::class,
    ]);

    $nav = Aura::navigation();

    expect((new GroupedNavigationModel)->pluralName())->toBe('NavigationModels');

    expect($nav['Custom Group'])->not->toBeNull();

    // Use firstWhere to find the item. If the item is found, it will not be null.
    $item = collect($nav['Custom Group'])->firstWhere('resource', 'GroupedNavigationModel');

    // Assert that an item was found.
    expect($item)->not->toBeNull();

    // Visit Dashboard and assert that the item is visible.
    $this->get(route('aura.dashboard'))
        ->assertSee('Custom Group')
        ->assertSee('NavigationModels');
});

test('navigation items can be dropdown', function () {
    Aura::registerResources([
        DropdownNavigationModel::class,
    ]);

    $nav = Aura::navigation();

    expect((new DropdownNavigationModel)->pluralName())->toBe('NavigationModels');
    expect($nav['Custom Group'])->not->toBeNull();
    expect($nav['Custom Group'][0]['group'])->toBe('Custom Group');
    expect($nav['Custom Group'][0]['dropdown'])->toBe('Custom Dropdown');
    expect($nav['Custom Group'][0]['group'])->toBe('Custom Group');

    $this->get(route('aura.dashboard'))
        ->assertSee('Custom Group')
        ->assertSee('Custom Dropdown')
        ->assertSee('NavigationModels');
});

test('navigation survives a serialized cache read in a fresh application container', function () {
    $cache = serializedNavigationCacheRepository();
    Cache::swap($cache);
    Aura::registerResources([NavigationModel::class]);

    expect(Aura::navigation())
        ->toBeInstanceOf(Collection::class)
        ->toHaveKey('Resources');

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($this->user);
    Aura::fake();
    Aura::registerResources([NavigationModel::class]);

    expect(Aura::navigation())
        ->toBeInstanceOf(Collection::class)
        ->toHaveKey('Resources');
});

test('navigation cache changes when the registered resource context changes', function () {
    Cache::swap(serializedNavigationCacheRepository());
    Aura::registerResources([NavigationModel::class]);

    expect(Aura::navigation())->not->toHaveKey('Custom Group');

    Aura::registerResources([GroupedNavigationModel::class]);

    expect(Aura::navigation())
        ->toHaveKey('Custom Group')
        ->and(collect(Aura::navigation()['Custom Group'])->firstWhere('resource', GroupedNavigationModel::class))
        ->not->toBeNull();
});

test('navigation cache changes when navigation hooks change', function () {
    Cache::swap(serializedNavigationCacheRepository());
    Aura::registerResources([NavigationModel::class]);

    expect(Aura::navigation())->not->toHaveKey('Custom Group');

    NavigationRegistry::add([[
        'icon' => '',
        'resource' => 'CustomPage',
        'type' => 'CustomPage',
        'name' => 'Custom Page',
        'slug' => 'custom-page',
        'sort' => 100,
        'group' => 'Custom Group',
        'route' => '#',
        'dropdown' => false,
        'showInNavigation' => true,
    ]]);

    expect(Aura::navigation())
        ->toHaveKey('Custom Group')
        ->and(collect(Aura::navigation()['Custom Group'])->firstWhere('resource', 'CustomPage'))
        ->not->toBeNull();
});

test('different opaque hooks in fresh managers never share navigation cache entries', function () {
    $cache = serializedNavigationCacheRepository();
    Cache::swap($cache);

    $firstManager = new HookManager;
    app()->instance('hook_manager', $firstManager);
    $firstManager->addHook('navigation', function (Collection $navigation): Collection {
        return $navigation->push(customNavigationItem('FirstCallbackPage'));
    });

    expect(collect(Aura::navigation()['Custom Group'])->pluck('resource'))
        ->toContain('FirstCallbackPage');

    $this->refreshApplication();
    Cache::swap($cache);
    $this->actingAs($this->user);
    Aura::fake();

    $secondManager = new HookManager;
    app()->instance('hook_manager', $secondManager);
    $secondManager->addHook('navigation', function (Collection $navigation): Collection {
        return $navigation->push(customNavigationItem('SecondCallbackPage'));
    });

    expect(collect(Aura::navigation()['Custom Group'])->pluck('resource'))
        ->toContain('SecondCallbackPage')
        ->not->toContain('FirstCallbackPage');
});

test('hook cache fingerprints are stable only for deterministic callables', function () {
    $firstManager = new HookManager;
    $firstManager->addHook('stable', 'trim');

    $secondManager = new HookManager;
    $secondManager->addHook('stable', 'trim');

    $differentManager = new HookManager;
    $differentManager->addHook('stable', 'strtolower');

    $opaqueManager = new HookManager;
    $opaqueManager->addHook('stable', fn ($value) => $value);

    expect($firstManager->cacheFingerprint('stable'))
        ->toBe($secondManager->cacheFingerprint('stable'))
        ->not->toBe($differentManager->cacheFingerprint('stable'))
        ->and($opaqueManager->cacheFingerprint('stable'))->toBeNull();
});

test('navigation registration fingerprints are stable across fresh managers', function () {
    $item = customNavigationItem('StablePage');

    $firstManager = new HookManager;
    app()->instance('hook_manager', $firstManager);
    NavigationRegistry::add([$item]);

    $secondManager = new HookManager;
    app()->instance('hook_manager', $secondManager);
    NavigationRegistry::add([$item]);

    $differentManager = new HookManager;
    app()->instance('hook_manager', $differentManager);
    NavigationRegistry::add([customNavigationItem('DifferentPage')]);

    expect($firstManager->cacheFingerprint('navigation'))
        ->toBe($secondManager->cacheFingerprint('navigation'))
        ->not->toBe($differentManager->cacheFingerprint('navigation'));
});

test('nested non-scalar navigation payloads bypass serialization', function (Closure $makeUnsafeValue) {
    $store = new ArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));
    Aura::registerResources([NavigationModel::class]);

    $unsafeValue = $makeUnsafeValue();
    NavigationRegistry::add([
        customNavigationItem('UnsafePage', 'Unsafe Group', [
            'metadata' => ['unsafe' => $unsafeValue],
        ]),
    ]);

    $readUnsafeValue = function () {
        $item = collect(Aura::navigation()['Unsafe Group'])->firstWhere('resource', 'UnsafePage');

        return $item['metadata']['unsafe'];
    };

    expect(get_debug_type($readUnsafeValue()))->toBe(get_debug_type($unsafeValue))
        ->and(get_debug_type($readUnsafeValue()))->toBe(get_debug_type($unsafeValue));

    foreach (array_keys($store->all(false)) as $key) {
        expect(str_starts_with($key, 'aura.navigation.') || str_starts_with($key, 'aura.cache.value.'))
            ->toBeFalse();
    }

    if (is_resource($unsafeValue)) {
        fclose($unsafeValue);
    }
})->with([
    'closure' => fn () => fn (): string => 'unsafe',
    'resource' => fn () => fopen('php://memory', 'r'),
    'model' => fn () => new NavigationModel,
]);

test('navigation retries when a deterministic hook races its first cache write', function () {
    $store = new InterleavingNavigationArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));
    Aura::registerResources([NavigationModel::class]);

    $store->beforeNextNavigationPut(function (): void {
        NavigationRegistry::add([customNavigationItem('RacingPage', 'Racing Group')]);
    });

    expect(collect(Aura::navigation()['Racing Group'])->pluck('resource'))
        ->toContain('RacingPage')
        ->and(collect(Aura::navigation()['Racing Group'])->pluck('resource'))
        ->toContain('RacingPage');
});
