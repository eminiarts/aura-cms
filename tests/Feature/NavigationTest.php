<?php

use Aura\Base\Resource;
use Aura\Base\Facades\Aura;

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

class NavigationModel extends Resource
{
    public static ?string $slug = 'navmodel';

    public static string $type = 'NavigationModel';

    public static $pluralName = 'NavigationModels';

    public static function getFields()
    {
        return [
            [
                'label' => 'Total',
                'name' => 'Total',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'total',
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [];
    }
}
class NavigationHiddenModel extends Resource
{
    protected static bool $showInNavigation = false;

    public static ?string $slug = 'navmodel';

    public static string $type = 'NavigationModel';

    public static $pluralName = 'NavigationModels';

    public static function getFields()
    {
        return [
            [
                'label' => 'Total',
                'name' => 'Total',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'total',
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [];
    }
}


class GroupedNavigationModel extends Resource
{
    public static ?string $slug = 'navmodel';

    protected static ?string $group = 'Custom Group';

    public static string $type = 'NavigationModel';

    public static $pluralName = 'NavigationModels';

    public static function getFields()
    {
        return [
            [
                'label' => 'Total',
                'name' => 'Total',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'total',
            ],
        ];
    }

    public static function getWidgets(): array
    {
        return [];
    }
}


test('navigation item is visible', function () {
    Aura::registerResources([
        NavigationModel::class,
    ]);

    $nav = Aura::navigation();

    expect((new NavigationModel())->pluralName())->toBe('NavigationModels');

    // Use firstWhere to find the item. If the item is found, it will not be null.
    $item = collect($nav['Resources'])->firstWhere('resource', "NavigationModel");

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
    $item = collect($nav['Resources'])->firstWhere('resource', "NavigationModel");

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
    $item = collect($nav['Resources'])->firstWhere('resource', "NavigationModel");

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
    $item = collect($nav['Custom Group'])->firstWhere('resource', "GroupedNavigationModel");

    // Assert that an item was found.
    expect($item)->not->toBeNull();

    // Visit Dashboard and assert that the item is visible.
    $this->get(route('aura.dashboard'))
        ->assertSee('NavigationModels');
});

test('navigation items can be dropdown', function () {
    //
});
