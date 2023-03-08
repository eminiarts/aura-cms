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

test('fields get grouped when field group is true', function () {
    $model = new ViewFieldsTestModel();

    $fields = $model->viewFields();

    expect($fields)->toHaveCount(1);
    
    expect($fields[0]['fields'])->toHaveCount(1);

    expect($fields[0]['fields'][0]['fields'])->toHaveCount(1);

    expect($fields[0]['fields'][0]['fields'][0]['slug'])->toBe('text1');

    expect($fields[0]['fields'][0]['fields'][0]['slug'])->not->toBe('text2');
});
