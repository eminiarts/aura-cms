<?php

use Eminiarts\Aura;
use Eminiarts\Aura\Pipeline\AddIdsToFields;
use Eminiarts\Aura\Pipeline\ApplyParentConditionalLogic;
use Eminiarts\Aura\Pipeline\ApplyTabs;
use Eminiarts\Aura\Pipeline\MapFields;
use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

class ParentConditionalLogicModel extends Post
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'global' => true,
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'tab-1',
                'conditional_logic' => [
                    [
                        'field' => 'role',
                        'operator' => '==',
                        'value' => 'super_admin',
                    ],
                ],
            ],
            [
                'label' => 'Text 1',
                'name' => 'Text 1',
                'type' => 'App\\Aura\\Fields\\Text',
                'slug' => 'text1',
                'validation' => '',
                'conditional_logic' => [
                    [
                        'field' => 'role',
                        'operator' => '==',
                        'value' => 'admin',
                    ],
                ],
            ],
            [
                'label' => 'Text 2',
                'name' => 'Text 2',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                    [
                        'field' => 'text1',
                        'operator' => '==',
                        'value' => 'test',
                    ],
                ],
                'slug' => 'text2',
            ],
        ];
    }
}

test('fields merge parent conditional logic', function () {
    $model = new ParentConditionalLogicModel();

    $fields = $model->sendThroughPipeline($model->fieldsCollection(), [
        ApplyTabs::class,
        MapFields::class,
        AddIdsToFields::class,
        ApplyParentConditionalLogic::class,
    ]);

    // Assert field with a Slug of 'tab-1' has only one item in 'conditional_logic'
    $this->assertCount(1, $fields->firstWhere('slug', 'tab-1')['conditional_logic']);

    // Assert field with a Slug of 'text1' has two items in 'conditional_logic'
    $this->assertCount(2, $fields->firstWhere('slug', 'text1')['conditional_logic']);

    // Assert field with a Slug of 'text2' has two items in 'conditional_logic'
    $this->assertCount(2, $fields->firstWhere('slug', 'text2')['conditional_logic']);

    // Assert 'conditional_logic' of field with a Slug of 'text2' has the correct values
    $this->assertEquals([
        [
            'field' => 'role',
            'operator' => '==',
            'value' => 'super_admin',
        ],
        [
            'field' => 'text1',
            'operator' => '==',
            'value' => 'test',
        ],
    ], $fields->firstWhere('slug', 'text2')['conditional_logic']);

    // Assert 'conditional_logic' of field with a Slug of 'text1' has the correct values
    $this->assertEquals([
        [
            'field' => 'role',
            'operator' => '==',
            'value' => 'super_admin',
        ],
        [
            'field' => 'role',
            'operator' => '==',
            'value' => 'admin',
        ],
    ], $fields->firstWhere('slug', 'text1')['conditional_logic']);

    // Assert 'conditional_logic' of field with a Slug of 'tab-1' has the correct values
    $this->assertEquals([
        [
            'field' => 'role',
            'operator' => '==',
            'value' => 'super_admin',
        ],
    ], $fields->firstWhere('slug', 'tab-1')['conditional_logic']);
});

// More advanced example

class AdvancedParentConditionalLogicModel extends Post
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'global' => true,
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'tab-1',
                'conditional_logic' => [
                    [
                        'field' => 'role',
                        'operator' => '==',
                        'value' => 'super_admin',
                    ],
                ],
            ],
            [
                'name' => 'Text 1',
                'type' => 'App\\Aura\\Fields\\Text',
                'slug' => 'text1',
                'validation' => '',
                'conditional_logic' => [
                    [
                        'field' => 'role',
                        'operator' => '==',
                        'value' => 'admin',
                    ],
                ],
            ],
            [
                'name' => 'Text 2',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                    [
                        'field' => 'text1',
                        'operator' => '==',
                        'value' => 'test',
                    ],
                ],
                'slug' => 'text2',
            ],
            [
                'name' => 'Tab 2',
                'global' => true,
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'tab-2',
                'conditional_logic' => [
                    [
                        'field' => 'role',
                        'operator' => '==',
                        'value' => 'admin',
                    ],
                ],
            ],
            [
                'name' => 'Panel 1',
                'type' => 'App\\Aura\\Fields\\Panel',
                'slug' => 'panel-1',
                'conditional_logic' => [
                    [
                        'field' => 'role',
                        'operator' => '==',
                        'value' => 'admin',
                    ],
                ],
            ],
            [
                'name' => 'Text 3',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                    [
                        'field' => 'user',
                        'operator' => '==',
                        'value' => '1',
                    ],
                ],
                'slug' => 'text3',
            ],

        ];
    }
}

test('fields merge parent conditional logic - more advanced Example', function () {
    $model = new AdvancedParentConditionalLogicModel();

    $fields = $model->sendThroughPipeline($model->fieldsCollection(), [
        ApplyTabs::class,
        MapFields::class,
        AddIdsToFields::class,
        ApplyParentConditionalLogic::class,
    ]);

    // Assert field with a Slug of 'tab-1' has only one item in 'conditional_logic'
    $this->assertCount(1, $fields->firstWhere('slug', 'tab-1')['conditional_logic']);

    // Assert field with a Slug of 'text1' has two items in 'conditional_logic'
    $this->assertCount(2, $fields->firstWhere('slug', 'text1')['conditional_logic']);

    // Assert field with a Slug of 'text2' has two items in 'conditional_logic'
    $this->assertCount(2, $fields->firstWhere('slug', 'text2')['conditional_logic']);

    // Assert field with a Slug of 'text3' has two items in 'conditional_logic'
    $this->assertCount(3, $fields->firstWhere('slug', 'text3')['conditional_logic']);

    // Assert field with a Slug of 'panel-1' has two items in 'conditional_logic'
    $this->assertCount(2, $fields->firstWhere('slug', 'panel-1')['conditional_logic']);

    // Assert 'conditional_logic' of field with a Slug of 'panel-1' has the correct values
    $this->assertEquals([
        [
            'field' => 'role',
            'operator' => '==',
            'value' => 'admin',
        ],
        [
            'field' => 'role',
            'operator' => '==',
            'value' => 'admin',
        ],
    ], $fields->firstWhere('slug', 'panel-1')['conditional_logic']);

    // Assert 'conditional_logic' of field with a Slug of 'text3' has the correct values
    $this->assertEquals([
        [
            'field' => 'role',
            'operator' => '==',
            'value' => 'admin',
        ],
        [
            'field' => 'role',
            'operator' => '==',
            'value' => 'admin',
        ],
        [
            'field' => 'user',
            'operator' => '==',
            'value' => '1',
        ],
    ], $fields->firstWhere('slug', 'text3')['conditional_logic']);
});

test('role condition as a Super Admin', function () {
    createSuperAdmin();

    $model = new AdvancedParentConditionalLogicModel();

    $fields = $model->sendThroughPipeline($model->fieldsCollection(), [
        ApplyTabs::class,
        MapFields::class,
        AddIdsToFields::class,
        ApplyParentConditionalLogic::class,
    ]);

    // dd($this->user->resource->isSuperAdmin());
    $superAdmin = $this->user;

    // Create a normal user
    $user2 = User::factory()->create();

    // act as $user2
    $this->actingAs($user2);

    // As a normal User, "Tab 1" should not be visible
    $this->assertFalse(Aura::checkCondition($model, $fields->firstWhere('slug', 'tab-1')));

    // Attach Super Admin Role to User
    // $user->resource()->update(['fields' => ['roles' => [$r->id]]]);

    // act as $this->user
    $this->actingAs($superAdmin);

    // As a Super Admin, "Tab 1" should be visible
    $this->assertTrue(Aura::checkCondition($model, $fields->firstWhere('slug', 'tab-1')));

    // As a Super Admin, "text1" should be visible too (even though role is admin)
    $this->assertTrue(Aura::checkCondition($model, $fields->firstWhere('slug', 'text1')));
});

test('role condition as a Admin', function () {
    $model = new AdvancedParentConditionalLogicModel();

    $fields = $model->sendThroughPipeline($model->fieldsCollection(), [
        ApplyTabs::class,
        MapFields::class,
        AddIdsToFields::class,
        ApplyParentConditionalLogic::class,
    ]);

    $role = Role::create(['type' => 'Role', 'title' => 'Super Admin', 'slug' => 'admin', 'name' => 'Super Admin', 'description' => 'Super Admin has can perform everything.', 'admin' => false, 'permissions' => []]);

    // assert there is a role in the db
    $this->assertDatabaseHas('posts', ['type' => 'Role', 'id' => $role->id]);

    $user = User::factory()->create();

    // As an Admin, "Tab 1" should be visible
    // $this->assertFalse(Aura::checkCondition($model, $fields->firstWhere('slug', 'tab-1')));

    // Attach Admin Role to User
    $user->resource->update(['fields' => ['roles' => [$role->id]]]);

    $user->refresh();

    $this->actingAs($user);

    // As an Admin, "Tab 1" should not be visible
    $this->assertFalse(Aura::checkCondition($model, $fields->firstWhere('slug', 'tab-1')));

    // As an Admin, "text1" should not be visible too because of tab 1
    $this->assertFalse(Aura::checkCondition($model, $fields->firstWhere('slug', 'text1')));

    // 'tab-2' should be visible
    $this->assertTrue(Aura::checkCondition($model, $fields->firstWhere('slug', 'tab-2')));

    // 'panel-1' should not be visible
    $this->assertTrue(Aura::checkCondition($model, $fields->firstWhere('slug', 'panel-1')));
});

test('role condition as a User', function () {
    $model = new AdvancedParentConditionalLogicModel();

    $fields = $model->sendThroughPipeline($model->fieldsCollection(), [
        ApplyTabs::class,
        MapFields::class,
        AddIdsToFields::class,
        ApplyParentConditionalLogic::class,
    ]);

    $this->actingAs($user = User::factory()->create());

    // As a User, fields with roles should not be visible
    $this->assertFalse(Aura::checkCondition($model, $fields->firstWhere('slug', 'tab-1')));
    $this->assertFalse(Aura::checkCondition($model, $fields->firstWhere('slug', 'text1')));
    $this->assertFalse(Aura::checkCondition($model, $fields->firstWhere('slug', 'tab-2')));
    $this->assertFalse(Aura::checkCondition($model, $fields->firstWhere('slug', 'panel-1')));
});
