<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Text;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;


class NestedFieldsModel extends Resource
{
    public static string $type = 'NestedFields';

    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Json',
                'slug' => 'settings',
                'name' => 'Settings',
                'on_index' => false,
                'on_forms' => false,
                'on_view' => false,
                'searchable' => false,
                'validation' => '',
                'conditional_logic' => '',
            ],

            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'settings.option_1',
                'name' => 'Settings Option 1',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
                'validation' => '',
                'conditional_logic' => '',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'settings.option_2',
                'name' => 'Settings Option 2',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
                'validation' => '',
                'conditional_logic' => '',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'settings.option_3',
                'name' => 'Settings Option 3',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
                'validation' => '',
                'conditional_logic' => '',
            ],
        ];
    }
}

class NestedFields2Model extends Resource
{
    public static string $type = 'NestedFields2';

    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'settings.option_1',
                'name' => 'Settings Option 1',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
                'validation' => '',
                'conditional_logic' => '',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'settings.option_2',
                'name' => 'Settings Option 2',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
                'validation' => '',
                'conditional_logic' => '',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'settings.option_3',
                'name' => 'Settings Option 3',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
                'validation' => '',
                'conditional_logic' => '',
            ],
        ];
    }
}

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('create model with nested fields', function () {

    $model = NestedFieldsModel::create([
        'settings.option_1' => '1',
        'settings.option_2' => '2',
        'settings.option_3' => '3',
    ]);

    // dd($model->toArray());

    expect($model->settings['option_1'])->toBe('1');
    expect($model->settings['option_2'])->toBe('2');
    expect($model->settings['option_3'])->toBe('3');

    expect($model->settings)->toBeArray();
    expect($model->settings)->toHaveCount(3);
    expect($model->settings)->toMatchArray([
        'option_1' => '1',
        'option_2' => '2',
        'option_3' => '3',
    ]);

    $this->assertDatabaseMissing('post_meta', [
        'key' => 'settings.option_1'
    ]);
});


test('create model with nested fields without JSON Parent', function () {

    $model = NestedFields2Model::create([
        'settings.option_1' => '1',
        'settings.option_2' => '2',
        'settings.option_3' => '3',
    ]);

    // dd($model->toArray());

    expect($model->settings)->toBeNull();

    $this->assertDatabaseMissing('post_meta', [
        'key' => 'settings.option_1'
    ]);
});


test('get model with nested fields doesnt show unnested attribute', function () {

    $model = NestedFieldsModel::create([
        'settings.option_1' => '1',
        'settings.option_2' => '2',
        'settings.option_3' => '3',
    ]);

    $retrievedModel = NestedFieldsModel::find($model->id);

    expect($retrievedModel->settings['option_1'])->toBe('1');
    expect($retrievedModel->settings['option_2'])->toBe('2');
    expect($retrievedModel->settings['option_3'])->toBe('3');

    expect($retrievedModel->settings)->toBeArray();
    expect($retrievedModel->settings)->toHaveCount(3);
    expect($retrievedModel->settings)->toMatchArray([
        'option_1' => '1',
        'option_2' => '2',
        'option_3' => '3',
    ]);


    $fields = $retrievedModel->fields->toArray();

    unset($fields['settings']);


    expect($fields)->not->toHaveKey('settings.option_1');
    expect($fields)->not->toHaveKey('settings.option_2');
    expect($fields)->not->toHaveKey('settings.option_3');
});
