<?php

use Aura\Base\Resource;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;

beforeEach(function () {
    config()->set('aura.teams', true);
    $this->actingAs($this->user = User::factory()->create());

    // Create a team for the user
    $team = Team::create([
        'name' => 'Test Team',
        'user_id' => $this->user->id,
    ]);

    // Update user's current team
    $this->user->update(['current_team_id' => $team->id]);
    $this->user->refresh();

    // Delete any existing roles and user_role relationships
    \DB::table('user_role')->where('team_id', $team->id)->delete();
    Role::where('team_id', $team->id)->delete();
});

class UserRoleConditionalIndexFieldsModel extends Resource
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
                'validation' => '',
                'conditional_logic' => [
                    [
                        'field' => 'role',
                        'operator' => '==',
                        'value' => 'admin',
                    ],
                ],
                'slug' => 'text2',
                'show_in_index' => false,
            ],
            [
                'name' => 'Text 3',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'text3',
                'show_in_index' => true,
            ],
        ];
    }
}

test('super admin can view all headers', function () {
    $role = Role::create([
        'name' => 'Super Admin',
        'slug' => 'super_admin',
        'description' => 'Super Admin has can perform everything.',
        'super_admin' => true,
        'permissions' => [],
        'user_id' => $this->user->id,
        'team_id' => $this->user->current_team_id,
    ]);

    // Attach role to User
    $this->user->update(['roles' => [$role->id]]);
    $this->user->refresh();
    $model = new UserRoleConditionalIndexFieldsModel;

    // Test getHeaders()
    $headers = $model->getHeaders();

    // Assert SuperAdmin sees all fields
    expect($headers)->toHaveCount(4);

    // Super Admin should see Text 1, Text 2, Text 3, and ID
    expect($headers)->toHaveKeys(['id', 'text1', 'text2', 'text3']);
});

test('admin can view his headers', function () {
    $model = new UserRoleConditionalIndexFieldsModel;

    // Assert Admin sees only Text 2 and ID
    $role = Role::create([
        'name' => 'Admin',
        'slug' => 'admin',
        'description' => 'Admin has can perform everything.',
        'super_admin' => false,
        'permissions' => [],
        'user_id' => $this->user->id,
        'team_id' => $this->user->current_team_id,
    ]);

    // Attach role to User
    $this->user->update(['roles' => [$role->id]]);
    $this->user->refresh();

    // Test getHeaders()
    $headers = $model->getHeaders();

    // Admin sees 3 fields
    expect($headers)->toHaveCount(3);

    // Assert Admin does not see Text 1
    expect($headers)->not->toHaveKeys(['text1']);
});

test('user can view his headers', function () {
    $model = new UserRoleConditionalIndexFieldsModel;

    // Assert Moderator sees only Text 3 and ID
    $role = Role::create([
        'name' => 'Moderator',
        'slug' => 'moderator',
        'description' => 'Moderator has can perform everything.',
        'super_admin' => false,
        'permissions' => [],
        'user_id' => $this->user->id,
        'team_id' => $this->user->current_team_id,
    ]);

    // Attach role to User
    $this->user->update(['roles' => [$role->id]]);
    $this->user->refresh();

    // Test getHeaders()
    $headers = $model->getHeaders();

    // Moderator sees only Text 3 and ID
    expect($headers)->toHaveCount(2);

    // Assert Moderator does not see Text 1 and Text 2
    expect($headers)->not->toHaveKey('text1');
    expect($headers)->not->toHaveKey('text2');
    expect($headers)->toHaveKey('text3');
    expect($headers)->toHaveKey('id');
});

test('super admin can get all fields', function () {
    $role = Role::create([
        'name' => 'Super Admin',
        'slug' => 'super_admin',
        'description' => 'Super Admin has can perform everything.',
        'super_admin' => true,
        'permissions' => [],
        'user_id' => $this->user->id,
        'team_id' => $this->user->current_team_id,
    ]);

    // Attach role to User
    $this->user->update(['roles' => [$role->id]]);
    $this->user->refresh();

    // Test getHeaders()
    $headers = (new UserRoleConditionalIndexFieldsModel)->getHeaders();

    // Assert field count (all fields should be visible)
    expect($headers)->toHaveCount(4);

    // Verify specific fields are present
    expect($headers)->toHaveKey('text1');
    expect($headers)->toHaveKey('text2');
    expect($headers)->toHaveKey('text3');
    expect($headers)->toHaveKey('id');
});

test('admin can get all fields except text1', function () {
    $role = Role::create([
        'name' => 'Admin',
        'slug' => 'admin',
        'description' => 'Admin has can perform everything.',
        'super_admin' => false,
        'permissions' => [],
        'user_id' => $this->user->id,
        'team_id' => $this->user->current_team_id,
    ]);

    // Attach role to User
    $this->user->update(['roles' => [$role->id]]);
    $this->user->refresh();

    // Test getHeaders()
    $headers = (new UserRoleConditionalIndexFieldsModel)->getHeaders();

    // Assert field count (text2 and text3 should be visible)
    expect($headers)->toHaveCount(3);

    // Verify specific fields
    expect($headers)->not->toHaveKey('text1');
    expect($headers)->toHaveKey('text2');
    expect($headers)->toHaveKey('text3');
    expect($headers)->toHaveKey('id');
});

test('user can get all fields except text1 and text2', function () {
    $role = Role::create([
        'name' => 'User',
        'slug' => 'user',
        'description' => 'Simple User',
        'super_admin' => false,
        'permissions' => [],
        'user_id' => $this->user->id,
        'team_id' => $this->user->current_team_id,
    ]);

    // Attach role to User
    $this->user->update(['roles' => [$role->id]]);
    $this->user->refresh();

    // Test getHeaders()
    $headers = (new UserRoleConditionalIndexFieldsModel)->getHeaders();

    // Assert field count (only text3 should be visible)
    expect($headers)->toHaveCount(2);

    // Verify specific fields
    expect($headers)->not->toHaveKey('text1');
    expect($headers)->not->toHaveKey('text2');
    expect($headers)->toHaveKey('text3');
    expect($headers)->toHaveKey('id');
});
