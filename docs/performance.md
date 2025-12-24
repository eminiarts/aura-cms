# Performance Optimization

Aura CMS is built with performance in mind, utilizing multi-layered caching, optimized database queries, and background job processing. This guide covers the actual caching strategies, database optimization, and performance patterns used in Aura CMS.

## Table of Contents

1. [Introduction](#introduction)
2. [Caching Strategies](#caching-strategies)
3. [Database Optimization](#database-optimization)
4. [Query Optimization](#query-optimization)
5. [Asset Optimization](#asset-optimization)
6. [Queue Configuration](#queue-configuration)
7. [Livewire Performance](#livewire-performance)
8. [Media Optimization](#media-optimization)
9. [Server Configuration](#server-configuration)
10. [Monitoring & Profiling](#monitoring--profiling)
11. [Best Practices](#best-practices)

## Introduction

Performance optimization in Aura CMS involves multiple layers:

- **Application Level**: Multi-layered caching (Laravel Cache, static arrays, instance properties), eager loading, field caching
- **Database Level**: Optimized indexes for team-scoped queries, meta table joins
- **Background Processing**: Queued jobs for thumbnail generation and permission creation
- **Frontend Level**: Asset optimization with Vite, lazy loading

## Caching Strategies

Aura CMS implements a multi-layered caching strategy combining Laravel's Cache facade, static arrays, and instance properties for optimal performance.

### Options Caching

Options are automatically cached for 1 hour with team-scoped cache keys:

```php
// Automatic caching in Aura::getOption() - src/Aura.php
public function getOption($name)
{
    // Team-scoped cache key when teams are enabled
    if (config('aura.teams') && optional(optional(auth()->user())->resource)->currentTeam) {
        return Cache::remember(
            auth()->user()->current_team_id . '.aura.' . $name,
            now()->addHour(),
            function () use ($name) {
                return auth()->user()->currentTeam->getOption($name);
            }
        );
    }

    // Global cache key when teams are disabled
    return Cache::remember('aura.' . $name, now()->addHour(), function () use ($name) {
        $option = Option::where('name', $name)->first();
        return $option ? json_decode($option->value, true) : [];
    });
}

// Update option (cache is NOT automatically cleared - clear manually if needed)
public function updateOption($key, $value)
{
    if (config('aura.teams')) {
        auth()->user()->currentTeam->updateOption($key, $value);
    } else {
        Option::withoutGlobalScopes([app(TeamScope::class)])
            ->updateOrCreate(['name' => $key], ['value' => $value]);
    }
}
```

### Navigation Caching

Navigation is automatically cached per user and team for 1 hour:

```php
// Automatic caching in Aura::navigation() - src/Aura.php
public function navigation()
{
    return Cache::remember(
        'user-' . auth()->id() . '-' . auth()->user()->current_team_id . '-navigation',
        3600,
        function () {
            // Filters resources by permission and builds navigation structure
            $resources = collect($this->getResources())
                ->filter(fn ($resource) => auth()->user()->can('viewAny', app($resource)))
                ->map(fn ($r) => app($r)->navigation())
                ->filter(fn ($r) => $r['showInNavigation'] ?? true)
                ->sortBy('sort');
            
            return collect($resources)->groupBy('group');
        }
    );
}
```

### Field Caching

Aura CMS uses multiple static caches to avoid recomputing field definitions:

```php
// Static array caching in InputFieldsHelpers trait - src/Traits/InputFieldsHelpers.php
trait InputFieldsHelpers
{
    protected static $fieldClassesBySlug = [];
    protected static $fieldsBySlug = [];
    protected static $fieldsCollectionCache = [];
    protected static $inputFieldSlugs = [];
    protected static $mappedFields = [];

    public function fieldsCollection()
    {
        $class = get_class($this);
        
        if (isset(self::$fieldsCollectionCache[$class])) {
            return self::$fieldsCollectionCache[$class];
        }
        
        self::$fieldsCollectionCache[$class] = collect($this->getFields());
        return self::$fieldsCollectionCache[$class];
    }

    public function fieldBySlug($slug)
    {
        $key = get_class($this) . '-' . $slug;
        
        if (isset(self::$fieldsBySlug[$key])) {
            return self::$fieldsBySlug[$key];
        }
        
        $result = $this->fieldsCollection()->firstWhere('slug', $slug);
        self::$fieldsBySlug[$key] = $result;
        
        return $result;
    }
}
```

### Instance Property Caching

Resources cache processed fields per instance:

```php
// In Resource.php - src/Resource.php
public $fieldsAttributeCache;

public function getFieldsAttribute()
{
    if (!isset($this->fieldsAttributeCache) || $this->fieldsAttributeCache === null) {
        $this->fieldsAttributeCache = collect($this->getFieldsWithoutConditionalLogic())
            ->filter(fn ($value, $key) => $this->shouldDisplayField($key));
    }
    
    return $this->fieldsAttributeCache;
}

// Clear cache when model is saved
public function clearFieldsAttributeCache()
{
    $this->fieldsAttributeCache = null;
    
    if ($this->usesMeta()) {
        $this->load('meta');
    }
}
```

### Team Scope Caching

The current team ID is cached indefinitely to avoid repeated database queries:

```php
// In TeamScope.php - src/Models/Scopes/TeamScope.php
private function getCurrentTeamId()
{
    if (!Auth::check()) {
        return null;
    }

    $userId = Auth::id();
    $cacheKey = "user_{$userId}_current_team_id";

    return Cache::rememberForever($cacheKey, function () use ($userId) {
        return DB::table('users')->where('id', $userId)->value('current_team_id');
    });
}
```

### User Data Caching

User-specific data is cached for 1 hour:

```php
// In User.php - src/Resources/User.php
public function getCacheKeyForRoles()
{
    return auth()->user()->current_team_id . '.user.' . $this->id . '.roles';
}

public function getRolesAttribute()
{
    return Cache::remember($this->getCacheKeyForRoles(), now()->addMinutes(60), function () {
        return $this->roles()->get();
    });
}

// Cache bookmarks, sidebar state, columns, etc.
public function getBookmarks()
{
    return Cache::remember('user.' . $this->id . '.bookmarks', now()->addHour(), function () {
        return $this->getUserOption('bookmarks') ?? [];
    });
}
```

### Resource Caching Example

Cache expensive resource operations:

```php
class ProductResource extends Resource
{
    public function getCategoriesOptions()
    {
        return Cache::remember('product-categories', 3600, function () {
            return Category::pluck('name', 'id')->toArray();
        });
    }
}
```

### Table Row Caching

The `CachedRows` trait provides optional caching for table data:

```php
// In CachedRows trait - src/Livewire/Table/Traits/CachedRows.php
trait CachedRows
{
    protected $useCache = false;

    public function useCachedRows()
    {
        $this->useCache = true;
    }

    protected function cache(callable $callback)
    {
        $cacheKey = $this->id;

        if ($this->useCache && cache()->has($cacheKey)) {
            return cache()->get($cacheKey);
        }

        $result = $callback();
        cache()->put($cacheKey, $result);

        return $result;
    }
}
```

### Clearing All Cache

Use the Aura facade to clear all caches:

```php
// Clear all cache and routes
Aura::clear();

// Clear only conditional logic cache
Aura::clearConditionsCache();
```

### Recommended Cache Configuration

For production, use Redis for better performance:

```php
// .env
CACHE_DRIVER=redis

// config/cache.php
'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
],
```

## Database Optimization

### Built-in Indexes

Aura CMS includes optimized indexes out of the box in the migration stub (`database/migrations/create_aura_tables.php.stub`):

```php
// Posts table indexes (team-aware)
Schema::create('posts', function (Blueprint $table) {
    $table->id();
    $table->text('title')->nullable();
    $table->longText('content')->nullable();
    $table->string('type', 20);
    $table->string('status', 20)->default('publish')->nullable();
    $table->string('slug')->index()->nullable();
    $table->foreignId('user_id')->nullable()->index();
    $table->foreignId('parent_id')->nullable()->index();
    
    if (config('aura.teams')) {
        $table->foreignId('team_id')->nullable();
        $table->index(['team_id', 'type']); // Team-scoped type queries
    } else {
        $table->index(['type', 'status', 'created_at', 'id']); // Non-team queries
    }
});

// Meta table indexes - optimized for key-value lookups
Schema::create('meta', function (Blueprint $table) {
    $table->id();
    $table->morphs('metable');
    $table->string('key')->nullable()->index();
    $table->longText('value')->nullable();
    
    $table->index(['metable_type', 'metable_id', 'key']);
});

// MySQL-specific index for meta value searches
if (config('database.default') === 'mysql') {
    DB::statement('CREATE INDEX idx_meta_metable_id_key_value ON meta (metable_id, `key`, value(255));');
}

// Post relations table
Schema::create('post_relations', function (Blueprint $table) {
    $table->morphs('resource');
    $table->morphs('related');
    $table->integer('order')->nullable();
    $table->string('slug')->nullable();
    
    $table->index(['resource_id', 'related_id', 'related_type']);
    $table->index('slug');
});
```

### Custom Indexes

Add indexes for your specific queries:

```php
// In your migration
public function up()
{
    Schema::table('products', function (Blueprint $table) {
        // For filtering by category and status
        $table->index(['category_id', 'status']);
        
        // For price range queries
        $table->index(['price', 'status']);
        
        // For full-text search
        $table->fullText(['name', 'description']);
    });
}
```

### Migration to Custom Tables

For better performance with large datasets, migrate from posts/meta to custom tables:

```bash
# Create a new resource with custom table support
php artisan aura:resource Product --custom

# Migrate existing resource from posts table to custom table
php artisan aura:migrate-from-posts-to-custom-table
```

This interactive command will:
1. Ask which resource to migrate
2. Generate the migration file
3. Modify the resource class to set `$customTable = true`
4. Optionally run the migration
5. Optionally transfer existing data

Custom table benefits:
- Direct column access (no JSON parsing)
- Better indexing capabilities
- Improved query performance
- Type-safe columns

### Resource Configuration for Custom Tables

```php
class Product extends Resource
{
    // Enable custom table mode
    public static $customTable = true;
    
    // Specify table name
    protected $table = 'products';
    
    // Disable meta storage (optional)
    public static bool $usesMeta = false;
}
```

## Query Optimization

### Eager Loading

Aura CMS automatically eager loads the `meta` relationship when `usesMeta()` returns true:

```php
// In Resource.php constructor - src/Resource.php
public function __construct(array $attributes = [])
{
    parent::__construct($attributes);
    
    if ($this->usesMeta()) {
        $this->with[] = 'meta';  // Automatically eager load meta
    }
}
```

Use the `indexQuery` method to add custom eager loading for table views:

```php
// In your Resource class
public function indexQuery($query)
{
    return $query->with(['category', 'tags', 'author']);
}
```

The Table component automatically calls `indexQuery` if it exists:

```php
// In Table.php - src/Livewire/Table/Table.php
if (method_exists($this->model, 'indexQuery')) {
    $query = $this->model->indexQuery($query, $this);
}
```

### Meta Field Query Scopes

Aura CMS provides optimized scopes for querying meta fields (in `AuraModelConfig` trait):

```php
// Simple meta field query
$posts = Post::whereMeta('color', 'blue')->get();

// With operator
$posts = Post::whereMeta('price', '>', 100)->get();

// Multiple conditions (AND)
$posts = Post::whereMeta(['color' => 'blue', 'size' => 'large'])->get();

// OR conditions
$posts = Post::orWhereMeta('color', 'red')->get();

// IN query
$posts = Post::whereInMeta('status', ['active', 'pending'])->get();

// NOT IN query
$posts = Post::whereNotInMeta('status', ['archived', 'deleted'])->get();

// JSON contains (for array meta values)
$posts = Post::whereMetaContains('tags', 'featured')->get();
```

### Join Optimization for Sorting

The table component uses efficient left joins for sorting on meta fields:

```php
// In Sorting trait - src/Livewire/Table/Traits/Sorting.php
$query->leftJoin('meta', function ($join) use ($field) {
    $join->on('posts.id', '=', 'meta.metable_id')
         ->where('meta.metable_type', '=', $this->model->getMorphClass())
         ->where('meta.key', '=', $field);
});
```

### Chunking and Cursors

For large datasets, use Laravel's built-in chunking:

```php
// Process in chunks to avoid memory issues
Product::chunk(100, function ($products) {
    foreach ($products as $product) {
        ProcessProduct::dispatch($product);
    }
});

// Or use cursor for streaming results
foreach (Product::cursor() as $product) {
    // Process one at a time - minimal memory usage
}
```

## Asset Optimization

### Vite Configuration

Optimize assets with Vite:

```javascript
// vite.config.js
import { defineConfig } from 'vite';
import laravel from 'laravel-vite-plugin';
import { compression } from 'vite-plugin-compression2';

export default defineConfig({
    build: {
        sourcemap: false, // Disable in production
        rollupOptions: {
            output: {
                manualChunks: {
                    'vendor': ['alpinejs', '@alpinejs/focus'],
                    'editor': ['monaco-editor'],
                },
                assetFileNames: (assetInfo) => {
                    let extType = assetInfo.name.split('.').at(1);
                    if (/png|jpe?g|svg|gif|tiff|bmp|ico/i.test(extType)) {
                        extType = 'img';
                    }
                    return `assets/${extType}/[name]-[hash][extname]`;
                },
            },
        },
        chunkSizeWarningLimit: 1000,
    },
    plugins: [
        laravel({
            input: ['resources/css/app.css', 'resources/js/app.js'],
            refresh: true,
        }),
        compression({
            algorithm: 'gzip',
            ext: '.gz',
        }),
        compression({
            algorithm: 'brotliCompress',
            ext: '.br',
        }),
    ],
});
```

### CSS Optimization

Optimize Tailwind CSS:

```javascript
// tailwind.config.js
module.exports = {
    content: [
        './resources/**/*.blade.php',
        './resources/**/*.js',
        './src/**/*.php',
    ],
    theme: {
        extend: {},
    },
    plugins: [],
    // Production optimizations
    ...(process.env.NODE_ENV === 'production' ? {
        cssnano: {
            preset: ['default', {
                discardComments: {
                    removeAll: true,
                },
            }],
        },
    } : {}),
};
```

### JavaScript Optimization

Lazy load heavy components:

```javascript
// Lazy load Monaco Editor
const loadMonaco = () => import('./monaco-editor');

// Lazy load charts
const loadCharts = () => import('./charts');

// Only load when needed
document.addEventListener('alpine:init', () => {
    Alpine.data('codeEditor', () => ({
        async init() {
            const { initMonaco } = await loadMonaco();
            initMonaco(this.$refs.editor);
        }
    }));
});
```

### CDN Integration

Use CDN for static assets:

```php
// config/app.php
'asset_url' => env('ASSET_URL', null),

// .env
ASSET_URL=https://cdn.yourdomain.com

// In blade views
<img src="{{ asset('images/logo.png') }}" alt="Logo">
```

## Queue Configuration

Aura CMS uses Laravel's queue system for background processing of thumbnails and permissions.

### Built-in Queue Jobs

Aura CMS includes three queue jobs (in `src/Jobs/`):

**1. GenerateImageThumbnail** - Generates multiple thumbnail sizes for uploaded images:

```php
// src/Jobs/GenerateImageThumbnail.php
class GenerateImageThumbnail implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle(ThumbnailGenerator $thumbnailGenerator)
    {
        $settings = Aura::option('media');
        
        if (!$settings || !($settings['generate_thumbnails'] ?? false)) {
            return;
        }

        foreach ($settings['dimensions'] as $thumbnail) {
            $thumbnailGenerator->generate(
                $this->attachment->fields['url'],
                $thumbnail['width'],
                $thumbnail['height'] ?? null
            );
        }
    }
}
```

**2. GenerateResourcePermissions** - Creates CRUD permissions for a resource:

```php
// src/Jobs/GenerateResourcePermissions.php
class GenerateResourcePermissions implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function handle()
    {
        $r = app($this->resource);
        
        // Creates: view, viewAny, create, update, restore, delete, forceDelete, scope
        Permission::firstOrCreate(
            ['slug' => 'view-' . $r::$slug],
            ['name' => 'View ' . $r->pluralName(), 'group' => $r->pluralName()]
        );
        // ... other permissions
    }
}
```

**3. GenerateAllResourcePermissions** - Generates permissions for all resources (synchronous):

```php
// src/Jobs/GenerateAllResourcePermissions.php - runs synchronously
class GenerateAllResourcePermissions
{
    public function handle()
    {
        DB::transaction(function () use ($resources) {
            foreach ($resources as $resource) {
                $this->generatePermissionsForResource(app($resource));
            }
        });
    }
}
```

### Queue Configuration

For production, use Redis:

```php
// .env
QUEUE_CONNECTION=redis

// config/queue.php
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => 90,
    'block_for' => null,
],
```

### Running Queue Workers

```bash
# Development
php artisan queue:work

# Production with Supervisor
[program:aura-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/logs/worker.log
```

## Livewire Performance

### Component Optimization

Optimize Livewire components:

```php
class ProductTable extends Component
{
    // Use lazy loading
    public $readyToLoad = false;
    
    public function loadProducts()
    {
        $this->readyToLoad = true;
    }
    
    // Use computed properties
    #[Computed]
    public function products()
    {
        if (!$this->readyToLoad) {
            return collect();
        }
        
        return Cache::remember('products-table-' . $this->getCacheKey(), 300, function () {
            return Product::with(['category', 'tags'])
                ->filter($this->filters)
                ->paginate($this->perPage);
        });
    }
    
    // Defer expensive operations
    public function render()
    {
        return view('livewire.product-table', [
            'products' => $this->products,
        ]);
    }
}
```

### Wire:init for Lazy Loading

Use wire:init for deferred loading:

```blade
<div wire:init="loadData">
    @if($loaded)
        <!-- Heavy content -->
        @foreach($products as $product)
            <x-product-card :product="$product" />
        @endforeach
    @else
        <x-loading-skeleton />
    @endif
</div>
```

### Pagination Optimization

Optimize pagination:

```php
use Livewire\WithPagination;

class ProductList extends Component
{
    use WithPagination;
    
    protected $paginationTheme = 'tailwind';
    
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    public function render()
    {
        return view('livewire.product-list', [
            'products' => Product::search($this->search)
                ->paginate(20)
                ->onEachSide(1), // Limit pagination links
        ]);
    }
}
```

### Event Debouncing

Debounce user input:

```blade
<!-- Debounce search input -->
<input 
    type="search"
    wire:model.live.debounce.500ms="search"
    placeholder="Search products..."
>

<!-- Lazy update on blur -->
<input 
    type="text"
    wire:model.blur="name"
    placeholder="Product name"
>
```

## Media Optimization

### Image Processing Configuration

Configure image optimization in `config/aura.php`:

```php
// Actual configuration in config/aura.php
'media' => [
    'disk' => 'public',           // Storage disk to use
    'path' => 'media',            // Upload path
    'quality' => 80,              // JPEG quality (1-100)
    'restrict_to_dimensions' => true,  // Only allow configured sizes
    'max_file_size' => 10000,     // Max file size in KB
    'generate_thumbnails' => true, // Enable thumbnail generation
    'dimensions' => [
        ['name' => 'xs', 'width' => 200],
        ['name' => 'sm', 'width' => 600],
        ['name' => 'md', 'width' => 1200],
        ['name' => 'lg', 'width' => 2000],
        ['name' => 'thumbnail', 'width' => 600, 'height' => 600],
    ],
],
```

### Thumbnail Generator

The `ThumbnailGenerator` service creates optimized thumbnails on-demand:

```php
// src/Services/ThumbnailGenerator.php
class ThumbnailGenerator
{
    public function generate(string $path, int $width, ?int $height = null): string
    {
        $quality = Config::get('aura.media.quality', 80) / 100;
        
        // Validate dimensions if restricted
        if (Config::get('aura.media.restrict_to_dimensions', true)) {
            // Only allow configured dimension combinations
        }
        
        // Skip if thumbnail already exists (caching)
        if (Storage::disk('public')->exists($thumbnailPath)) {
            return $thumbnailPath;
        }
        
        // Don't upscale images
        if ($width > $originalWidth) {
            return $path;
        }
        
        // Generate and save thumbnail
        $image->resize($width, null, fn ($constraint) => $constraint->aspectRatio());
        $image->encode('jpg', $quality * 100);
        Storage::disk('public')->put($thumbnailPath, (string) $image);
        
        return $thumbnailPath;
    }
}
```

### Thumbnail Naming Convention

Thumbnails are stored in a `thumbnails/` subdirectory with predictable names:

```
Original: media/images/photo.jpg
Thumbnails:
  - thumbnails/media/images/600_auto_photo.jpg  (width only)
  - thumbnails/media/images/600_600_photo.jpg   (width + height)
```

### Storage Optimization

For production, consider using S3 or another cloud storage:

```php
// config/filesystems.php
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'options' => [
            'CacheControl' => 'max-age=31536000, public',
        ],
    ],
],

// Then update config/aura.php
'media' => [
    'disk' => 's3',
    // ...
],
```

## Server Configuration

### PHP Configuration

Optimize PHP settings:

```ini
; php.ini optimizations
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.revalidate_freq=60
opcache.fast_shutdown=1
opcache.enable_cli=1
opcache.jit=1255
opcache.jit_buffer_size=128M

; Memory and execution
memory_limit=256M
max_execution_time=30
max_input_time=60

; File uploads
upload_max_filesize=20M
post_max_size=25M
```

### Nginx Configuration

Optimize Nginx:

```nginx
# nginx.conf
http {
    # Gzip compression
    gzip on;
    gzip_vary on;
    gzip_min_length 1024;
    gzip_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
    
    # Brotli compression
    brotli on;
    brotli_comp_level 6;
    brotli_types text/plain text/css text/xml text/javascript application/javascript application/xml+rss application/json;
    
    # Connection settings
    keepalive_timeout 65;
    keepalive_requests 100;
    
    # Buffer sizes
    client_body_buffer_size 128k;
    client_max_body_size 20m;
    client_header_buffer_size 1k;
    large_client_header_buffers 4 16k;
    
    # Cache static files
    location ~* \.(jpg|jpeg|png|gif|ico|css|js|pdf|txt)$ {
        expires 1y;
        add_header Cache-Control "public, immutable";
    }
    
    # Security headers
    add_header X-Frame-Options "SAMEORIGIN" always;
    add_header X-Content-Type-Options "nosniff" always;
    add_header X-XSS-Protection "1; mode=block" always;
}
```

### Redis Configuration

Optimize Redis:

```conf
# redis.conf
maxmemory 2gb
maxmemory-policy allkeys-lru
save ""
stop-writes-on-bgsave-error no
rdbcompression no
rdbchecksum no
```

## Monitoring & Profiling

### Laravel Telescope

Install and configure Telescope for development:

```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

Configure in `app/Providers/TelescopeServiceProvider.php`:

```php
public function gate()
{
    Gate::define('viewTelescope', function ($user) {
        return in_array($user->email, [
            'admin@example.com',
        ]);
    });
}

protected function hideSensitiveRequestDetails()
{
    if ($this->app->environment('local')) {
        return;
    }

    Telescope::hideRequestParameters(['_token']);
    Telescope::hideRequestHeaders([
        'cookie',
        'x-csrf-token',
        'x-xsrf-token',
    ]);
}
```

### Query Monitoring

Monitor slow queries:

```php
// AppServiceProvider
public function boot()
{
    if (config('app.debug')) {
        DB::listen(function ($query) {
            if ($query->time > 100) {
                Log::warning('Slow query detected', [
                    'sql' => $query->sql,
                    'bindings' => $query->bindings,
                    'time' => $query->time,
                ]);
            }
        });
    }
}
```

### Custom Performance Metrics

Track custom metrics:

```php
class PerformanceTracker
{
    public static function track($operation, Closure $callback)
    {
        $start = microtime(true);
        
        try {
            $result = $callback();
            
            $duration = microtime(true) - $start;
            
            if ($duration > 0.5) { // Log operations over 500ms
                Log::channel('performance')->info("Slow operation: {$operation}", [
                    'duration' => $duration,
                    'memory' => memory_get_peak_usage(true) / 1024 / 1024,
                ]);
            }
            
            return $result;
        } catch (\Exception $e) {
            Log::error("Operation failed: {$operation}", [
                'error' => $e->getMessage(),
            ]);
            throw $e;
        }
    }
}

// Usage
$products = PerformanceTracker::track('load-products', function () {
    return Product::with(['category', 'tags'])->get();
});
```

### APM Integration

Integrate Application Performance Monitoring:

```php
// New Relic integration
if (extension_loaded('newrelic')) {
    newrelic_set_appname(config('app.name'));
    
    // Track custom events
    newrelic_record_custom_event('ProductView', [
        'productId' => $product->id,
        'userId' => auth()->id(),
        'timestamp' => now()->timestamp,
    ]);
}

// Sentry Performance
\Sentry\configureScope(function (\Sentry\State\Scope $scope): void {
    $scope->setContext('performance', [
        'memory_usage' => memory_get_usage(true),
        'peak_memory' => memory_get_peak_usage(true),
        'cpu_usage' => sys_getloadavg()[0],
    ]);
});
```

## Best Practices

### 1. Use Aura's Built-in Caching

Aura CMS caches automatically. Leverage it:

```php
// Options are cached automatically - just use them
$settings = Aura::getOption('media');

// Navigation is cached per user/team
$nav = Aura::navigation();

// Clear all caches when needed
Aura::clear();
```

### 2. Implement indexQuery for Eager Loading

```php
// In your Resource class
class Product extends Resource
{
    public function indexQuery($query)
    {
        return $query->with(['category', 'tags', 'images']);
    }
}
```

### 3. Use Meta Query Scopes

```php
// Use optimized scopes instead of raw queries
$products = Product::whereMeta('status', 'active')
    ->whereInMeta('category', [1, 2, 3])
    ->get();
```

### 4. Migrate to Custom Tables for Large Datasets

```bash
# When posts/meta table gets too large
php artisan aura:migrate-from-posts-to-custom-table
```

### 5. Queue Heavy Operations

```php
// Thumbnails are automatically queued
// For custom jobs:
ProcessLargeImport::dispatch($file)->onQueue('imports');
```

### 6. Enable Thumbnail Restrictions

```php
// config/aura.php - prevent arbitrary dimension attacks
'media' => [
    'restrict_to_dimensions' => true,
    'dimensions' => [
        ['name' => 'sm', 'width' => 600],
        ['name' => 'md', 'width' => 1200],
    ],
],
```

### 7. Optimize Autoloader

```bash
# Production optimization
composer dump-autoload -o
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

### 8. Use Redis for Cache and Queues

```env
CACHE_DRIVER=redis
QUEUE_CONNECTION=redis
SESSION_DRIVER=redis
```

### 9. Monitor Slow Queries

```php
// In AppServiceProvider
public function boot()
{
    if (config('app.debug')) {
        DB::listen(function ($query) {
            if ($query->time > 100) {
                Log::warning('Slow query', [
                    'sql' => $query->sql,
                    'time' => $query->time,
                ]);
            }
        });
    }
}
```

### 10. Clear Cache Strategically

```php
// Clear specific caches, not everything
Cache::forget('user.' . $user->id . '.roles');
Cache::forget($user->current_team_id . '.aura.settings');

// Or clear all when needed
Aura::clear();
```

## Conclusion

Aura CMS includes many performance optimizations out of the box:

1. **Multi-layered caching** - Options, navigation, fields, and user data are cached automatically
2. **Optimized indexes** - Team-scoped queries and meta lookups are indexed
3. **Eager loading** - Meta relationships are loaded automatically
4. **Background jobs** - Thumbnails and permissions are processed asynchronously
5. **Query scopes** - Efficient meta field querying with dedicated scopes

For production:
- Use Redis for caching and queues
- Enable Laravel's built-in caching (`config:cache`, `route:cache`)
- Consider custom tables for high-volume resources
- Monitor slow queries and optimize as needed

For additional performance resources, see [Laravel Performance](https://laravel.com/docs/optimization) and [Livewire Performance](https://livewire.laravel.com/docs/performance).