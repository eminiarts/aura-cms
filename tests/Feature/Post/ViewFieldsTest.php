<?php

use Eminiarts\Aura\Models\Post;

class ViewFieldsTestModel extends Post
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'tab-1',
                'global' => true,
            ],
            [
                'name' => 'Text 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text1',
            ],
            [
                'name' => 'Tab 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'tab-2',
                'global' => true,
                'on_view' => false,
            ],
            [
                'label' => 'Text 2',
                'name' => 'Text 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text2',
            ],
        ];
    }
}

test('field inherits on_view from parent', function () {
    $model = new ViewFieldsTestModel();

    $fields = $model->viewFields();

    expect($fields)->toHaveCount(1);

    expect($fields[0]['fields'])->toHaveCount(1);

    expect($fields[0]['fields'][0]['fields'])->toHaveCount(1);

    expect($fields[0]['fields'][0]['fields'][0]['slug'])->toBe('text1');

    expect($fields[0]['fields'][0]['fields'][0]['slug'])->not->toBe('text2');
});

class ViewFieldsTestModel2 extends Post
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'tab-1',
                'global' => true,
            ],
            [
                'name' => 'Text 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text1',
                'on_view' => true,
            ],
            [
                'name' => 'Tab 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'tab-2',
                'global' => true,
            ],
            [
                'label' => 'Text 2',
                'name' => 'Text 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'numeric',
                'conditional_logic' => [],
                'slug' => 'text2',
                'on_view' => false,
            ],
        ];
    }
}

test('field is hidden when on_view is false', function () {
    $model = new ViewFieldsTestModel2();

    $fields = $model->viewFields();

    expect($fields)->toHaveCount(1);

    expect($fields[0]['fields'])->toHaveCount(2);

    expect($fields[0]['fields'][0]['fields'])->toHaveCount(1);

    expect($fields[0]['fields'][0]['fields'][0]['slug'])->toBe('text1');

    expect($fields[0]['fields'][0]['fields'][0]['slug'])->not->toBe('text2');

    expect(optional($fields[0]['fields'][1])['fields'])->toBeEmpty();
});
