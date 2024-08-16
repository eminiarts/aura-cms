<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Tags;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Aura\Base\Resources\Tag;
use Livewire\Livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

class TagsFieldModel extends Resource
{
    public static string $type = 'TagsModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Tags',
                'slug' => 'tags',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Resources\\Tag',
                'create' => true,
                'validation' => '',
                'conditional_logic' => [],
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],
        ];
    }
}

test('check Tags Fields', function () {
    $slug = new Tags;

    $fields = collect($slug->getFields());

    expect($fields->firstWhere('slug', 'create'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'resource'))->not->toBeNull();
});

// test('Tags Field - Name rendered', function () {
//     $field = [
//                 'name' => 'Tags',
//                 'slug' => 'tags',
//                 'type' => 'Aura\\Base\\Fields\\Tags',
//                 'resource' => 'Aura\\Base\\Resources\\Tag',
//                 'create' => true,
//                 'validation' => '',
//                 'conditional_logic' => [],
//                 'on_index' => false,
//                 'on_forms' => true,
//                 'on_view' => true,
//             ];

//     $fieldClass = app($field['type']);
//     $field['field'] = $fieldClass;

//     $view = $this->withViewErrors([])->blade(
//         '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
//         ['component' => $fieldClass->edit(), 'field' => $field, 'form' => []]
//     );

//     expect((string) $view)->toContain('>Tags</label>');
// });

test('Tags Field - Default Value set', function () {
    Aura::fake();
    Aura::setModel(new TagsFieldModel);

    $component = Livewire::test(Create::class, ['slug' => 'TagsModel'])
        ->assertSee('Tags')
        ->assertSet('form.fields.tags', []);
});

test('Text Field - Prefix rendered', function () {
    $field = [
        'name' => 'Text for Test',
        'type' => 'Aura\\Base\\Fields\\Text',
        'prefix' => 'Prefix for Test',
        'validation' => '',
        'conditional_logic' => [],
        'slug' => 'text',
    ];

    $fieldClass = app($field['type']);
    $field['field'] = $fieldClass;

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
        ['component' => $fieldClass->edit(), 'field' => $field, 'form' => []]
    );

    expect((string) $view)->toContain('Prefix for Test');
});

test('TagsFieldModel - Saving Tags', function () {
    $model = TagsFieldModel::create(['tags' => ['123', '456', 'Enes']]);

    expect($model->tags)->toHaveCount(3);

    expect($model->fields['tags'])->toBeArray();
    expect($model->fields['tags'])->toEqual(Tag::get()->pluck('id')->toArray());
});
