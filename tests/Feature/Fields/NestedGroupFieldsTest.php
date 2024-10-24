<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Illuminate\Support\Facades\DB;

class NestedGroupFieldsModel extends Resource
{
    public static string $type = 'NestedGroupFields';

    public static function getFields()
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Group',
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

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('create model with nested fields', function () {

    $model = NestedGroupFieldsModel::create([
        'settings.option_1' => '1',
        'settings.option_2' => '2',
        'settings.option_3' => '3',
    ]);

    // dd($model->toArray());

    $meta = DB::table('meta')->get();

    $this->assertDatabaseMissing('meta', [
        'key' => 'settings',
        'metable_id' => $model->id,
        'metable_type' => NestedGroupFieldsModel::class,
    ]);

});
