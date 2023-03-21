<?php

use Eminiarts\Aura\Models\Post;

use Eminiarts\Aura\Facades\Aura;

use Eminiarts\Aura\Resources\User;

use function Pest\Livewire\livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = User::factory()->create());

    // Create Team and assign to user
    createSuperAdmin();

    // Refresh User
    $this->user = $this->user->refresh();

    // Login
    $this->actingAs($this->user);
});


class ResourceActionsTestModel extends Post
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public array $actions = [
      'createMissingPermissions' => 'Create Missing Permissions',
      'delete' => 'Delete',
    ];

    public static function getFields()
    {
        return [];
    }
}

test('simple single Actions work correctly', function () {
    $model = ResourceActionsTestModel::create([
        'title' => 'Test',
        'slug' => 'test',
    ]);

    Aura::fake();
    Aura::setModel($model);

    $component = livewire('aura::post-edit', [$model->type, $model->id]);

    $component->assertSee('Create Missing Permissions');
    $component->assertSee('Delete');
    $component->assertSee('Actions');

    // expect to see "delete" and "createMissingPermissions" actions
    expect($component->actions)->toHaveCount(2);
});

class ResourceActionsTestModel2 extends Post
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public array $actions = [
      'createMissingPermissions' => [
        'label' => 'Create Missing Permissions',
        'icon' => 'icon',
      ],
      'delete' => [
        'label' => 'Delete',
        'icon' => 'delete-icon',
      ],
    ];

    public static function getFields()
    {
        return [];
    }
}

test('actions with label and icon are displayed correctly', function () {
    $model = ResourceActionsTestModel2::create([
        'title' => 'Test',
        'slug' => 'test',
    ]);

    Aura::fake();
    Aura::setModel($model);

    $component = livewire('aura::post-edit', [$model->type, $model->id]);

    $component->assertSee('Create Missing Permissions');
    $component->assertSee('Delete');
    $component->assertSee('Actions');
    $component->assertOk();

    // expect to see "delete" and "createMissingPermissions" actions
    expect($component->actions)->toHaveCount(2);

    // visit edit page
    $this->get(route('aura.post.edit', [$model->type, $model->id]))->assertOk();
});
