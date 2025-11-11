<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
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

test('TagsRelationFieldModel - Saving Tags', function () {

    $currentTeam = $this->user->currentTeam;

    $users = User::factory()->count(3)->create()->each(function ($user) use ($currentTeam) {
        if (config('aura.teams')) {
            $user->teams()->attach($currentTeam->id, ['role_id' => $this->user->roles->first()->id]);
        }
    });

    $model = TagsRelationFieldModel::create(['users' => $users->pluck('id')->toArray()]);

    expect($model->users)->toHaveCount(3);

});
