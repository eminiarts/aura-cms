<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Aura\Base\Resources\Tag;
use Aura\Base\Resources\User;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

class TagsRelationFieldModel extends Resource
{
    public static string $type = 'TagsRelationModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'User Tags',
                'slug' => 'users',
                'type' => 'Aura\\Base\\Fields\\Tags',
                'resource' => 'Aura\\Base\\Resources\\User',
                'create' => false,
                'validation' => '',
                'conditional_logic' => [],
                'on_index' => false,
                'on_forms' => true,
                'on_view' => true,
            ],
        ];
    }
}

test('TagsRelationFieldModel - Saving Tags', function () {

    $users = User::factory()->count(3)->create();

    $model = TagsRelationFieldModel::create(['users' => $users->pluck('id')]);

    expect($model->tags)->toHaveCount(3);

    expect($model->fields['users'])->toBeArray();
    expect($model->fields['users'])->toEqual(Tag::get()->pluck('id')->toArray());
});
