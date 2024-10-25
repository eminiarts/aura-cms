<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\ResourceEditor;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;

beforeEach(function () {
    if (File::exists(app_path('Aura/Resources/Project.php'))) {
        File::delete(app_path('Aura/Resources/Project.php'));
    }

    $migrationFiles = File::files(database_path('migrations'));

    foreach ($migrationFiles as $file) {
        File::delete($file);
    }

    Aura::clear();

    $this->user = createSuperAdmin();

    $this->actingAs($this->user);

    Artisan::call('aura:resource', [
        'name' => 'Project',
        '--custom' => true,
    ]);

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

it('creates a migration when fields are added', function () {

    config(['aura.resource_editor.custom_table_migrations' => 'single']);

    // Manually re-register the event listeners based on the updated configuration
    $appServiceProvider = new \Aura\Base\Providers\AppServiceProvider(app());
    $appServiceProvider->boot();

    // Aura::fake();
    // Aura::setModel(new Project);

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
        ], 0, 'test') // Provide the $index and $slug here
        ->assertDispatched('newFields')
        ->assertDispatched('finishedSavingFields');

    $this->assertTrue(Schema::hasTable('projects'));
    $this->assertTrue(Schema::hasColumn('projects', 'description'));

    Livewire::test(ResourceEditor::class, ['slug' => 'Project'])
        ->call('saveNewField', [
            'type' => "Aura\Base\Fields\Text",
            'slug' => 'third',
            'name' => 'third',
            'on_index' => true,
            'on_forms' => true,
            'on_view' => true,
            'searchable' => false,
            'validation' => '',
            'conditional_logic' => '',
        ], 1, 'test') // Provide the $index and $slug here
        ->assertDispatched('newFields')
        ->assertDispatched('finishedSavingFields');

    $this->assertTrue(Schema::hasTable('projects'));
    $this->assertTrue(Schema::hasColumn('projects', 'third'));

    $allMigrations = File::files(database_path('migrations'));

    expect(count($allMigrations))->toBe(1);

    Aura::clear();
});
