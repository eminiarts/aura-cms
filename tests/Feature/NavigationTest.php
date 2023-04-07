<?php

use Eminiarts\Aura\Resource;

class NavigationModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'label' => 'Total',
                'name' => 'Total',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
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
    //
})->todo();

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
