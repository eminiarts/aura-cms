<?php

use Eminiarts\Aura\Fields\HasMany;
use Eminiarts\Aura\Fields\HasOne;
use Eminiarts\Aura\Fields\HasOneOfMany;
use Eminiarts\Aura\Resource;

class GroupRelationsTestModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Attachments',
                'slug' => 'attachments',
                'type' => 'Eminiarts\\Aura\\Fields\\HasMany',
                'resource' => 'Eminiarts\\Aura\\Resources\\Attachment',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Pages',
                'slug' => 'pages',
                'type' => 'Eminiarts\\Aura\\Fields\\HasOne',
                'resource' => 'Eminiarts\\Aura\\Resources\\Page',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Latest Post',
                'slug' => 'latest_post',
                'type' => 'Eminiarts\\Aura\\Fields\\HasOneOfMany',
                'resource' => 'Eminiarts\\Aura\\Resources\\Post',
                'option' => 'latest',
                'validation' => '',
                'conditional_logic' => [],
                'wrapper' => '',
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }
}

test('hasMany - fields should not be grouped', function () {
    $model = new GroupRelationsTestModel();

    $fields = $model->getGroupedFields();

    $this->assertCount(3, $fields);
});

test('hasMany - field should not be grouped', function () {
    expect((new HasMany())->group)->toBe(false);
});

test('hasOne - field should not be grouped', function () {
    expect((new HasOne())->group)->toBe(false);
});

test('hasOneOfMany - field should not be grouped', function () {
    expect((new HasOneOfMany())->group)->toBe(false);
});
