<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resource;

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

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

    expect((new NavigationModel())->pluralName())->toBe('NavigationModels');

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

    // Use firstWhere to find the item. If the item is found, it will not be null.
    $item = collect($nav['Aura'])->firstWhere('resource', 'NavigationModel');

    // Assert that an item was found.
    expect($item)->toBeNull();

    // Visit Dashboard and assert that the item is visible.
    $this->get(route('aura.dashboard'))
        ->assertDontSee('NavigationModels');

});

test('navigation item is hidden when the Role has no access to it', function () {
    Aura::registerResources([
        NavigationModel::class,
    ]);

    $nav = Aura::navigation();

    expect((new NavigationModel())->pluralName())->toBe('NavigationModels');

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

    expect((new GroupedNavigationModel())->pluralName())->toBe('NavigationModels');

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

    expect((new DropdownNavigationModel())->pluralName())->toBe('NavigationModels');
    expect($nav['Custom Group'])->not->toBeNull();
    expect($nav['Custom Group'][0]['group'])->toBe('Custom Group');
    expect($nav['Custom Group'][0]['dropdown'])->toBe('Custom Dropdown');
    expect($nav['Custom Group'][0]['group'])->toBe('Custom Group');

    $this->get(route('aura.dashboard'))
        ->assertSee('Custom Group')
        ->assertSee('Custom Dropdown')
        ->assertSee('NavigationModels');
});
