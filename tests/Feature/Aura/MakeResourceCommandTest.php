<?php

// uses()->group('current');

use Aura\Base\Resources\User;
use Illuminate\Filesystem\FilesystemServiceProvider;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);
// uses(FilesystemServiceProvider::class);

beforeEach(function () {
    $this->actingAs(User::factory()->create());
});

it('generates the correct files for a resource', function () {
    Storage::fake('app');

    Artisan::call('aura:resource', [
        'name' => 'TestResource',
    ]);

    // assert app/Aura/Resources/TestResource.php exists
    $this->assertTrue(File::exists(app_path('Aura/Resources/TestResource.php')));

    // delete app/Aura/Resources/TestResource.php
    File::delete(app_path('Aura/Resources/TestResource.php'));
});

test('MakeResource command generates resource class and test', function () {
    $resourceName = 'Resource';

    // Run the command and provide input
    $this->artisan('aura:resource '.$resourceName)->assertExitCode(0);

    // Assert that the resource class file was generated
    expect(File::exists(app_path('Aura/Resources/'.ucfirst($resourceName).'.php')))->toBeTrue();

    // Check file contents
    $resourceClass = File::get(app_path('Aura/Resources/'.ucfirst($resourceName).'.php'));

    expect($resourceClass)->toContain("namespace App\Aura\Resources;");
    expect($resourceClass)->toContain('public static string $type = \'Resource\';');
    expect($resourceClass)->toContain('public static ?string $slug = \'resource\';');
    expect($resourceClass)->toContain('public function getIcon()');
    expect($resourceClass)->toContain('public static function getFields()');

    // Delete the resource class file
    if (File::exists(app_path('Aura/Resources/'.ucfirst($resourceName).'.php'))) {
        File::delete(app_path('Aura/Resources/'.ucfirst($resourceName).'.php'));
    }
});
