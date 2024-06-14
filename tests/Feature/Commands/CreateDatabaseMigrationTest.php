<?php

use Aura\Base\Events\SaveFields;
use Aura\Base\Listeners\CreateDatabaseMigration;
use Aura\Base\Resource;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\File;

beforeEach(function () {
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
    File::delete(app_path('Aura/Resources/Project.php'));
});

it('creates a custom resource', function () {
    $this->assertTrue(file_exists(app_path('Aura/Resources/Project.php')));
});

it('resource editor is accessible', function () {

    expect(config('aura.resource_editor.enabled'))->toBeTrue();

    $classExists = class_exists('App\Aura\Resources\Project');

    expect($classExists)->toBeTrue();

    $this->get(route('aura.resource.editor', ['slug' => 'Project']))
        ->assertStatus(200)
        ->assertSeeLivewire('aura::resource-editor');
});

it('creates a migration when fields are added', function () {

    // Event::listen(SaveFields::class, CreateDatabaseMigration::class);
    // Create Resource

});
