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

it('generates the correct files for a taxonomy', function () {
    Storage::fake('app');

    Artisan::call('aura:taxonomy', [
        'name' => 'Length',
    ]);

    // assert app/Aura/Taxonomies/Length.php exists
    $this->assertTrue(File::exists(app_path('Aura/Taxonomies/Length.php')));

    // delete app/Aura/Taxonomies/Length.php
    File::delete(app_path('Aura/Taxonomies/Length.php'));
});

test('MakeTaxonomy command generates taxonomy class and test', function () {
    $tayonomyName = 'TestTaxonomy';

    // Run the command and provide input
    $this->artisan('aura:taxonomy '.$tayonomyName)->assertExitCode(0);

    // Assert that the posttype class file was generated
    expect(File::exists(app_path('Aura/Taxonomies/'.ucfirst($tayonomyName).'.php')))->toBeTrue();

    // Check file contents
    $posttypeClass = File::get(app_path('Aura/Taxonomies/'.ucfirst($tayonomyName).'.php'));

    expect($posttypeClass)->toContain("namespace App\Aura\Taxonomies;");
    expect($posttypeClass)->toContain('public static $hierarchical');
    expect($posttypeClass)->toContain('use Eminiarts\Aura\Taxonomies\Taxonomy;');
    expect($posttypeClass)->toContain('extends Taxonomy');
    expect($posttypeClass)->toContain('public static string $type');
    expect($posttypeClass)->toContain('public function getIcon()');
    expect($posttypeClass)->toContain('public static ?string $slug');

    // Delete the posttype class file
    if (File::exists(app_path('Aura/Taxonomies/'.ucfirst($tayonomyName).'.php'))) {
        File::delete(app_path('Aura/Taxonomies/'.ucfirst($tayonomyName).'.php'));
    }
});
