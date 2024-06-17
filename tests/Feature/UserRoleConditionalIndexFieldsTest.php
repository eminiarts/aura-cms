<?php

use Aura\Base\ConditionalLogic;
use Aura\Base\Facades\Aura;
use Aura\Base\Models\Post;
use Aura\Base\Resource;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

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
            ],
            [
                'name' => 'Text 3',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'text3',
            ],
        ];
    }
}

test('super admin can view all headers', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    // Attach role to User
    $this->user->resource->update(['roles' => [$role->id]]);
    $this->user->refresh();
    $model = new UserRoleConditionalIndexFieldsModel();

    // Test getHeaders()
    $headers = $model->getHeaders();

    // Assert SuperAdmin sees all fields
    expect($headers)->toHaveCount(4);

    // Super Admin should see Text 1, Text 2, Text 3, and ID
    expect($headers)->toHaveKeys(['id', 'text1', 'text2', 'text3']);
});

test('admin can view his headers', function () {
    $model = new UserRoleConditionalIndexFieldsModel();

    // Assert Admin sees only Text 2 and ID
    $role = Role::create(['type' => 'Role', 'title' => 'Admin', 'slug' => 'admin', 'description' => 'Admin has can perform everything.', 'super_admin' => false, 'permissions' => []]);

    // Attach role to User
    $this->user->resource->update(['roles' => [$role->id]]);
    $this->user->refresh();

    // Test getHeaders()
    $headers = $model->getHeaders();

    // Admin sees 3 fields
    expect($headers)->toHaveCount(3);

    // Assert Admin does not see Text 1
    expect($headers)->not->toHaveKeys(['text1']);
});

test('user can view his headers', function () {
    $model = new UserRoleConditionalIndexFieldsModel();

    // Assert Moderator sees only Text 3 and ID
    $role = Role::create(['type' => 'Role', 'title' => 'Moderator', 'slug' => 'moderator', 'description' => 'Moderator has can perform everything.', 'super_admin' => false, 'permissions' => []]);

    // Attach role to User
    $this->user->resource->update(['roles' => [$role->id]]);

    // Test getHeaders()
    $headers = $model->getHeaders();

    // Moderator sees 2 fields
    expect($headers)->toHaveCount(2);

    // Assert Moderator does not see Text 1
    expect($headers)->not->toHaveKeys(['text1']);

    // Assert Moderator does not see Text 2
    expect($headers)->not->toHaveKeys(['text2']);
});

test('super admin can get all fields', function () {

    $user = createSuperAdmin();

    $this->actingAs($user);

    // Create a new Post
    $post = UserRoleConditionalIndexFieldsModel::create(['title' => 'Test Post', 'slug' => 'test-post', 'fields' => ['text1' => 'Text 1', 'text2' => 'Text 2', 'text3' => 'Text 3']]);

    $post->clearFieldsAttributeCache();

    // Assert Post is in DB
    expect($post->exists)->toBeTrue();

    // Assert Post Meta are saved in DB
    expect($post->meta()->count())->toBe(3);

    ConditionalLogic::clearConditionsCache();

    $post = (new UserRoleConditionalIndexFieldsModel)::find($post->id);

    $post->clearFieldsAttributeCache();
    // Super Admin should be able to call $post->text1, it should return 'Text 1'
    expect($post->text1)->toBe('Text 1');

    // Super Admin should be able to call $post->text2, it should return 'Text 2'
    expect($post->text2)->toBe('Text 2');

    // Super Admin should be able to call $post->text3, it should return 'Text 3'
    expect($post->text3)->toBe('Text 3');
});

test('admin can get all fields except text1', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'Admin', 'slug' => 'admin', 'name' => 'Admin', 'description' => 'Admin has can perform everything.', 'super_admin' => false, 'permissions' => []]);

    // Attach role to User
    $this->user->resource->update(['roles' => [$role->id]]);
    $this->user->refresh();

    // Create a new Post
    $post = UserRoleConditionalIndexFieldsModel::create(['title' => 'Test Post', 'slug' => 'test-post', 'fields' => ['text1' => 'Text 1', 'text2' => 'Text 2', 'text3' => 'Text 3']]);

    // Assert Post is in DB
    expect($post->exists)->toBeTrue();

    $post = $post->fresh();

    Aura::clearConditionsCache();
    // Assert Post Meta are saved in DB

    // dd($post->toArray(), $post->fields, $post->text1);
    expect($post->fields->count())->toBe(2);

    //  Admin should not be able to call $post->text1, it should return null
    expect($post->text1)->toBeNull();

    //  Admin should be able to call $post->text2, it should return 'Text 2'
    expect($post->text2)->toBe('Text 2');

    //  Admin should be able to call $post->text3, it should return 'Text 3'
    expect($post->text3)->toBe('Text 3');
});

test('user can get all fields except text1 and text2', function () {
    $role = Role::create(['type' => 'Role', 'title' => 'User', 'slug' => 'user', 'name' => 'User', 'description' => 'Simple User', 'super_admin' => false, 'permissions' => []]);

    // Attach role to User
    $this->user->resource->update(['roles' => [$role->id]]);

    // Create a new Post
    $post = UserRoleConditionalIndexFieldsModel::create(['title' => 'Test Post', 'slug' => 'test-post', 'fields' => ['text1' => 'Text 1', 'text2' => 'Text 2', 'text3' => 'Text 3']]);

    // Assert Post is in DB
    expect($post->exists)->toBeTrue();

    $post = $post->fresh();

    Aura::clearConditionsCache();

    expect($post->fields->count())->toBe(1);
    expect($post->text1)->toBeNull();
    expect($post->text2)->toBeNull();
    expect($post->text3)->toBe('Text 3');
});
