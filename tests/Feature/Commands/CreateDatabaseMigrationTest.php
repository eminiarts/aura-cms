<?php

use Aura\Base\Events\SaveFields;
use Aura\Base\Facades\Aura;
use Aura\Base\Listeners\CreateDatabaseMigration;
use Aura\Base\Listeners\ModifyDatabaseMigration;
use Aura\Base\Livewire\ResourceEditor;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    $this->user = createSuperAdmin();

    $this->actingAs($this->user);

    // Cache clear
    Artisan::call('cache:clear');

    Artisan::call('aura:resource', [
        'name' => 'Project',
        '--custom' => true,
    ]);

    config(['aura.features.resource_editor' => true]);

    app('aura')::registerResources(['App\Aura\Resources\Project']);

    Artisan::call('cache:clear');

    require_once app_path('Aura/Resources/Project.php');
});

afterEach(function () {

    if (File::exists(app_path('Aura/Resources/Project.php'))) {
        File::delete(app_path('Aura/Resources/Project.php'));
    }

    $migrationFiles = File::files(database_path('migrations'));

    foreach ($migrationFiles as $file) {
        File::delete($file);
    }

    Aura::clear();

});

it('creates a custom resource', function () {
    $this->assertTrue(file_exists(app_path('Aura/Resources/Project.php')));
});

it('creates a custom resource with custom table', function () {

    $this->assertStringContainsString('public static $customTable = true;', file_get_contents(app_path('Aura/Resources/Project.php')));
    $this->assertStringContainsString('protected $table = \'projects\';', file_get_contents(app_path('Aura/Resources/Project.php')));
});

it('resource editor is accessible', function () {

    expect(config('aura.features.resource_editor'))->toBeTrue();

    $classExists = class_exists('App\Aura\Resources\Project');

    expect($classExists)->toBeTrue();

    Aura::registerRoutes('project');

    Aura::clearRoutes();

    $this->get(route('aura.resource.editor', ['slug' => 'Project']))
        ->assertStatus(200)
        ->assertSeeLivewire('aura::resource-editor');
});

it('saveFields listens for ModifyDatabaseMigration', function () {
    config(['aura.features.custom_tables_for_resources' => 'single']);

    Event::fake();

    Livewire::test(ResourceEditor::class, ['slug' => 'Project'])
        ->call('saveNewField', [
            'type' => "Aura\Base\Fields\Text",
            'slug' => 'description',
            'name' => 'Description',
            'on_index' => true,
            'on_forms' => true,
            'on_view' => true,
            'searchable' => false,
            'validation' => '',
            'conditional_logic' => '',
        ], 0, 'Project')
        ->assertDispatched('newFields')
        ->assertDispatched('finishedSavingFields');

    // Manually re-register the event listeners based on the updated configuration
    $appServiceProvider = new \Aura\Base\Providers\AppServiceProvider(app());
    $appServiceProvider->boot();

    Event::assertDispatched(SaveFields::class);

    // $this->assertTrue(file_exists(database_path('migrations/2021_09_01_000000_create_custom_projects_table.php')));

    Event::assertListening(SaveFields::class, ModifyDatabaseMigration::class);

});

it('saveFields listens for CreateDatabaseMigration', function () {
    config(['aura.features.custom_tables_for_resources' => 'multiple']);

    Event::fake();

    Event::forget(SaveFields::class);

    // Manually re-register the event listeners based on the updated configuration
    $appServiceProvider = new \Aura\Base\Providers\AppServiceProvider(app());
    $appServiceProvider->boot();

    Livewire::test(ResourceEditor::class, ['slug' => 'Project'])
        ->call('saveNewField', [
            'type' => "Aura\Base\Fields\Text",
            'slug' => 'description',
            'name' => 'Description',
            'on_index' => true,
            'on_forms' => true,
            'on_view' => true,
            'searchable' => false,
            'validation' => '',
            'conditional_logic' => '',
        ], 0, 'Project')
        ->assertDispatched('newFields')
        ->assertDispatched('finishedSavingFields');

    Event::assertDispatched(SaveFields::class);
    Event::assertDispatchedTimes(SaveFields::class, 1);

    Event::assertListening(SaveFields::class, CreateDatabaseMigration::class);
});

it('creates a migration when fields are added', function () {

    config(['aura.features.custom_tables_for_resources' => 'single']);

    // Manually re-register the event listeners based on the updated configuration
    $appServiceProvider = new \Aura\Base\Providers\AppServiceProvider(app());
    $appServiceProvider->boot();

    Livewire::test(ResourceEditor::class, ['slug' => 'Project'])
        ->call('saveNewField', [
            'type' => "Aura\Base\Fields\Text",
            'slug' => 'description',
            'name' => 'Description',
            'on_index' => true,
            'on_forms' => true,
            'on_view' => true,
            'searchable' => false,
            'validation' => '',
            'conditional_logic' => '',
        ], 0, 'Project')
        ->assertDispatched('newFields')
        ->assertDispatched('finishedSavingFields');

    $this->assertTrue(Schema::hasTable('projects'), 'The projects table does not exist.');
    $this->assertTrue(Schema::hasColumn('projects', 'description'), 'The description column does not exist in the projects table.');

});
