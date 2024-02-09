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
    //
})->todo();

test('navigation item is hidden when the Role has no access to it', function () {
    //
})->todo();

test('navigation items can be grouped', function () {
    //
})->todo();

test('navigation items can be dropdown', function () {
    //
})->todo();
