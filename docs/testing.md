# Testing Guide

Aura CMS provides a comprehensive testing infrastructure built on PestPHP and Laravel's testing foundation. This guide covers testing strategies, patterns, and best practices for ensuring your Aura CMS applications are robust and reliable.

## Table of Contents

1. [Introduction](#introduction)
2. [Testing Setup](#testing-setup)
3. [Testing Architecture](#testing-architecture)
4. [Writing Tests](#writing-tests)
5. [Testing Resources](#testing-resources)
6. [Testing Fields](#testing-fields)
7. [Testing Livewire Components](#testing-livewire-components)
8. [Testing Permissions](#testing-permissions)
9. [Testing Media & Uploads](#testing-media--uploads)
10. [Testing Commands](#testing-commands)
11. [Continuous Integration](#continuous-integration)
12. [Performance Testing](#performance-testing)
13. [Best Practices](#best-practices)

## Introduction

Aura CMS uses PestPHP as its testing framework, providing an elegant and expressive testing experience. The testing suite covers:

- **Unit Tests**: Isolated testing of individual components
- **Feature Tests**: Integration testing of complete features
- **Browser Tests**: End-to-end testing (Dusk compatible)
- **Performance Tests**: Response time and optimization testing

### Key Benefits

- Fast test execution with SQLite in-memory database
- Comprehensive test helpers and utilities
- Team-aware testing support
- Full Livewire component testing
- Media and file upload testing

## Testing Setup

### Installation

Aura CMS comes with testing pre-configured. Ensure you have the required dependencies:

```bash
composer require --dev pestphp/pest
composer require --dev pestphp/pest-plugin-laravel
composer require --dev pestphp/pest-plugin-livewire
```

### Configuration Files

#### PHPUnit Configuration

Aura CMS provides two PHPUnit configurations:

**Standard Configuration** (`phpunit.xml.dist`):
```xml
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="vendor/phpunit/phpunit/phpunit.xsd"
         bootstrap="vendor/autoload.php"
         colors="true"
>
    <testsuites>
        <testsuite name="Aura CMS Test Suite">
            <directory>tests</directory>
        </testsuite>
    </testsuites>
    <php>
        <env name="APP_ENV" value="testing"/>
        <env name="DB_CONNECTION" value="sqlite"/>
        <env name="DB_DATABASE" value=":memory:"/>
        <env name="CACHE_DRIVER" value="array"/>
        <env name="SESSION_DRIVER" value="array"/>
        <env name="QUEUE_CONNECTION" value="sync"/>
    </php>
</phpunit>
```

**Without Teams Configuration** (`phpunit-without-teams.xml`):
```xml
<php>
    <env name="AURA_TEAMS" value="false"/>
    <!-- Other configurations same as standard -->
</php>
```

#### Pest Configuration

Configure Pest in `tests/Pest.php`:

```php
uses(Aura\Base\Tests\TestCase::class)->in(__DIR__);

// Define test groups
uses()->group('fields')->in('Feature/Fields');
uses()->group('resource')->in('Feature/Resource');
uses()->group('table')->in('Feature/Table');

// Global test helpers
function createSuperAdmin()
{
    $user = User::factory()->create();
    auth()->login($user);
    
    if (config('aura.teams')) {
        $team = Team::factory()->create();
        $user->teams()->attach($team);
        $user->switchTeam($team);
    }
    
    return $user;
}

function createPost(array $attributes = []): Post
{
    return Post::factory()->create(array_merge([
        'type' => 'Post',
        'title' => fake()->sentence(),
        'user_id' => auth()->id() ?? 1,
        'team_id' => auth()->user()?->currentTeam?->id ?? 1,
    ], $attributes));
}
```

### Running Tests

```bash
# Run all tests
composer test
./vendor/bin/pest

# Run specific test file
./vendor/bin/pest tests/Feature/ResourceTest.php

# Run specific test group
./vendor/bin/pest --group=fields

# Run with coverage
XDEBUG_MODE=coverage ./vendor/bin/pest --coverage --min=80

# Run without teams
./vendor/bin/pest -c phpunit-without-teams.xml

# Run in parallel
./vendor/bin/pest --parallel

# Stop on first failure
./vendor/bin/pest --bail
```

## Testing Architecture

### Directory Structure

```
tests/
├── Feature/                 # Integration tests
│   ├── Aura/               # Core functionality
│   ├── Auth/               # Authentication
│   ├── Commands/           # Artisan commands
│   ├── Fields/             # Field types
│   ├── Livewire/           # Components
│   ├── Media/              # File handling
│   ├── Permissions/        # Authorization
│   ├── Resource/           # Resources
│   └── Table/              # Table component
├── Unit/                   # Isolated unit tests
├── Browser/                # Dusk tests (optional)
├── TestCase.php           # Base test class
└── Pest.php               # Pest configuration
```

### Base Test Class

Create a base test class that extends Aura's TestCase:

```php
namespace Tests;

use Aura\Base\Tests\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;

abstract class TestCase extends BaseTestCase
{
    use RefreshDatabase;
    
    protected function setUp(): void
    {
        parent::setUp();
        
        // Additional setup
        $this->withoutVite();
        
        // Create default super admin
        $this->actingAs(createSuperAdmin());
    }
}
```

## Writing Tests

### Basic Test Structure

```php
describe('Product Resource', function () {
    beforeEach(function () {
        $this->user = createSuperAdmin();
        $this->actingAs($this->user);
    });
    
    test('can create a product', function () {
        $response = $this->post(route('aura.products.store'), [
            'name' => 'Test Product',
            'price' => 99.99,
            'status' => 'active',
        ]);
        
        $response->assertRedirect();
        
        expect(Product::where('name', 'Test Product')->exists())->toBeTrue();
    });
    
    it('validates required fields', function () {
        $response = $this->post(route('aura.products.store'), []);
        
        $response->assertSessionHasErrors(['name', 'price']);
    });
});
```

### Using Expectations

PestPHP provides expressive expectations:

```php
test('product calculations', function () {
    $product = Product::factory()->create([
        'price' => 100,
        'tax_rate' => 0.2,
    ]);
    
    expect($product)
        ->price->toBe(100.0)
        ->tax_rate->toBe(0.2)
        ->total_price->toBe(120.0);
    
    expect($product->isAvailable())->toBeTrue();
    expect($product->categories)->toHaveCount(3);
    expect($product->toArray())->toHaveKeys(['id', 'name', 'price']);
});
```

### Dataset Testing

Test multiple scenarios with datasets:

```php
it('validates email formats', function (string $email, bool $valid) {
    $response = $this->post(route('register'), [
        'email' => $email,
        'password' => 'password',
    ]);
    
    if ($valid) {
        expect($response)->not->toHaveSessionErrors('email');
    } else {
        expect($response)->toHaveSessionErrors('email');
    }
})->with([
    ['test@example.com', true],
    ['invalid.email', false],
    ['user@', false],
    ['user@domain', false],
    ['user@sub.domain.com', true],
]);
```

## Testing Resources

### Basic Resource Testing

```php
use Aura\Base\Facades\Aura;

test('resource registration', function () {
    // Fake Aura to prevent side effects
    Aura::fake();
    
    // Register a test resource
    Aura::registerResource(ProductResource::class);
    
    // Verify registration
    expect(Aura::getResources())->toContain(ProductResource::class);
    expect(Aura::findResourceBySlug('products'))->toBe(ProductResource::class);
});
```

### Testing Resource Fields

```php
test('resource has correct fields', function () {
    $resource = new ProductResource;
    $fields = collect($resource->getFields());
    
    expect($fields)
        ->toHaveCount(5)
        ->pluck('slug')->toContain('name', 'price', 'description');
    
    $nameField = $fields->firstWhere('slug', 'name');
    expect($nameField)
        ->type->toBe(Text::class)
        ->validation->toContain('required', 'max:255');
});
```

### Testing Resource CRUD Operations

```php
describe('Product CRUD', function () {
    test('index displays products', function () {
        Product::factory()->count(5)->create();
        
        $response = $this->get(route('aura.products.index'));
        
        $response->assertOk()
            ->assertSeeLivewire('aura::resource.index')
            ->assertSee('Products');
    });
    
    test('create form shows fields', function () {
        $response = $this->get(route('aura.products.create'));
        
        $response->assertOk()
            ->assertSeeLivewire('aura::resource.create')
            ->assertSee('Name')
            ->assertSee('Price')
            ->assertSee('Description');
    });
    
    test('store creates product', function () {
        $data = [
            'fields' => [
                'name' => 'New Product',
                'price' => 49.99,
                'description' => 'Test description',
            ],
        ];
        
        $response = $this->post(route('aura.products.store'), $data);
        
        $response->assertRedirect();
        
        $this->assertDatabaseHas('products', [
            'name' => 'New Product',
            'price' => 49.99,
        ]);
    });
});
```

### Testing Custom Tables

```php
test('resource with custom table', function () {
    Schema::create('custom_products', function ($table) {
        $table->id();
        $table->string('name');
        $table->decimal('price');
        $table->timestamps();
    });
    
    $resource = new class extends Resource {
        public static $model = Product::class;
        public static bool $customTable = true;
        protected $table = 'custom_products';
    };
    
    Aura::fake();
    Aura::registerResource($resource);
    
    // Test migration was applied
    expect(Schema::hasTable('custom_products'))->toBeTrue();
    expect(Schema::hasColumns('custom_products', ['name', 'price']))->toBeTrue();
});
```

## Testing Fields

### Field Validation Testing

```php
test('email field validation', function () {
    $field = Email::make('Email Address')
        ->rules('required|email|unique:users')
        ->slug('email_address');
    
    // Test validation rules
    $rules = $field->getValidationRules();
    expect($rules)->toContain('required', 'email', 'unique:users');
    
    // Test with Livewire component
    Livewire::test(Create::class, ['slug' => 'users'])
        ->set('form.fields.email_address', 'invalid-email')
        ->call('save')
        ->assertHasErrors(['form.fields.email_address' => 'email']);
});
```

### Field Display Testing

```php
test('field renders correctly', function () {
    $field = Text::make('Product Name')
        ->default('New Product')
        ->help('Enter the product name')
        ->placeholder('e.g., iPhone 15');
    
    $view = $this->blade(
        '<x-dynamic-component :component="$field->edit()" :field="$field" />',
        ['field' => $field]
    );
    
    expect($view->render())
        ->toContain('Product Name')
        ->toContain('Enter the product name')
        ->toContain('placeholder="e.g., iPhone 15"')
        ->toContain('value="New Product"');
});
```

### Conditional Field Testing

```php
test('conditional field logic', function () {
    $fields = [
        Select::make('Type')->options([
            'physical' => 'Physical',
            'digital' => 'Digital',
        ]),
        Text::make('Weight')->displayIf('type', 'physical'),
        Text::make('Download URL')->displayIf('type', 'digital'),
    ];
    
    $component = Livewire::test(Create::class, ['slug' => 'products'])
        ->set('form.fields.type', 'physical');
    
    expect($component->instance()->shouldShowField('weight'))->toBeTrue();
    expect($component->instance()->shouldShowField('download_url'))->toBeFalse();
    
    $component->set('form.fields.type', 'digital');
    
    expect($component->instance()->shouldShowField('weight'))->toBeFalse();
    expect($component->instance()->shouldShowField('download_url'))->toBeTrue();
});
```

### Custom Field Testing

```php
test('custom color picker field', function () {
    $field = new ColorPicker('Brand Color');
    
    expect($field)
        ->getComponent()->toBe('fields.color-picker')
        ->getValidationRules()->toContain('regex:/^#[0-9A-F]{6}$/i');
    
    // Test value transformation
    expect($field->transform('red'))->toBe('#FF0000');
    expect($field->transform('#00FF00'))->toBe('#00FF00');
});
```

## Testing Livewire Components

### Basic Component Testing

```php
use Livewire\Livewire;

test('dashboard component loads', function () {
    Livewire::test(Dashboard::class)
        ->assertOk()
        ->assertSee('Welcome to Aura CMS')
        ->assertSeeLivewire('aura::widgets.stats');
});
```

### Component Interaction Testing

```php
test('resource creation flow', function () {
    Livewire::test(Create::class, ['slug' => 'products'])
        ->assertSee('Create Product')
        ->set('form.fields.name', 'Test Product')
        ->set('form.fields.price', 99.99)
        ->set('form.fields.status', 'active')
        ->call('save')
        ->assertHasNoErrors()
        ->assertRedirect(route('aura.products.index'))
        ->assertDispatched('notify', [
            'message' => 'Product created successfully',
            'type' => 'success',
        ]);
    
    $this->assertDatabaseHas('products', [
        'name' => 'Test Product',
        'price' => 99.99,
    ]);
});
```

### Component Authorization Testing

```php
test('non-admin cannot access settings', function () {
    $user = User::factory()->create();
    $this->actingAs($user);
    
    Livewire::test(Settings::class)
        ->assertForbidden();
});

test('admin can update settings', function () {
    $admin = User::factory()->admin()->create();
    $this->actingAs($admin);
    
    Livewire::test(Settings::class)
        ->assertOk()
        ->set('settings.site_name', 'New Site Name')
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('settings-updated');
    
    expect(config('aura.site_name'))->toBe('New Site Name');
});
```

### Table Component Testing

```php
test('table sorting functionality', function () {
    Product::factory()->create(['name' => 'Alpha', 'created_at' => now()->subDay()]);
    Product::factory()->create(['name' => 'Beta', 'created_at' => now()]);
    Product::factory()->create(['name' => 'Gamma', 'created_at' => now()->subHour()]);
    
    Livewire::test(Table::class, ['model' => Product::class])
        ->assertSeeInOrder(['Beta', 'Gamma', 'Alpha']) // Default sort by created_at desc
        ->call('sortBy', 'name')
        ->assertSeeInOrder(['Alpha', 'Beta', 'Gamma'])
        ->call('sortBy', 'name') // Toggle direction
        ->assertSeeInOrder(['Gamma', 'Beta', 'Alpha']);
});

test('table filtering', function () {
    Product::factory()->count(3)->create(['status' => 'active']);
    Product::factory()->count(2)->create(['status' => 'inactive']);
    
    $component = Livewire::test(Table::class, ['model' => Product::class])
        ->assertSee('5 results');
    
    $component->set('filters.status', 'active')
        ->assertSee('3 results')
        ->assertDontSee('inactive');
    
    $component->set('filters.status', 'inactive')
        ->assertSee('2 results')
        ->assertDontSee('active');
});
```

### Testing Component Events

```php
test('components communicate via events', function () {
    // Create modal component that listens for events
    $modal = Livewire::test(Modal::class)
        ->assertSet('show', false);
    
    // Trigger event from another component
    Livewire::test(ProductList::class)
        ->call('editProduct', 1)
        ->assertDispatched('openModal', [
            'component' => 'product-form',
            'parameters' => ['productId' => 1],
        ]);
    
    // Verify modal received event
    $modal->dispatch('openModal', [
        'component' => 'product-form',
        'parameters' => ['productId' => 1],
    ])
    ->assertSet('show', true)
    ->assertSet('component', 'product-form');
});
```

## Testing Permissions

### Role-Based Testing

```php
test('role permissions', function () {
    $role = Role::create([
        'name' => 'Editor',
        'permissions' => [
            'viewAny-post' => true,
            'view-post' => true,
            'create-post' => true,
            'update-post' => true,
            'delete-post' => false,
        ],
    ]);
    
    $user = User::factory()->create();
    $user->assignRole($role);
    $this->actingAs($user);
    
    $post = Post::factory()->create();
    
    // Test permissions
    expect($user->can('viewAny', Post::class))->toBeTrue();
    expect($user->can('create', Post::class))->toBeTrue();
    expect($user->can('update', $post))->toBeTrue();
    expect($user->can('delete', $post))->toBeFalse();
    
    // Test in practice
    $this->get(route('aura.posts.index'))->assertOk();
    $this->get(route('aura.posts.create'))->assertOk();
    $this->get(route('aura.posts.edit', $post))->assertOk();
    $this->delete(route('aura.posts.destroy', $post))->assertForbidden();
});
```

### Team-Based Permissions

```php
test('team scoped permissions', function () {
    $team1 = Team::factory()->create();
    $team2 = Team::factory()->create();
    
    $user = User::factory()->create();
    $user->teams()->attach([$team1->id, $team2->id]);
    $user->switchTeam($team1);
    
    $this->actingAs($user);
    
    // Create posts in different teams
    $team1Post = Post::factory()->create(['team_id' => $team1->id]);
    $team2Post = Post::factory()->create(['team_id' => $team2->id]);
    
    // Can only see current team's posts
    $response = $this->get(route('aura.posts.index'));
    $response->assertSee($team1Post->title)
             ->assertDontSee($team2Post->title);
    
    // Switch teams
    $user->switchTeam($team2);
    
    $response = $this->get(route('aura.posts.index'));
    $response->assertDontSee($team1Post->title)
             ->assertSee($team2Post->title);
});
```

### Policy Testing

```php
test('custom resource policy', function () {
    $owner = User::factory()->create();
    $other = User::factory()->create();
    
    $product = Product::factory()->create(['user_id' => $owner->id]);
    
    // Owner can do everything
    $this->actingAs($owner);
    expect($owner->can('update', $product))->toBeTrue();
    expect($owner->can('delete', $product))->toBeTrue();
    
    // Other user cannot
    $this->actingAs($other);
    expect($other->can('update', $product))->toBeFalse();
    expect($other->can('delete', $product))->toBeFalse();
});
```

## Testing Media & Uploads

### File Upload Testing

```php
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

test('file upload handling', function () {
    Storage::fake('public');
    
    $file = UploadedFile::fake()->image('product.jpg', 800, 600);
    
    Livewire::test(MediaUploader::class)
        ->set('files', [$file])
        ->call('upload')
        ->assertHasNoErrors()
        ->assertDispatched('fileUploaded');
    
    // Verify file was stored
    Storage::disk('public')->assertExists('media/' . $file->hashName());
    
    // Verify database record
    $this->assertDatabaseHas('attachments', [
        'name' => 'product.jpg',
        'mime_type' => 'image/jpeg',
        'size' => $file->getSize(),
    ]);
});
```

### Image Processing Testing

```php
test('image thumbnail generation', function () {
    Storage::fake('public');
    
    $image = UploadedFile::fake()->image('large.jpg', 2000, 2000);
    
    $attachment = Attachment::create([
        'name' => $image->getClientOriginalName(),
        'path' => $image->store('media', 'public'),
        'mime_type' => 'image/jpeg',
        'size' => $image->getSize(),
    ]);
    
    // Trigger thumbnail generation
    $attachment->generateThumbnails();
    
    // Verify thumbnails exist
    expect($attachment->getThumbnail('small'))->not->toBeNull();
    expect($attachment->getThumbnail('medium'))->not->toBeNull();
    
    Storage::disk('public')->assertExists($attachment->getThumbnailPath('small'));
    Storage::disk('public')->assertExists($attachment->getThumbnailPath('medium'));
});
```

### Media Library Testing

```php
test('media library selection', function () {
    // Create test media
    $images = Attachment::factory()->count(5)->image()->create();
    $documents = Attachment::factory()->count(3)->document()->create();
    
    Livewire::test(MediaManager::class, ['type' => 'image'])
        ->assertSee('Media Library')
        ->assertViewHas('attachments', function ($attachments) use ($images) {
            return $attachments->count() === 5;
        })
        ->set('selected', [$images[0]->id, $images[1]->id])
        ->call('confirmSelection')
        ->assertDispatched('media-selected', function ($event, $data) {
            return count($data['media']) === 2;
        });
});
```

## Testing Commands

### Command Execution Testing

```php
test('make:resource command', function () {
    $this->artisan('aura:resource', ['name' => 'Product'])
        ->expectsOutput('Resource [Product] created successfully.')
        ->assertSuccessful();
    
    // Verify file was created
    $this->assertFileExists(app_path('Aura/Resources/Product.php'));
    
    // Verify file content
    $content = file_get_contents(app_path('Aura/Resources/Product.php'));
    expect($content)
        ->toContain('namespace App\Aura\Resources')
        ->toContain('class Product extends Resource')
        ->toContain('public static string $model = Product::class');
    
    // Cleanup
    unlink(app_path('Aura/Resources/Product.php'));
});
```

### Interactive Command Testing

```php
test('aura:install command', function () {
    $this->artisan('aura:install')
        ->expectsQuestion('Do you want to use teams?', true)
        ->expectsQuestion('Do you want to install sample data?', false)
        ->expectsOutput('Publishing Aura assets...')
        ->expectsOutput('Running migrations...')
        ->expectsOutput('Aura CMS installed successfully!')
        ->assertSuccessful();
    
    // Verify configuration
    expect(config('aura.teams'))->toBeTrue();
    
    // Verify migrations ran
    expect(Schema::hasTable('teams'))->toBeTrue();
});
```

### Command Options Testing

```php
test('resource command with options', function () {
    $this->artisan('aura:resource Product --model=App\Models\Product --force')
        ->assertSuccessful();
    
    $content = file_get_contents(app_path('Aura/Resources/Product.php'));
    expect($content)->toContain('public static string $model = \App\Models\Product::class');
});
```

## Continuous Integration

### GitHub Actions Configuration

Create `.github/workflows/tests.yml`:

```yaml
name: Tests

on:
  push:
    branches: [main, develop]
  pull_request:
    branches: [main]

jobs:
  test:
    runs-on: ubuntu-latest
    
    strategy:
      matrix:
        php: [8.1, 8.2, 8.3]
        laravel: [10.*, 11.*]
        
    steps:
      - uses: actions/checkout@v3
      
      - name: Setup PHP
        uses: shivammathur/setup-php@v2
        with:
          php-version: ${{ matrix.php }}
          extensions: dom, curl, libxml, mbstring, zip, pcntl, pdo, sqlite, pdo_sqlite
          coverage: xdebug
          
      - name: Install dependencies
        run: |
          composer require "laravel/framework:${{ matrix.laravel }}" --no-interaction --no-update
          composer update --prefer-dist --no-interaction
          
      - name: Execute tests
        run: vendor/bin/pest --coverage --min=80
        
      - name: Upload coverage
        uses: codecov/codecov-action@v3
        with:
          file: ./coverage.xml
```

### GitLab CI Configuration

Create `.gitlab-ci.yml`:

```yaml
stages:
  - test
  - deploy

variables:
  MYSQL_DATABASE: aura_test
  MYSQL_ROOT_PASSWORD: secret

test:
  stage: test
  image: php:8.2-cli
  services:
    - mysql:8.0
  before_script:
    - apt-get update -yqq
    - apt-get install -yqq git libpq-dev libcurl4-gnutls-dev libicu-dev libvpx-dev libjpeg-dev libpng-dev libxpm-dev zlib1g-dev libfreetype6-dev libxml2-dev libexpat1-dev libbz2-dev libgmp3-dev libldap2-dev unixodbc-dev libsqlite3-dev libaspell-dev libsnmp-dev libpcre3-dev libtidy-dev libonig-dev libzip-dev
    - docker-php-ext-install mbstring pdo_mysql zip
    - pecl install xdebug
    - docker-php-ext-enable xdebug
    - curl -sS https://getcomposer.org/installer | php
    - php composer.phar install
  script:
    - cp .env.example .env
    - php artisan key:generate
    - php artisan migrate
    - vendor/bin/pest --coverage --coverage-text --colors=never
  coverage: '/^\s*Lines:\s*\d+.\d+\%/'
```

### Pre-commit Hooks

Install pre-commit hooks to run tests before commits:

```bash
composer require --dev captainhook/captainhook
vendor/bin/captainhook install
```

Configure in `captainhook.json`:

```json
{
    "pre-commit": {
        "enabled": true,
        "actions": [
            {
                "action": "vendor/bin/pest --bail --parallel"
            },
            {
                "action": "vendor/bin/phpstan analyse"
            },
            {
                "action": "vendor/bin/pint --test"
            }
        ]
    }
}
```

## Performance Testing

### Response Time Testing

```php
test('page load performance', function () {
    $start = microtime(true);
    
    $response = $this->get(route('aura.posts.index'));
    
    $duration = microtime(true) - $start;
    
    expect($duration)->toBeLessThan(0.5); // 500ms max
});
```

### Database Query Testing

```php
test('optimized queries', function () {
    Product::factory()->count(100)->create();
    
    DB::enableQueryLog();
    
    $this->get(route('aura.products.index'));
    
    $queries = DB::getQueryLog();
    
    // Should use eager loading, not N+1
    expect(count($queries))->toBeLessThan(10);
    
    // Check for specific optimizations
    $hasEagerLoading = collect($queries)->contains(function ($query) {
        return str_contains($query['query'], 'whereIn');
    });
    
    expect($hasEagerLoading)->toBeTrue();
});
```

### Memory Usage Testing

```php
test('memory efficient operations', function () {
    $initialMemory = memory_get_usage();
    
    // Process large dataset
    Product::query()
        ->chunk(100, function ($products) {
            // Process products
        });
    
    $peakMemory = memory_get_peak_usage();
    $memoryIncrease = ($peakMemory - $initialMemory) / 1024 / 1024; // MB
    
    expect($memoryIncrease)->toBeLessThan(50); // Max 50MB increase
});
```

### Load Testing

Create a dedicated load test file:

```php
// tests/Performance/LoadTest.php
test('handles concurrent requests', function () {
    $responses = collect();
    
    $promises = collect(range(1, 50))->map(function ($i) {
        return Http::async()->get(route('aura.products.index'));
    });
    
    $responses = Http::pool(fn ($pool) => $promises);
    
    $successCount = collect($responses)->filter(fn ($r) => $r->ok())->count();
    
    expect($successCount)->toBe(50);
});
```

## Best Practices

### 1. Test Organization

```php
// Group related tests
describe('Product Management', function () {
    describe('Creation', function () {
        test('validates required fields', function () {
            // Test validation
        });
        
        test('creates with valid data', function () {
            // Test creation
        });
    });
    
    describe('Updates', function () {
        // Update tests
    });
});
```

### 2. Data Factories

Create comprehensive factories:

```php
class ProductFactory extends Factory
{
    public function definition(): array
    {
        return [
            'name' => $this->faker->productName(),
            'price' => $this->faker->randomFloat(2, 10, 1000),
            'description' => $this->faker->paragraph(),
            'status' => $this->faker->randomElement(['active', 'inactive']),
            'stock' => $this->faker->numberBetween(0, 100),
        ];
    }
    
    public function active(): static
    {
        return $this->state(fn () => ['status' => 'active']);
    }
    
    public function outOfStock(): static
    {
        return $this->state(fn () => ['stock' => 0]);
    }
}
```

### 3. Test Helpers

Create reusable test helpers:

```php
// tests/Helpers/ResourceTestHelper.php
namespace Tests\Helpers;

trait ResourceTestHelper
{
    protected function createResourceWithFields(string $resourceClass, array $fields = [])
    {
        $resource = new $resourceClass;
        
        foreach ($fields as $field) {
            $resource->addField($field);
        }
        
        Aura::registerResource($resource);
        
        return $resource;
    }
    
    protected function assertResourceHasField(Resource $resource, string $slug)
    {
        $fields = collect($resource->getFields());
        
        expect($fields->pluck('slug')->toArray())->toContain($slug);
    }
}
```

### 4. Mock External Services

```php
// Mock external API calls
Http::fake([
    'api.external.com/*' => Http::response(['status' => 'ok'], 200),
]);

// Mock file storage
Storage::fake('s3');

// Mock mail sending
Mail::fake();

// Mock queue jobs
Queue::fake();
```

### 5. Test Database Seeders

```php
test('database seeder creates initial data', function () {
    $this->seed(DatabaseSeeder::class);
    
    expect(User::count())->toBeGreaterThan(0);
    expect(Role::count())->toBe(3); // Admin, Editor, User
    expect(Post::count())->toBeGreaterThan(10);
});
```

### 6. Clean Test Data

```php
afterEach(function () {
    // Clean up uploaded files
    Storage::fake('public')->deleteDirectory('test-uploads');
    
    // Clear cache
    Cache::flush();
    
    // Reset config changes
    config(['aura' => config('aura-testing')]);
});
```

### 7. Assertion Methods

Create custom assertions:

```php
expect()->extend('toBeActiveResource', function () {
    return $this->toBeInstanceOf(Resource::class)
        ->status->toBe('active')
        ->deleted_at->toBeNull();
});

// Usage
expect($product)->toBeActiveResource();
```

> 📹 **Video Placeholder**: [Complete walkthrough of setting up and running tests in Aura CMS, including CI/CD integration]

## Pro Tips

1. **Use Parallel Testing**: Run tests in parallel for faster execution with `--parallel`
2. **Profile Slow Tests**: Use `--profile` to identify slow tests
3. **Test Coverage**: Aim for 80%+ coverage but focus on critical paths
4. **Mock Time**: Use `$this->travel(5)->days()` for time-based testing
5. **Test Emails**: Use `Mail::fake()` and `Mail::assertSent()`
6. **Test Events**: Use `Event::fake()` and `Event::assertDispatched()`
7. **Test Jobs**: Use `Queue::fake()` and `Queue::assertPushed()`
8. **Database Transactions**: Tests automatically rollback, keeping DB clean
9. **HTTP Testing**: Use Laravel's HTTP client for external API testing
10. **Browser Testing**: Add Dusk for E2E testing when needed

## Common Testing Patterns

### Testing Soft Deletes

```php
test('soft delete functionality', function () {
    $product = Product::factory()->create();
    
    $product->delete();
    
    expect($product->fresh())
        ->deleted_at->not->toBeNull()
        ->trashed()->toBeTrue();
    
    // Not visible in normal queries
    expect(Product::find($product->id))->toBeNull();
    
    // Visible with trashed
    expect(Product::withTrashed()->find($product->id))->not->toBeNull();
    
    // Restore
    $product->restore();
    expect($product->fresh()->deleted_at)->toBeNull();
});
```

### Testing Scopes

```php
test('resource scopes', function () {
    Product::factory()->count(3)->create(['status' => 'active']);
    Product::factory()->count(2)->create(['status' => 'inactive']);
    
    expect(Product::active()->count())->toBe(3);
    expect(Product::inactive()->count())->toBe(2);
    expect(Product::count())->toBe(5);
});
```

### Testing Relationships

```php
test('resource relationships', function () {
    $category = Category::factory()->create();
    $products = Product::factory()->count(3)->create([
        'category_id' => $category->id,
    ]);
    
    expect($category->products)->toHaveCount(3);
    expect($category->products->first())->toBeInstanceOf(Product::class);
    
    $products->each(function ($product) use ($category) {
        expect($product->category->id)->toBe($category->id);
    });
});
```

## Conclusion

Testing is a critical part of developing robust Aura CMS applications. This guide provides comprehensive patterns and practices for testing every aspect of your application. Remember:

- Write tests as you develop, not after
- Focus on behavior, not implementation
- Keep tests simple and readable
- Use factories and helpers to reduce duplication
- Run tests frequently and automatically

For more testing resources, see the [Laravel Testing Documentation](https://laravel.com/docs/testing) and [PestPHP Documentation](https://pestphp.com/docs).