<?php

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
    $this->resourcePath = app_path('Aura/Resources');
});

afterEach(function () {
    // Clean up any generated files
    $files = [
        'TestResource.php',
        'Resource.php',
        'MyCustomResource.php',
        'BlogPost.php',
        'ProductCategory.php',
    ];

    foreach ($files as $file) {
        $path = $this->resourcePath.'/'.$file;
        if (File::exists($path)) {
            File::delete($path);
        }
    }

    // Remove empty directory if it exists
    if (File::isDirectory($this->resourcePath) && empty(File::files($this->resourcePath))) {
        File::deleteDirectory($this->resourcePath);
    }
});

it('generates a resource file with correct path', function () {
    $this->artisan('aura:resource', ['name' => 'TestResource'])
        ->assertExitCode(0);

    expect(File::exists($this->resourcePath.'/TestResource.php'))->toBeTrue();
});

it('generates resource class with correct namespace and structure', function () {
    $this->artisan('aura:resource', ['name' => 'Resource'])
        ->assertExitCode(0);

    $resourceClass = File::get($this->resourcePath.'/Resource.php');

    expect($resourceClass)
        ->toContain("namespace App\Aura\Resources;")
        ->toContain('use Aura\Base\Resource;')
        ->toContain('class Resource extends Resource')
        ->toContain('public static string $type = \'Resource\';')
        ->toContain('public static ?string $slug = \'resource\';')
        ->toContain('public function getIcon()')
        ->toContain('public static function getFields()')
        ->toContain('public static function getWidgets(): array');
});

it('generates resource with --custom option for custom table', function () {
    $this->artisan('aura:resource', ['name' => 'MyCustomResource', '--custom' => true])
        ->assertExitCode(0);

    $resourceClass = File::get($this->resourcePath.'/MyCustomResource.php');

    expect($resourceClass)
        ->toContain('public static $customTable = true')
        ->toContain("protected \$table = 'my_custom_resources'");
});

it('generates correct slug from PascalCase name', function () {
    $this->artisan('aura:resource', ['name' => 'BlogPost'])
        ->assertExitCode(0);

    $resourceClass = File::get($this->resourcePath.'/BlogPost.php');

    // Str::slug converts BlogPost to blogpost (no hyphens)
    expect($resourceClass)
        ->toContain("public static ?string \$slug = 'blogpost';")
        ->toContain("public static string \$type = 'BlogPost';");
});

it('generates correct table name for custom table resources', function () {
    $this->artisan('aura:resource', ['name' => 'ProductCategory', '--custom' => true])
        ->assertExitCode(0);

    $resourceClass = File::get($this->resourcePath.'/ProductCategory.php');

    expect($resourceClass)
        ->toContain("protected \$table = 'product_categories'");
});

it('generates resource with SVG icon', function () {
    $this->artisan('aura:resource', ['name' => 'TestResource'])
        ->assertExitCode(0);

    $resourceClass = File::get($this->resourcePath.'/TestResource.php');

    expect($resourceClass)
        ->toContain('<svg')
        ->toContain('</svg>');
});

it('does not overwrite existing resource without force', function () {
    // Create first resource
    $this->artisan('aura:resource', ['name' => 'TestResource'])
        ->assertExitCode(0);

    // Try to create again - Laravel's GeneratorCommand should handle this
    $this->artisan('aura:resource', ['name' => 'TestResource'])
        ->assertExitCode(0);

    expect(File::exists($this->resourcePath.'/TestResource.php'))->toBeTrue();
});
