<?php

use Aura\Base\Fields\HasMany;
use Aura\Base\Fields\HasOne;
use Aura\Base\Resource;

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
                'type' => 'Aura\\Base\\Fields\\HasMany',
                'resource' => 'Aura\\Base\\Resources\\Attachment',
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
                'type' => 'Aura\\Base\\Fields\\HasOne',
                'resource' => 'Aura\\Base\\Resources\\Page',
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
    $model = new GroupRelationsTestModel;

    $fields = $model->getGroupedFields();

    $this->assertCount(2, $fields);
});

test('hasMany - field should not be grouped', function () {
    expect((new HasMany)->group)->toBe(false);
});

test('hasOne - field should not be grouped', function () {
    expect((new HasOne)->group)->toBe(false);
});
