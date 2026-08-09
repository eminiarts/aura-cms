<?php

use Aura\Base\Facades\Aura;
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
