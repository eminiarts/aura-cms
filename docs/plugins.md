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
- **Service Provider**: Register functionality with Aura
- **Isolated**: Each plugin has its own namespace and dependencies
- **Testable**: Full testing support with PHPUnit/Pest

> 📹 **Video Placeholder**: [Overview of Aura plugin system showing plugin creation, installation, and integration with the CMS]

## Plugin Architecture

### Directory Structure

```
plugins/
└── vendor-name/
    └── plugin-name/
        ├── src/
        │   ├── PluginNameServiceProvider.php
        │   ├── Resources/
        │   ├── Fields/
        │   └── Widgets/
        ├── resources/
        │   └── views/
        ├── config/
        │   └── plugin-name.php
        ├── database/
        │   └── migrations/
        ├── tests/
        ├── composer.json
        └── README.md
```

### Registration Flow

1. **Composer Autoloading**: Plugin classes are autoloaded via PSR-4
2. **Service Provider**: Registered in `config/app.php`
3. **Boot Method**: Plugin functionality registered with Aura
4. **Runtime**: Plugin features available throughout application

## Creating Plugins

### Using the Plugin Generator

Aura provides an Artisan command to scaffold new plugins:

```bash
php artisan aura:plugin vendor/plugin-name
```

The generator offers four plugin types:
1. **Complete Plugin**: Full-featured plugin with all capabilities
2. **Resource Plugin**: Adds new resource types
3. **Field Plugin**: Adds new field types
4. **Widget Plugin**: Adds dashboard widgets

### Step-by-Step Creation

```bash
# 1. Create plugin
php artisan aura:plugin acme/blog

# 2. Select plugin type
# > Complete plugin

# 3. Plugin created at plugins/acme/blog
# 4. Service provider added to config/app.php
# 5. Composer autoload updated
```

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
        "php": "^8.1"
    },
    "autoload": {
        "psr-4": {
            "Acme\\Blog\\": "src"
        }
    }
}
EOF

# Update main composer.json
composer config repositories.acme/blog path plugins/acme/blog
composer require acme/blog:@dev
```

## Plugin Types

### Complete Plugin

A full-featured plugin with all Aura integration points:

```php
namespace Acme\Blog;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;
use Aura\Base\Facades\Aura;

class BlogServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('blog')
            ->hasConfigFile()
            ->hasViews()
            ->hasMigrations([
                'create_blog_posts_table',
                'create_blog_categories_table',
            ])
            ->hasCommand(BlogCommand::class);
    }

    public function packageBooted()
    {
        // Register resources
        Aura::registerResources([
            Resources\Post::class,
            Resources\Category::class,
        ]);

        // Register fields
        Aura::registerFields([
            Fields\MarkdownEditor::class,
            Fields\TagSelector::class,
        ]);

        // Register widgets
        Aura::registerWidgets([
            Widgets\RecentPosts::class,
            Widgets\PopularPosts::class,
        ]);

        // Register routes
        $this->loadRoutesFrom(__DIR__.'/../routes/web.php');
    }
}
```

### Resource Plugin

Adds new resource types to Aura CMS:

```php
namespace Acme\Products;

use Aura\Base\Facades\Aura;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class ProductsServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('products')
            ->hasViews('acme-products');

        // Register the Product resource
        Aura::registerResources([
            \Acme\Products\Product::class,
        ]);
    }
}
```

Product Resource example:

```php
namespace Acme\Products;

use Aura\Base\Resource;

class Product extends Resource
{
    public static string $type = 'Product';
    
    public static ?string $slug = 'product';
    
    public static ?string $name = 'Products';
    
    public static ?string $singularName = 'Product';
    
    public static ?string $icon = 'shopping-cart';
    
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
                'prefix' => '$',
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
                'use_media_manager' => true,
                'max_files' => 10,
            ],
        ];
    }
}
```

### Field Plugin

Creates custom field types:

```php
namespace Acme\ColorPicker;

use Aura\Base\Fields\Field;

class ColorPicker extends Field
{
    public $edit = 'acme-colorpicker::fields.color-picker';
    
    public $view = 'acme-colorpicker::fields.color-picker-view';
    
    public $optionGroup = 'Custom Fields';
    
    public function get($class, $value, $field = null)
    {
        return $value ?? '#000000';
    }
    
    public function set($post, $field, $value)
    {
        return $value;
    }
    
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Default Color',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'default_color',
                'validation' => 'required|regex:/^#[0-9A-F]{6}$/i',
                'instructions' => 'Default color in hex format (e.g., #FF0000)',
            ],
            [
                'name' => 'Color Palette',
                'type' => 'Aura\\Base\\Fields\\Select',
                'slug' => 'palette',
                'options' => [
                    'default' => 'Default Palette',
                    'material' => 'Material Design',
                    'tailwind' => 'Tailwind CSS',
                ],
            ],
        ]);
    }
}
```

Field view (`resources/views/fields/color-picker.blade.php`):

```blade
<div x-data="colorPicker(@entangle($field['slug']))" class="relative">
    <label class="aura-label">{{ $field['name'] }}</label>
    
    <div class="flex items-center space-x-2">
        <input 
            type="text" 
            x-model="color"
            @input="updateColor"
            class="aura-input"
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

<script>
function colorPicker(value) {
    return {
        color: value || '#000000',
        showPicker: false,
        updateColor() {
            this.$wire.set('{{ $field['slug'] }}', this.color);
        }
    }
}
</script>
```

### Widget Plugin

Adds dashboard widgets:

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

> 📹 **Video Placeholder**: [Creating different types of plugins - resource, field, and widget plugins with live examples]

## Plugin Development

### Using Aura APIs

```php
use Aura\Base\Facades\Aura;

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

// Access configuration
$mediaSettings = Aura::option('media');
$siteTitle = Aura::option('general')['site_title'] ?? 'My Site';

// Get resources
$resources = Aura::getResources();
$productResource = Aura::findResourceBySlug('product');

// Inject views
Aura::registerInjectView('dashboard.footer', function () {
    return view('my-plugin::partials.dashboard-footer');
});
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
           "php": "^8.1",
           "aura/base": "^1.0"
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
       "aura/base": "^1.0|^2.0",
       "laravel/framework": "^10.0|^11.0"
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