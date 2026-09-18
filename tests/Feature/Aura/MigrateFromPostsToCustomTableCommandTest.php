<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
    $this->resourceFile = app_path('Aura/Resources/MigratableThing.php');
});

afterEach(function () {
    if (File::exists($this->resourceFile)) {
        File::delete($this->resourceFile);
    }

    foreach (File::glob(database_path('migrations/*create_migratable_things_table*.php')) as $file) {
        File::delete($file);
    }
});

it('fails on an unknown resource class instead of prompting', function () {
    $this->artisan('aura:migrate-from-posts-to-custom-table', ['resource' => 'App\Aura\Resources\DoesNotExist'])
        ->assertExitCode(1);
});

it('migrates the resource passed as an argument', function () {
    $this->artisan('aura:resource', ['name' => 'MigratableThing'])->assertExitCode(0);

    require_once $this->resourceFile;

    $this->artisan('aura:migrate-from-posts-to-custom-table', [
        'resource' => 'App\Aura\Resources\MigratableThing',
    ])
        ->expectsConfirmation('Do you want to run the migration now?', 'no')
        ->expectsConfirmation('Do you want to transfer data from posts and meta tables?', 'no')
        ->assertExitCode(0);

    expect(File::get($this->resourceFile))
        ->toContain('public static $customTable = true;')
        ->toContain("protected \$table = 'migratable_things';");
});
