<?php

use Eminiarts\Aura\Models\Post;

class GroupRelationsTestModel extends Post
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

test('hasMany - field should not be grouped', function () {
    $model = new GroupRelationsTestModel();

    $fields = $model->getGroupedFields();

    $this->assertCount(3, $fields);
});
