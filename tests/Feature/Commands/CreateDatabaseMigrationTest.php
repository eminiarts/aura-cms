<?php

use Aura\Base\Resource;
use Aura\Base\Events\SaveFields;
use Illuminate\Support\Facades\DB;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\Event;
use Aura\Base\Livewire\ResourceEditor;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Aura\Base\Listeners\CreateDatabaseMigration;



beforeEach(function () {
    // Storage::fake();

    // Filesystem::fake();
    // Set up any necessary test data or mocks
    // $this->filesystem = $this->mock(Filesystem::class);
    // $this->listener = new CreateDatabaseMigration($this->filesystem);
    // Artisan::partialMock();

    $this->user = createSuperAdmin();

    $this->actingAs($this->user);
});

class TestModel extends Resource
{
    protected $table = 'test_models';
    public static $customTable = true;
}

it('creates a migration when fields are added', function () {

    // Event::listen(SaveFields::class, CreateDatabaseMigration::class);
    // Create Resource
  
     Artisan::call('aura:resource', [
            'name' => 'Project',
            '--custom' => true,
    ]);

    $this->assertTrue(file_exists(app_path('Aura/Resources/Project.php')));

});
