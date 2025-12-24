# Plugin Development

Aura CMS provides a powerful plugin system that allows you to extend and customize the platform's functionality. Built on Laravel's package ecosystem, plugins can add new resources, fields, widgets, and more while maintaining clean separation from the core code.

## Table of Contents

- [Overview](#overview)
- [Plugin Architecture](#plugin-architecture)
- [Creating Plugins](#creating-plugins)
- [Plugin Types](#plugin-types)
- [Plugin Development](#plugin-development)
- [Hook System](#hook-system)
- [Event System](#event-system)
- [Testing Plugins](#testing-plugins)
- [Distribution](#distribution)
- [Best Practices](#best-practices)
- [Example Plugins](#example-plugins)

## Overview

Aura plugins are standard Laravel packages with additional conventions for seamless integration:

- **Composer-based**: Install and update via Composer
- **PSR-4 Autoloading**: Standard PHP namespace conventions
- **Service Provider**: Register functionality with Aura using Spatie's Laravel Package Tools
- **Isolated**: Each plugin has its own namespace and dependencies
- **Testable**: Full testing support with PHPUnit/Pest

## Plugin Architecture

### Directory Structure

The plugin generator creates the following structure (complete plugin example):

```
plugins/
└── vendor-name/
    └── plugin-name/
        ├── src/
        │   ├── PluginNameServiceProvider.php
        │   ├── Commands/
        │   │   └── PluginNameCommand.php
        │   ├── Facades/
        │   │   └── PluginName.php
        │   └── PluginName.php
        ├── resources/
        │   └── views/
        ├── config/
        │   └── plugin-name.php
        ├── database/
        │   ├── factories/
        │   │   └── ModelFactory.php
        │   └── migrations/
        │       └── create_plugin_name_table.php.stub
        ├── composer.json
        ├── configure.php
        ├── README.md
        ├── CHANGELOG.md
        └── LICENSE.md
```

### Registration Flow

1. **Composer Autoloading**: Plugin classes are autoloaded via PSR-4 (added to main `composer.json`)
2. **Service Provider**: Optionally registered in `config/app.php` via the generator
3. **Spatie Package Tools**: Uses `PackageServiceProvider` for Laravel integration
4. **Runtime**: Plugin features available throughout application

## Creating Plugins

### Using the Plugin Generator

Aura provides an Artisan command to scaffold new plugins:

```bash
php artisan aura:plugin vendor/plugin-name
```

The generator offers three plugin types:
1. **Complete Plugin**: Full-featured Laravel package with config, migrations, commands, and views
2. **Resource Plugin**: Adds new resource types to Aura CMS
3. **Field Plugin**: Adds new custom field types

### Step-by-Step Creation

```bash
# 1. Create plugin (interactive)
php artisan aura:plugin

# Or with name argument
php artisan aura:plugin acme/blog

# 2. Select plugin type from the menu
# > Complete plugin

# 3. Plugin created at plugins/acme/blog
# 4. Optionally adds service provider to config/app.php
# 5. Updates composer.json autoload and runs dump-autoload
```

The generator will:
- Create the plugin directory structure at `plugins/vendor/name`
- Run a configure script to replace placeholder values
- Offer to add the ServiceProvider to `config/app.php`
- Update the main `composer.json` with PSR-4 autoloading
- Run `composer dump-autoload` automatically

### Manual Creation

```bash
# Create directory structure
mkdir -p plugins/acme/blog/src

# Create composer.json
cat > plugins/acme/blog/composer.json << 'EOF'
{
    "name": "acme/blog",
    "description": "Blog plugin for Aura CMS",
    "type": "library",
    "require": {
        "php": "^8.2",
        "spatie/laravel-package-tools": "^1.14.0",
        "illuminate/contracts": "^10.0|^11.0|^12.0"
    },
    "autoload": {
        "psr-4": {
            "Acme\\Blog\\": "src"
        }
    },
    "extra": {
        "laravel": {
            "providers": [
                "Acme\\Blog\\BlogServiceProvider"
            ]
        }
    }
}
EOF

# Update main composer.json to include the plugin path
# Add to autoload.psr-4: "Acme\\Blog\\": "plugins/acme/blog/src"
# Then run:
composer dump-autoload
```

## Plugin Types

### Complete Plugin

A full-featured plugin uses Spatie's Laravel Package Tools for configuration and can integrate with Aura:

```php
namespace Acme\Blog;

use Aura\Base\Facades\Aura;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Acme\Blog\Commands\BlogCommand;

class BlogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('blog')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigration('create_blog_table')
            ->hasCommand(BlogCommand::class);

        // Register Aura resources in configurePackage
        Aura::registerResources([
            Resources\Post::class,
            Resources\Category::class,
        ]);

        // Register custom fields
        Aura::registerFields([
            Fields\MarkdownEditor::class,
            Fields\TagSelector::class,
        ]);

        // Register widgets
        Aura::registerWidgets([
            Widgets\RecentPosts::class,
            Widgets\PopularPosts::class,
        ]);
    }

    public function packageBooted(): void
    {
        // Additional boot logic (optional)
        // Register routes if needed
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
```

### Resource Plugin

Adds new resource types to Aura CMS. The generated structure includes a basic resource class and service provider:

```php
namespace Acme\Products;

use Aura\Base\Facades\Aura;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ProductsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('products')
            ->hasViews('acme-products');

        /*
         * Register Aura Resources
         *
         * More info: https://aura-cms.com/docs/resources
         */
        Aura::registerResources([
            \Acme\Products\Product::class,
        ]);
    }
}
```

Product Resource example (generated scaffold):

```php
namespace Acme\Products;

use Aura\Base\Resource;

class Product extends Resource
{
    public static ?string $slug = 'product';

    public static string $type = 'Product';

    protected static ?string $group = 'Acme';

    public static function getFields()
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'title',
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }

    public function getIcon()
    {
        return '<svg class="w-5 h-5" viewBox="0 0 18 18" fill="none" stroke="currentColor" xmlns="http://www.w3.org/2000/svg"><path d="M15.75 9a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
    }

    public static function getWidgets(): array
    {
        return [];
    }
}
```

Extended Resource example with more fields:

```php
namespace Acme\Products;

use Aura\Base\Resource;

class Product extends Resource
{
    public static string $type = 'Product';
    
    public static ?string $slug = 'product';
    
    public static ?string $name = 'Products';
    
    public static ?string $singularName = 'Product';
    
    protected static ?string $group = 'E-Commerce';
    
    public static function getFields()
    {
        return [
            [
                'name' => 'Product Information',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'slug' => 'product-info',
            ],
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'name',
                'validation' => 'required|max:255',
                'searchable' => true,
                'on_index' => true,
            ],
            [
                'name' => 'Price',
                'type' => 'Aura\\Base\\Fields\\Number',
                'slug' => 'price',
                'validation' => 'required|numeric|min:0',
                'on_index' => true,
            ],
            [
                'name' => 'Description',
                'type' => 'Aura\\Base\\Fields\\Wysiwyg',
                'slug' => 'description',
            ],
            [
                'name' => 'Images',
                'type' => 'Aura\\Base\\Fields\\Image',
                'slug' => 'images',
            ],
        ];
    }

    public function getIcon()
    {
        return '<svg class="w-5 h-5" viewBox="0 0 24 24" fill="none" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/></svg>';
    }
}
```

### Field Plugin

Creates custom field types. The generated structure includes a field class and Blade views:

**Service Provider** (`src/ColorPickerServiceProvider.php`):

```php
namespace Acme\ColorPicker;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ColorPickerServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package
            ->name('colorpicker')
            ->hasViews('acme-colorpicker');
    }
}
```

**Field Class** (`src/ColorPicker.php`):

```php
namespace Acme\ColorPicker;

use Aura\Base\Fields\Field;

class ColorPicker extends Field
{
    // Component view for editing (form input)
    public $component = 'acme-colorpicker::fields.color-picker';
    
    // View for displaying the value (read-only)
    public $view = 'acme-colorpicker::fields.color-picker-view';
    
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            // Custom field configuration options
            // These appear in the Resource Editor when configuring the field
            [
                'name' => 'Default Color',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'default_color',
                'validation' => 'regex:/^#[0-9A-F]{6}$/i',
                'instructions' => 'Default color in hex format (e.g., #FF0000)',
            ],
        ]);
    }
}
```

**Edit View** (`resources/views/components/fields/color-picker.blade.php`):

```blade
<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text 
        :disabled="optional($field)['disabled']" 
        wire:model="form.fields.{{ optional($field)['slug'] }}" 
        error="form.fields.{{ optional($field)['slug'] }}" 
        placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}" 
        id="resource-field-{{ optional($field)['slug'] }}"
    />
</x-aura::fields.wrapper>
```

**View Mode** (`resources/views/components/fields/color-picker-view.blade.php`):

```blade
<x-aura::fields.wrapper :field="$field">
    {!! $this->model->display($field['slug']) !!}
</x-aura::fields.wrapper>
```

**Extended Example with Color Picker UI**:

```blade
{{-- resources/views/components/fields/color-picker.blade.php --}}
<x-aura::fields.wrapper :field="$field">
    <div x-data="{ 
        color: $wire.entangle('form.fields.{{ optional($field)['slug'] }}'),
        showPicker: false 
    }" class="relative">
        <div class="flex items-center space-x-2">
            <input 
                type="text" 
                x-model="color"
                class="aura-input flex-1"
                placeholder="#000000"
            >
            
            <div 
                @click="showPicker = !showPicker"
                class="w-10 h-10 rounded cursor-pointer border"
                :style="{ backgroundColor: color }"
            ></div>
        </div>
        
        <div 
            x-show="showPicker" 
            x-transition
            @click.outside="showPicker = false"
            class="absolute z-10 mt-2 p-2 bg-white rounded-lg shadow-lg"
        >
            <!-- Color picker implementation -->
        </div>
    </div>
</x-aura::fields.wrapper>
```

### Adding Widgets

To add dashboard widgets to your plugin, create a widget class and register it with Aura. Widgets can be added to any plugin type:

```php
namespace Acme\Analytics;

use Aura\Base\Widgets\Widget;

class PageViewsWidget extends Widget
{
    public function render()
    {
        $views = $this->getPageViews();
        
        return view('acme-analytics::widgets.page-views', [
            'views' => $views,
            'period' => $this->period,
        ]);
    }
    
    protected function getPageViews()
    {
        // Your analytics logic here
        return [
            'total' => 15234,
            'trend' => '+12.5%',
            'chart' => [...],
        ];
    }
}
```

Register widgets in your service provider:

```php
use Aura\Base\Facades\Aura;

public function configurePackage(Package $package): void
{
    $package
        ->name('analytics')
        ->hasViews('acme-analytics');

    Aura::registerWidgets([
        \Acme\Analytics\PageViewsWidget::class,
    ]);
}
```

## Plugin Development

### Using Aura APIs

All Aura registrations should typically be done in the `configurePackage()` method of your service provider:

```php
use Aura\Base\Facades\Aura;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MyPluginServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('my-plugin')
            ->hasViews('my-plugin');

        // Register multiple resources
        Aura::registerResources([
            Resources\Article::class,
            Resources\Author::class,
            Resources\Category::class,
        ]);

        // Register custom fields
        Aura::registerFields([
            Fields\LocationPicker::class,
            Fields\VideoEmbed::class,
        ]);

        // Register widgets
        Aura::registerWidgets([
            Widgets\Statistics::class,
            Widgets\RecentActivity::class,
        ]);
    }

    public function packageBooted(): void
    {
        // Access configuration at runtime
        $mediaSettings = Aura::option('media');
        $siteTitle = Aura::option('general')['site_title'] ?? 'My Site';

        // Get registered resources
        $resources = Aura::getResources();
        $productResource = Aura::findResourceBySlug('product');

        // Inject views into specific locations
        Aura::registerInjectView('dashboard.footer', function () {
            return view('my-plugin::partials.dashboard-footer');
        });
    }
}
```

### Database Migrations

Create migrations for custom tables:

```php
// database/migrations/2024_01_01_000000_create_products_table.php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up()
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->string('sku')->unique();
            $table->integer('stock')->default(0);
            $table->boolean('is_active')->default(true);
            
            // Team support
            if (config('aura.teams')) {
                $table->foreignId('team_id')->constrained()->cascadeOnDelete();
            }
            
            $table->timestamps();
            $table->softDeletes();
        });
    }
    
    public function down()
    {
        Schema::dropIfExists('products');
    }
};
```

### Configuration Files

```php
// config/my-plugin.php
return [
    'features' => [
        'comments' => true,
        'ratings' => true,
        'reviews' => false,
    ],
    
    'api' => [
        'key' => env('MY_PLUGIN_API_KEY'),
        'endpoint' => env('MY_PLUGIN_API_ENDPOINT', 'https://api.example.com'),
    ],
    
    'cache' => [
        'enabled' => true,
        'ttl' => 3600, // 1 hour
    ],
];
```

### Routes

```php
// routes/web.php
use Illuminate\Support\Facades\Route;
use Acme\Blog\Http\Controllers\BlogController;

Route::middleware(config('aura.middleware.web'))
    ->prefix('blog')
    ->name('blog.')
    ->group(function () {
        Route::get('/', [BlogController::class, 'index'])->name('index');
        Route::get('/{slug}', [BlogController::class, 'show'])->name('show');
        Route::get('/category/{category}', [BlogController::class, 'category'])->name('category');
    });

// API routes
Route::middleware(config('aura.middleware.api'))
    ->prefix('api/blog')
    ->group(function () {
        Route::get('/posts', [BlogController::class, 'apiIndex']);
        Route::get('/posts/{id}', [BlogController::class, 'apiShow']);
    });
```

### Views and Assets

```blade
{{-- resources/views/blog/index.blade.php --}}
<x-aura::layout.app>
    <div class="container mx-auto px-4 py-8">
        <h1 class="text-3xl font-bold mb-8">Blog Posts</h1>
        
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            @foreach($posts as $post)
                <article class="bg-white rounded-lg shadow-md overflow-hidden">
                    @if($post->featured_image)
                        <img 
                            src="{{ $post->featured_image->thumbnail('md') }}" 
                            alt="{{ $post->title }}"
                            class="w-full h-48 object-cover"
                        >
                    @endif
                    
                    <div class="p-6">
                        <h2 class="text-xl font-semibold mb-2">
                            <a href="{{ route('blog.show', $post->slug) }}" class="hover:text-blue-600">
                                {{ $post->title }}
                            </a>
                        </h2>
                        
                        <p class="text-gray-600 mb-4">{{ Str::limit($post->excerpt, 150) }}</p>
                        
                        <div class="flex items-center text-sm text-gray-500">
                            <span>{{ $post->author->name }}</span>
                            <span class="mx-2">•</span>
                            <time>{{ $post->published_at->format('M d, Y') }}</time>
                        </div>
                    </div>
                </article>
            @endforeach
        </div>
        
        {{ $posts->links() }}
    </div>
</x-aura::layout.app>
```

## Hook System

Aura provides a hook system for extending functionality:

### Registering Hooks

```php
use Aura\Base\Facades\Aura;

class PluginServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Add navigation items
        app('aura.hooks')->addHook('navigation.after', function ($navigation) {
            $navigation[] = [
                'name' => 'Analytics',
                'slug' => 'analytics',
                'icon' => 'chart-bar',
                'route' => 'analytics.dashboard',
                'sort' => 100,
            ];
            
            return $navigation;
        });
        
        // Modify resource fields
        app('aura.hooks')->addHook('resource.fields.post', function ($fields) {
            $fields[] = [
                'name' => 'SEO Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'seo_title',
                'validation' => 'max:60',
                'instructions' => 'Maximum 60 characters',
            ];
            
            return $fields;
        });
        
        // Add to dashboard
        app('aura.hooks')->addHook('dashboard.widgets', function ($widgets) {
            $widgets[] = \Acme\Analytics\Widgets\TrafficWidget::class;
            return $widgets;
        });
    }
}
```

### Available Hooks

- `navigation.before` - Modify navigation before rendering
- `navigation.after` - Add items after existing navigation
- `resource.fields.{type}` - Modify fields for specific resource type
- `dashboard.widgets` - Add widgets to dashboard
- `table.filters.{resource}` - Add filters to resource tables
- `table.actions.{resource}` - Add actions to resource tables

## Event System

### Listening to Events

```php
use Aura\Base\Events\SaveFields;
use Illuminate\Support\Facades\Event;

class PluginServiceProvider extends ServiceProvider
{
    public function boot()
    {
        // Listen to field save events
        Event::listen(SaveFields::class, function (SaveFields $event) {
            $model = $event->model;
            $fields = $event->fields;
            
            // Process saved fields
            if ($model->type === 'Product') {
                $this->updateInventory($model, $fields);
            }
        });
        
        // Listen to login events
        Event::listen(\Aura\Base\Events\LoggedIn::class, function ($event) {
            $user = $event->user;
            
            // Track user login
            activity()
                ->performedOn($user)
                ->log('User logged in');
        });
    }
    
    protected function updateInventory($product, $fields)
    {
        // Your inventory logic
    }
}
```

### Dispatching Events

```php
namespace Acme\Inventory\Events;

use Illuminate\Foundation\Events\Dispatchable;

class StockLevelChanged
{
    use Dispatchable;
    
    public function __construct(
        public $product,
        public $oldStock,
        public $newStock
    ) {}
}

// Dispatch the event
StockLevelChanged::dispatch($product, $oldStock, $newStock);
```

> 📹 **Video Placeholder**: [Working with hooks and events - practical examples of extending Aura functionality]

## Testing Plugins

### Setting Up Tests

```php
// tests/TestCase.php
namespace Acme\Blog\Tests;

use Orchestra\Testbench\TestCase as Orchestra;
use Aura\Base\AuraServiceProvider;
use Acme\Blog\BlogServiceProvider;

class TestCase extends Orchestra
{
    protected function getPackageProviders($app)
    {
        return [
            AuraServiceProvider::class,
            BlogServiceProvider::class,
        ];
    }
    
    protected function defineEnvironment($app)
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('aura.teams', false);
    }
    
    protected function defineDatabaseMigrations()
    {
        $this->loadMigrationsFrom(__DIR__.'/../vendor/aura/base/database/migrations');
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');
    }
}
```

### Writing Tests

```php
// tests/Feature/BlogPostTest.php
namespace Acme\Blog\Tests\Feature;

use Acme\Blog\Tests\TestCase;
use Acme\Blog\Resources\Post;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

class BlogPostTest extends TestCase
{
    use RefreshDatabase;
    
    public function test_can_create_blog_post()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        $post = Post::create([
            'title' => 'Test Post',
            'content' => 'Test content',
            'author_id' => $user->id,
        ]);
        
        $this->assertDatabaseHas('posts', [
            'title' => 'Test Post',
            'type' => 'BlogPost',
        ]);
    }
    
    public function test_blog_post_requires_title()
    {
        $user = User::factory()->create();
        
        $this->actingAs($user);
        
        $response = $this->post(route('aura.blog-post.create'), [
            'content' => 'Test content',
        ]);
        
        $response->assertSessionHasErrors('title');
    }
    
    public function test_can_view_blog_posts()
    {
        Post::factory()->count(5)->create();
        
        $response = $this->get(route('blog.index'));
        
        $response->assertOk();
        $response->assertViewHas('posts');
    }
}
```

### Testing Custom Fields

```php
public function test_color_picker_field_saves_value()
{
    $field = new ColorPicker();
    
    $processedValue = $field->set(null, ['slug' => 'color'], '#FF0000');
    
    $this->assertEquals('#FF0000', $processedValue);
}

public function test_color_picker_field_validates_hex_format()
{
    $this->expectException(ValidationException::class);
    
    $field = new ColorPicker();
    $field->set(null, ['slug' => 'color'], 'invalid-color');
}
```

## Distribution

### Preparing for Distribution

1. **Documentation**
   ```markdown
   # My Plugin
   
   Description of what the plugin does.
   
   ## Installation
   ```bash
   composer require vendor/plugin
   ```
   
   ## Configuration
   
   Publish the config file:
   ```bash
   php artisan vendor:publish --tag=plugin-config
   ```
   ```

2. **Version Tags**
   ```bash
   git tag -a v1.0.0 -m "Initial release"
   git push origin v1.0.0
   ```

3. **Composer Package**
   ```json
   {
       "name": "vendor/plugin",
       "description": "Plugin description",
       "keywords": ["aura-cms", "plugin", "laravel"],
       "license": "MIT",
       "authors": [{
           "name": "Your Name",
           "email": "email@example.com"
       }],
       "require": {
           "php": "^8.2",
           "aura/base": "^1.0|^2.0",
           "laravel/framework": "^10.0|^11.0|^12.0",
           "spatie/laravel-package-tools": "^1.14.0"
       },
       "autoload": {
           "psr-4": {
               "Vendor\\Plugin\\": "src/"
           }
       },
       "extra": {
           "laravel": {
               "providers": [
                   "Vendor\\Plugin\\PluginServiceProvider"
               ]
           }
       }
   }
   ```

### Publishing to Packagist

1. Create account on [packagist.org](https://packagist.org)
2. Submit your package URL
3. Set up webhook for auto-updates
4. Add badges to README:
   ```markdown
   [![Latest Version](https://img.shields.io/packagist/v/vendor/plugin.svg)](https://packagist.org/packages/vendor/plugin)
   [![Total Downloads](https://img.shields.io/packagist/dt/vendor/plugin.svg)](https://packagist.org/packages/vendor/plugin)
   ```

### Installation Instructions

```bash
# Install via Composer
composer require vendor/plugin

# Publish assets (if needed)
php artisan vendor:publish --provider="Vendor\Plugin\PluginServiceProvider"

# Run migrations (if needed)
php artisan migrate

# Clear cache
php artisan aura:clear
```

## Best Practices

### Code Organization

1. **Follow PSR Standards**
   - PSR-4 for autoloading
   - PSR-12 for coding style
   - Use PHP-CS-Fixer for consistency

2. **Namespace Everything**
   ```php
   namespace Vendor\Plugin\Resources;
   namespace Vendor\Plugin\Fields;
   namespace Vendor\Plugin\Widgets;
   ```

3. **Use Type Declarations**
   ```php
   public function process(array $data): ProcessedResult
   {
       // Type-safe code
   }
   ```

### Performance

1. **Lazy Loading**
   ```php
   public function boot()
   {
       // Only load routes if needed
       if ($this->app->runningInConsole()) {
           return;
       }
       
       $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
   }
   ```

2. **Cache Expensive Operations**
   ```php
   public function getStatistics(): array
   {
       return Cache::remember('plugin.stats', 3600, function () {
           return $this->calculateStatistics();
       });
   }
   ```

3. **Use Database Indexes**
   ```php
   Schema::table('products', function (Blueprint $table) {
       $table->index('sku');
       $table->index(['category_id', 'is_active']);
   });
   ```

### Security

1. **Validate All Input**
   ```php
   public function rules(): array
   {
       return [
           'name' => 'required|string|max:255',
           'email' => 'required|email|unique:users',
           'price' => 'required|numeric|min:0',
       ];
   }
   ```

2. **Use Policies**
   ```php
   public function viewAny(User $user): bool
   {
       return $user->can('view-products');
   }
   ```

3. **Sanitize Output**
   ```blade
   {{-- Always escape output --}}
   {{ $product->description }}
   
   {{-- Only use unescaped for trusted HTML --}}
   {!! $product->trusted_html !!}
   ```

### Compatibility

1. **Version Constraints**
   ```json
    "require": {
        "php": "^8.2",
        "aura/base": "^1.0|^2.0",
        "laravel/framework": "^10.0|^11.0|^12.0",
        "spatie/laravel-package-tools": "^1.14.0"
    }
   ```

2. **Feature Detection**
   ```php
   if (method_exists(Aura::class, 'registerWidgets')) {
       Aura::registerWidgets($this->widgets);
   }
   ```

3. **Graceful Degradation**
   ```php
   try {
       $this->publishAdvancedFeatures();
   } catch (\Exception $e) {
       logger()->warning('Advanced features not available', [
           'error' => $e->getMessage()
       ]);
   }
   ```

## Example Plugins

### E-Commerce Plugin

```php
namespace Acme\Commerce;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Aura\Base\Facades\Aura;

class CommerceServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('commerce')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations([
                'create_products_table',
                'create_orders_table',
                'create_order_items_table',
            ]);
    }
    
    public function packageBooted()
    {
        Aura::registerResources([
            Resources\Product::class,
            Resources\Order::class,
            Resources\Customer::class,
        ]);
        
        Aura::registerFields([
            Fields\PriceField::class,
            Fields\StockField::class,
        ]);
        
        Aura::registerWidgets([
            Widgets\SalesChart::class,
            Widgets\RecentOrders::class,
        ]);
        
        // Add menu items
        app('aura.hooks')->addHook('navigation.after', function ($nav) {
            $nav[] = [
                'name' => 'Commerce',
                'icon' => 'shopping-cart',
                'children' => [
                    ['name' => 'Products', 'route' => 'aura.product.index'],
                    ['name' => 'Orders', 'route' => 'aura.order.index'],
                    ['name' => 'Customers', 'route' => 'aura.customer.index'],
                ],
            ];
            return $nav;
        });
    }
}
```

### SEO Plugin

```php
namespace Acme\Seo;

class SeoServiceProvider extends PackageServiceProvider
{
    public function packageBooted()
    {
        // Add SEO fields to all resources
        app('aura.hooks')->addHook('resource.fields', function ($fields, $resource) {
            if (!in_array($resource->type, ['Attachment', 'User'])) {
                $fields[] = [
                    'name' => 'SEO',
                    'type' => 'Aura\\Base\\Fields\\Panel',
                    'slug' => 'seo-panel',
                ];
                
                $fields[] = [
                    'name' => 'Meta Title',
                    'type' => 'Aura\\Base\\Fields\\Text',
                    'slug' => 'meta_title',
                    'validation' => 'max:60',
                    'instructions' => 'Recommended: 50-60 characters',
                ];
                
                $fields[] = [
                    'name' => 'Meta Description',
                    'type' => 'Aura\\Base\\Fields\\Textarea',
                    'slug' => 'meta_description',
                    'validation' => 'max:160',
                    'instructions' => 'Recommended: 150-160 characters',
                ];
            }
            
            return $fields;
        });
        
        // Inject SEO tags into head
        Aura::registerInjectView('head', function () {
            return view('acme-seo::meta-tags');
        });
    }
}
```

> 📹 **Video Placeholder**: [Building a complete plugin from scratch - showing real-world example with all features]

### Pro Tips

1. **Use Aura's Built-in Components**: Leverage existing fields and UI components
2. **Follow Laravel Conventions**: Use Laravel's patterns for familiarity
3. **Document Everything**: Include inline docs and README
4. **Test Thoroughly**: Include unit and feature tests
5. **Version Carefully**: Use semantic versioning
6. **Consider Teams**: Support both team and non-team installations
7. **Optimize Assets**: Minimize JS/CSS for production
8. **Provide Migrations**: Always include rollback methods

The plugin system provides unlimited possibilities for extending Aura CMS while maintaining clean architecture and ensuring compatibility with future updates.