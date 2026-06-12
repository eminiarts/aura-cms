<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resource;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
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

    public static function getFields()
    {
        return [];
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

class ResourceActionsTestModelWithConfirmation extends Resource
{
    public array $actions = [
        'dangerousAction' => [
            'label' => 'Dangerous Action',
            'confirm' => true,
            'confirm-title' => 'Are you sure?',
            'confirm-content' => 'This action cannot be undone.',
            'confirm-button' => 'Yes, do it',
            'confirm-button-class' => 'bg-red-600',
        ],
    ];

    public static ?string $slug = 'page';

    public static string $type = 'Page';

    public static function getFields()
    {
        return [];
    }
}

test('actions with confirmation settings are available', function () {
    $model = ResourceActionsTestModelWithConfirmation::create([
        'title' => 'Test Confirmation',
        'slug' => 'test-confirmation',
    ]);

    Aura::fake();
    Aura::setModel($model);

    $component = livewire('aura::resource-edit', [$model->id]);

    $component->assertOk();
    $component->assertSee('Dangerous Action');

    expect($component->actions)->toHaveCount(1);
    expect($component->actions['dangerousAction']['confirm'])->toBeTrue();
    expect($component->actions['dangerousAction']['confirm-title'])->toBe('Are you sure?');
});

test('resource getActions returns actions array', function () {
    $model = new ResourceActionsTestModel;

    // getActions() returns the actions property when no actions() method exists
    expect($model->getActions())->toBeArray();
    expect($model->getActions())->toHaveCount(2);
});

test('resource getActions returns actions from method when defined', function () {
    $model = ResourceActionsTestModelWithConditionalLogic::create([
        'title' => 'Test Filter',
        'slug' => 'test-filter',
    ]);

    // When actions() method is defined, getActions() uses it
    $actions = $model->getActions();

    expect($actions)->toBeArray();
    // Actions method returns all actions, conditional logic filtering happens in Livewire component
    expect($actions)->toHaveCount(2);
});
