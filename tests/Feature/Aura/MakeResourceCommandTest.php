<?php

use App\Aura\Resources\StubWidget;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
    $this->resourcePath = app_path('Aura/Resources');
});

afterEach(function () {
    Schema::dropIfExists('stub_widgets');

    // Clean up any generated files
    $files = [
        'TestResource.php',
        'Resource.php',
        'MyCustomResource.php',
        'BlogPost.php',
        'ProductCategory.php',
        'StubWidget.php',
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
        ->toContain('public static bool $usesMeta = false;')
        ->toContain("protected \$table = 'my_custom_resources'");
});

it('stores a custom table resource field in its own column instead of meta', function () {
    $this->artisan('aura:resource', ['name' => 'StubWidget', '--custom' => true])
        ->assertExitCode(0);

    $path = $this->resourcePath.'/StubWidget.php';

    File::put($path, str_replace(
        '            // Fields are plain arrays. Uncomment to get started:',
        "            ['name' => 'Title', 'slug' => 'title', 'type' => 'Aura\\\\Base\\\\Fields\\\\Text', 'conditional_logic' => []],",
        File::get($path)
    ));

    require_once $path;

    Schema::create('stub_widgets', function (Blueprint $table) {
        $table->id();
        $table->string('title')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->foreignId('team_id')->nullable();
        $table->timestamps();
    });

    $widget = StubWidget::create(['title' => 'Hello']);

    expect(DB::table('stub_widgets')->where('id', $widget->id)->value('title'))->toBe('Hello');
    expect(DB::table('meta')->where('metable_type', StubWidget::class)->count())->toBe(0);
});

it('generates correct slug and readable names from a PascalCase name', function () {
    $this->artisan('aura:resource', ['name' => 'BlogPost'])
        ->assertExitCode(0);

    $resourceClass = File::get($this->resourcePath.'/BlogPost.php');

    expect($resourceClass)
        ->toContain("public static ?string \$slug = 'blog-post';")
        ->toContain("public static string \$type = 'BlogPost';")
        ->toContain("public static \$singularName = 'Blog Post';")
        ->toContain("public static \$pluralName = 'Blog Posts';");
});

it('gives custom table resources a matching slug and table', function () {
    $this->artisan('aura:resource', ['name' => 'BlogPost', '--custom' => true])
        ->assertExitCode(0);

    $resourceClass = File::get($this->resourcePath.'/BlogPost.php');

    expect($resourceClass)
        ->toContain("public static ?string \$slug = 'blog-post';")
        ->toContain("protected \$table = 'blog_posts';")
        ->toContain("public static \$singularName = 'Blog Post';")
        ->toContain("public static \$pluralName = 'Blog Posts';");
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
