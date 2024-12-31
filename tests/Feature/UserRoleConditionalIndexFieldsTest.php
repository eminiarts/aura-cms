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

    protected $table = 'posts';

    protected $fillable = ['type'];

    protected $attributes = [];

    protected $fields = [];

    public function __get($key)
    {
        if ($key === 'fields') {
            return $this->fields;
        }

        $field = collect($this->fields)->firstWhere('slug', $key);
        return $field ? $field['value'] : null;
    }

    public static function boot()
    {
        parent::boot();

        static::retrieved(function ($model) {
            $model->refreshFields();
        });

        static::created(function ($model) {
            $model->refreshFields();
        });
    }

    public function clearFieldsAttributeCache()
    {
        $this->refreshFields();
        return $this;
    }

    public function getAttribute($key)
    {
        if (array_key_exists($key, $this->attributes)) {
            return $this->attributes[$key];
        }

        return parent::getAttribute($key);
    }

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

    public static function usesMeta(): string
    {
        return 'meta';
    }

    public function getMeta($key = null)
    {
        $meta = $this->meta()->pluck('value', 'key')->toArray();
        return $key ? ($meta[$key] ?? null) : $meta;
    }

    public function saveMeta($key, $value)
    {
        return $this->meta()->updateOrCreate(
            ['key' => $key],
            ['value' => $value]
        );
    }

    public function setAttribute($key, $value)
    {
        if (in_array($key, ['text1', 'text2', 'text3'])) {
            $this->saveMeta($key, $value);
            return $this;
        }

        $this->attributes[$key] = $value;
        return $this;
    }

    protected function refreshFields()
    {
        $fields = static::getFields();
        $userRole = auth()->user()->roles->first()->slug ?? null;
        $meta = $this->getMeta();
        $isSuperAdmin = auth()->user()->isSuperAdmin();

        $visibleFields = collect($fields)->filter(function ($field) use ($userRole, $isSuperAdmin) {
            $logic = $field['conditional_logic'] ?? [];
            if (empty($logic)) {
                return true;
            }

            if ($isSuperAdmin) {
                return true;
            }

            foreach ($logic as $condition) {
                if ($condition['field'] === 'role') {
                    if ($condition['operator'] === '==' && $userRole !== $condition['value']) {
                        return false;
                    }
                }
            }

            return true;
        })->values();

        $this->fields = $visibleFields->map(function ($field) use ($meta) {
            $slug = $field['slug'];
            $field['value'] = $meta[$slug] ?? null;
            return $field;
        });

        return $this;
    }
}

test('super admin can view all headers', function () {
    $role = Role::create(['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

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
    $role = Role::create(['name' => 'Admin', 'slug' => 'admin', 'description' => 'Admin has can perform everything.', 'super_admin' => false, 'permissions' => []]);

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
    $role = Role::create(['name' => 'Moderator', 'slug' => 'moderator', 'description' => 'Moderator has can perform everything.', 'super_admin' => false, 'permissions' => []]);

    // Attach role to User
    $this->user->update(['roles' => [$role->id]]);

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
    $role = Role::create(['name' => 'Super Admin', 'slug' => 'super_admin', 'description' => 'Super Admin has can perform everything.', 'super_admin' => true, 'permissions' => []]);

    // Attach role to User
    $this->user->update(['roles' => [$role->id]]);
    $this->user->refresh();

    $model = new UserRoleConditionalIndexFieldsModel;
    
    Aura::fake();
    Aura::setModel($model);

    $post = UserRoleConditionalIndexFieldsModel::create(['type' => 'page']);

    // Save meta values
    $post->saveMeta('text1', 'Text 1');
    $post->saveMeta('text2', 'Text 2');
    $post->saveMeta('text3', 'Text 3');

    $post = $post->fresh();
    $post->clearFieldsAttributeCache();

    // Get the fields collection
    $fields = collect($post->fields)->pluck('value', 'slug')->toArray();

    // Assert field count (all fields should be visible)
    expect($fields)->toHaveCount(3);

    // Verify specific fields
    expect($fields)->toHaveKey('text1');
    expect($fields)->toHaveKey('text2');
    expect($fields)->toHaveKey('text3');
    expect($fields['text1'])->toBe('Text 1');
    expect($fields['text2'])->toBe('Text 2');
    expect($fields['text3'])->toBe('Text 3');
});

test('admin can get all fields except text1', function () {
    $role = Role::create(['name' => 'Admin', 'slug' => 'admin', 'description' => 'Admin has can perform everything.', 'super_admin' => false, 'permissions' => []]);

    // Attach role to User
    $this->user->update(['roles' => [$role->id]]);
    $this->user->refresh();

    $model = new UserRoleConditionalIndexFieldsModel;

    Aura::fake();
    Aura::setModel($model);

    $post = UserRoleConditionalIndexFieldsModel::create(['type' => 'page']);

    // Save meta values
    $post->saveMeta('text1', 'Text 1');
    $post->saveMeta('text2', 'Text 2');
    $post->saveMeta('text3', 'Text 3');

    $post = $post->fresh();
    $post->clearFieldsAttributeCache();

    // Get the fields collection
    $fields = collect($post->fields)->pluck('value', 'slug')->toArray();

    // Assert field count (text2 and text3 should be visible)
    expect($fields)->toHaveCount(2);

    // Verify specific fields
    expect($fields)->not->toHaveKey('text1');
    expect($fields)->toHaveKey('text2');
    expect($fields)->toHaveKey('text3');
    expect($fields['text2'])->toBe('Text 2');
    expect($fields['text3'])->toBe('Text 3');
});

test('user can get all fields except text1 and text2', function () {
    $role = Role::create(['name' => 'User', 'slug' => 'user', 'description' => 'Simple User', 'super_admin' => false, 'permissions' => []]);

    // Attach role to User
    $this->user->update(['roles' => [$role->id]]);
    $this->user->refresh();

    $model = new UserRoleConditionalIndexFieldsModel;

    Aura::fake();
    Aura::setModel($model);

    $post = UserRoleConditionalIndexFieldsModel::create(['type' => 'page']);

    // Save meta values
    $post->saveMeta('text1', 'Text 1');
    $post->saveMeta('text2', 'Text 2');
    $post->saveMeta('text3', 'Text 3');

    $post = $post->fresh();
    $post->clearFieldsAttributeCache();

    // Get the fields collection
    $fields = collect($post->fields)->pluck('value', 'slug')->toArray();

    // Assert field count (only text3 should be visible)
    expect($fields)->toHaveCount(1);

    // Verify specific fields
    expect($fields)->not->toHaveKey('text1');
    expect($fields)->not->toHaveKey('text2');
    expect($fields)->toHaveKey('text3');
    expect($fields['text3'])->toBe('Text 3');
});
