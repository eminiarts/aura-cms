<?php

use Aura\Base\AuraServiceProvider;
use Aura\Base\Facades\Aura;
use Aura\Base\Resource;
use Illuminate\Support\Facades\App;

use function Pest\Livewire\livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    // The beforeBootstrapping method is not being called because it's not in the correct place.
    // Move this code to a service provider or to the bootstrap/app.php file.
    // For testing purposes, you can use the following approach:

    //   $this->app->beforeBootstrapping(AuraServiceProvider::class, function () {
    //   });

});

class ResourceActionsTestModel extends Resource
{
    public array $actions = [
        'createMissingPermissions' => 'Create Missing Permissions',
        'delete' => 'Delete',
    ];

    public static ?string $slug = 'page';

    public static string $type = 'Page';

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

    $component = livewire('aura::resource-edit', [$model->id]);

    $component->assertSee('Create Missing Permissions');
    $component->assertSee('Delete');
    $component->assertSee('Actions');

    // expect to see "delete" and "createMissingPermissions" actions
    expect($component->actions)->toHaveCount(2);
});

class ResourceActionsTestModel2 extends Resource
{
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

    public static ?string $slug = 'page';

    public static string $type = 'Page';

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

    $component = livewire('aura::resource-edit', [$model->id]);

    $component->assertSee('Create Missing Permissions');
    $component->assertSee('Delete');
    $component->assertSee('Actions');
    $component->assertOk();

    // expect to see "delete" and "createMissingPermissions" actions
    expect($component->actions)->toHaveCount(2);

    // visit edit page
    $this->get(route('aura.'.$model::$slug.'.edit', [$model->id]))->assertOk();
});

class ResourceActionsTestModelWithNoActions extends Resource
{
    public array $actions = [];

    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [];
    }
}

test('resource with no actions shows empty actions list', function () {
    $model = ResourceActionsTestModelWithNoActions::create([
        'title' => 'Test No Actions',
        'slug' => 'test-no-actions',
    ]);

    Aura::fake();
    Aura::setModel($model);

    $component = livewire('aura::resource-edit', [$model->id]);

    $component->assertOk();

    expect($component->actions)->toHaveCount(0);
});

class ResourceActionsTestModelWithConditionalLogic extends Resource
{
    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [];
    }

    public function actions()
    {
        return [
            'visibleAction' => [
                'label' => 'Visible Action',
                'conditional_logic' => fn () => true,
            ],
            'hiddenAction' => [
                'label' => 'Hidden Action',
                'conditional_logic' => fn () => false,
            ],
        ];
    }
}

test('actions with conditional logic are filtered correctly', function () {
    $model = ResourceActionsTestModelWithConditionalLogic::create([
        'title' => 'Test Conditional',
        'slug' => 'test-conditional',
    ]);

    Aura::fake();
    Aura::setModel($model);

    $component = livewire('aura::resource-edit', [$model->id]);

    $component->assertOk();
    $component->assertSee('Visible Action');
    $component->assertDontSee('Hidden Action');

    // Only the visible action should be in the actions list
    expect($component->actions)->toHaveCount(1);
    expect(array_keys($component->actions))->toContain('visibleAction');
});
