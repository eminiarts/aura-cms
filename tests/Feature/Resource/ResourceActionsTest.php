<?php

use Aura\Base\Resource;
use Aura\Base\Facades\Aura;
use Aura\Base\AuraServiceProvider;

use Illuminate\Support\Facades\App;
use function Pest\Livewire\livewire;


// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

      // The beforeBootstrapping method is not being called because it's not in the correct place.
      // Move this code to a service provider or to the bootstrap/app.php file.
      // For testing purposes, you can use the following approach:
      
    //   $this->app->beforeBootstrapping(AuraServiceProvider::class, function () {
    //       ray('before bootstrapping aura service provider')->green();
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

    $component = livewire('aura::post-edit', [$model->type, $model->id]);

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

    ray('test', $model->getSlug(), $model->id)->red();

    $component = livewire('aura::post-edit', [$model->getSlug(), $model->id]);

    $component->assertSee('Create Missing Permissions');
    $component->assertSee('Delete');
    $component->assertSee('Actions');
    $component->assertOk();

    // expect to see "delete" and "createMissingPermissions" actions
    expect($component->actions)->toHaveCount(2);

    // dd('he',  $model::$slug);

    // visit edit page
    $this->get(route('aura.' . $model::$slug . '.edit', [$model->id]))->assertOk();
});
