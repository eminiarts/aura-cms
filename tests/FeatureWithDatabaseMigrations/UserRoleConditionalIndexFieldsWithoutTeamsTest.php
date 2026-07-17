<?php

use Aura\Base\Resource;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;

beforeEach(function () {
    config(['aura.teams' => false]);

    $this->artisan('migrate:fresh');

    $migration = require __DIR__.'/../../database/migrations/create_aura_tables.php.stub';
    $migration->up();

    $this->actingAs($this->user = User::factory()->create());
});

afterEach(function () {
    config(['aura.teams' => true]);
});

class ConditionalIndexFieldsWithoutTeamsModel extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'text1',
                'validation' => '',
                'conditional_logic' => [
                    [
                        'field' => 'role',
                        'operator' => '==',
                        'value' => 'super_admin',
                    ],
                ],
                'show_in_index' => false,
            ],
            [
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'text2',
                'validation' => '',
                'conditional_logic' => [
                    [
                        'field' => 'role',
                        'operator' => '==',
                        'value' => 'admin',
                    ],
                ],
                'show_in_index' => false,
            ],
            [
                'name' => 'Text 3',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'text3',
                'validation' => '',
                'conditional_logic' => [],
                'show_in_index' => true,
            ],
        ];
    }
}

test('role-conditioned index headers are respected when teams are disabled', function () {
    expect(config('aura.teams'))->toBeFalse();
    expect(Schema::hasColumn('roles', 'team_id'))->toBeFalse();

    $role = Role::create([
        'name' => 'Admin',
        'slug' => 'admin',
        'description' => 'Admin can perform everything.',
        'super_admin' => true,
        'permissions' => [],
        'user_id' => $this->user->id,
    ]);

    $this->user->roles()->attach($role->id);
    $this->user->refresh();

    $headers = (new ConditionalIndexFieldsWithoutTeamsModel)->getHeaders();

    // A super admin sees every field plus the id column.
    expect($headers)->toHaveKeys(['id', 'text1', 'text2', 'text3']);
});

test('a non-super role only sees its conditioned headers when teams are disabled', function () {
    $role = Role::create([
        'name' => 'Moderator',
        'slug' => 'moderator',
        'description' => 'Moderator role.',
        'super_admin' => false,
        'permissions' => [],
        'user_id' => $this->user->id,
    ]);

    $this->user->roles()->attach($role->id);
    $this->user->refresh();

    $headers = (new ConditionalIndexFieldsWithoutTeamsModel)->getHeaders();

    // Neither the super_admin- nor the admin-conditioned column is visible.
    expect($headers)->not->toHaveKey('text1');
    expect($headers)->not->toHaveKey('text2');
    expect($headers)->toHaveKey('text3');
    expect($headers)->toHaveKey('id');
});
