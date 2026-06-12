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

test('relation fields are not grouped together', function () {
    $model = new GroupRelationsTestModel;
    $fields = $model->getGroupedFields();

    expect($fields)->toHaveCount(2);
});

test('HasMany field has group set to false', function () {
    expect((new HasMany)->group)->toBe(false);
});

test('HasOne field has group set to false', function () {
    expect((new HasOne)->group)->toBe(false);
});

test('HasMany field stays at root level', function () {
    $model = new GroupRelationsTestModel;
    $fields = $model->getGroupedFields();

    expect($fields[0]['slug'])->toBe('attachments')
        ->and($fields[0]['type'])->toBe('Aura\Base\Fields\HasMany');
});

test('HasOne field stays at root level', function () {
    $model = new GroupRelationsTestModel;
    $fields = $model->getGroupedFields();

    expect($fields[1]['slug'])->toBe('pages')
        ->and($fields[1]['type'])->toBe('Aura\Base\Fields\HasOne');
});
