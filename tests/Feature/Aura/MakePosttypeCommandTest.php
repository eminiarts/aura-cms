<?php

// uses()->group('current');

use Eminiarts\Aura\Resources\User;
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

it('generates the correct files for a posttype', function () {
    Storage::fake('app');

    Artisan::call('aura:posttype', [
        'name' => 'TestPosttype',
    ]);

    // assert app/Aura/Resources/TestPosttype.php exists
    $this->assertTrue(File::exists(app_path('Aura/Resources/TestPosttype.php')));

    // delete app/Aura/Resources/TestPosttype.php
    File::delete(app_path('Aura/Resources/TestPosttype.php'));
});

test('MakePosttype command generates posttype class and test', function () {
    $posttypeName = 'Posttype';
    
    // Run the command and provide input
    $this->artisan('aura:posttype ' . $posttypeName)->assertExitCode(0);

    // Assert that the posttype class file was generated
    expect(File::exists(app_path('Aura/Resources/' . ucfirst($posttypeName) . '.php')))->toBeTrue();

    // Check file contents
    $posttypeClass = File::get(app_path('Aura/Resources/' . ucfirst($posttypeName) . '.php'));

    expect($posttypeClass)->toContain("namespace App\Aura\Resources;");
    expect($posttypeClass)->toContain('public static string $type = \'Posttype\';');
    expect($posttypeClass)->toContain('public static ?string $slug = \'posttype\';');
    expect($posttypeClass)->toContain("public function getIcon()");
    expect($posttypeClass)->toContain("public static function getFields()");

    // Delete the posttype class file
    if (File::exists(app_path('Aura/Resources/' . ucfirst($posttypeName) . '.php'))) {
        File::delete(app_path('Aura/Resources/' . ucfirst($posttypeName) . '.php'));
    }
});