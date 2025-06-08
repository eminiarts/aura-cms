# Performance Optimization

Aura CMS is built with performance in mind, offering numerous optimization strategies and techniques to ensure your applications run efficiently at scale. This guide covers caching strategies, database optimization, asset management, and monitoring tools.

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
11. [Performance Testing](#performance-testing)
12. [Best Practices](#best-practices)

## Introduction

Performance optimization in Aura CMS involves multiple layers:

- **Application Level**: Caching, query optimization, lazy loading
- **Database Level**: Indexes, query optimization, connection pooling
- **Frontend Level**: Asset optimization, lazy loading, CDN usage
- **Server Level**: PHP OPcache, Redis/Memcached, HTTP/2

### Performance Goals

- Page load times under 200ms
- Database queries under 50ms
- Asset delivery under 100ms
- Concurrent user support: 1000+ active users
- Memory usage under 128MB per request

## Caching Strategies

### Application Caching

Aura CMS provides multiple caching layers:

#### Options Caching

Options are automatically cached for 1 hour:

```php
// Automatic caching in Aura::getOption()
public function getOption($name)
{
    $cacheKey = $this->getCacheKey('aura.' . $name);
    
    return Cache::remember($cacheKey, 3600, function () use ($name) {
        return Option::where('name', $name)->first()?->value;
    });
}

// Clear option cache when updated
public function setOption($name, $value)
{
    Option::updateOrCreate(['name' => $name], ['value' => $value]);
    Cache::forget($this->getCacheKey('aura.' . $name));
}
```

#### Navigation Caching

Navigation is cached per user and team:

```php
// In your AppServiceProvider
public function boot()
{
    // Cache navigation for 1 hour
    Aura::cacheNavigation(3600);
    
    // Or implement custom caching
    Aura::navigationUsing(function () {
        $cacheKey = 'nav-' . auth()->id() . '-' . auth()->user()->current_team_id;
        
        return Cache::remember($cacheKey, 3600, function () {
            return Aura::buildNavigation();
        });
    });
}
```

#### Resource Caching

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
    
    public function getStatsProperty()
    {
        return Cache::remember('product-stats-' . $this->id, 300, function () {
            return [
                'views' => $this->views()->count(),
                'sales' => $this->orders()->sum('quantity'),
                'revenue' => $this->orders()->sum('total'),
            ];
        });
    }
}
```

### View Caching

Cache rendered views for static content:

```php
// Cache entire pages
Route::middleware('cache.headers:public;max_age=3600')->group(function () {
    Route::get('/about', AboutController::class);
    Route::get('/terms', TermsController::class);
});

// Cache view fragments
@cache('product-sidebar', 3600)
    <x-product-categories :categories="$categories" />
    <x-popular-products :limit="10" />
@endcache
```

### Query Result Caching

Cache database query results:

```php
// Simple query caching
$popularProducts = Cache::remember('popular-products', 3600, function () {
    return Product::with(['category', 'images'])
        ->where('status', 'active')
        ->orderBy('sales_count', 'desc')
        ->limit(10)
        ->get();
});

// Tagged caching for easy invalidation
$products = Cache::tags(['products', 'inventory'])->remember('all-products', 3600, function () {
    return Product::with('category')->get();
});

// Clear tagged cache
Cache::tags(['products'])->flush();
```

### Cache Configuration

Configure caching in `config/cache.php`:

```php
'default' => env('CACHE_DRIVER', 'redis'),

'stores' => [
    'redis' => [
        'driver' => 'redis',
        'connection' => 'cache',
        'lock_connection' => 'default',
    ],
    
    'array' => [
        'driver' => 'array',
        'serialize' => false,
    ],
],

// Redis configuration for better performance
'redis' => [
    'client' => env('REDIS_CLIENT', 'predis'),
    'default' => [
        'host' => env('REDIS_HOST', '127.0.0.1'),
        'password' => env('REDIS_PASSWORD'),
        'port' => env('REDIS_PORT', 6379),
        'database' => env('REDIS_DB', 0),
        'pool' => [
            'min_connections' => 1,
            'max_connections' => 10,
            'max_idle_time' => 60,
        ],
    ],
],
```

## Database Optimization

### Indexes

Aura CMS includes optimized indexes out of the box:

```php
// Composite indexes for efficient queries
Schema::create('posts', function (Blueprint $table) {
    // ... columns
    
    // Team-based queries
    $table->index(['team_id', 'type']);
    
    // Status and sorting
    $table->index(['type', 'status', 'created_at', 'id']);
    
    // Slug lookups
    $table->unique(['team_id', 'type', 'slug']);
});

// Meta table indexes
Schema::create('meta', function (Blueprint $table) {
    // ... columns
    
    // Efficient meta lookups
    $table->index(['metable_type', 'metable_id', 'key']);
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

### Database Connection Pooling

Configure connection pooling:

```php
// config/database.php
'mysql' => [
    'driver' => 'mysql',
    'host' => env('DB_HOST', '127.0.0.1'),
    'port' => env('DB_PORT', '3306'),
    'database' => env('DB_DATABASE', 'forge'),
    'username' => env('DB_USERNAME', 'forge'),
    'password' => env('DB_PASSWORD', ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'prefix' => '',
    'prefix_indexes' => true,
    'strict' => true,
    'engine' => null,
    'options' => extension_loaded('pdo_mysql') ? array_filter([
        PDO::MYSQL_ATTR_SSL_CA => env('MYSQL_ATTR_SSL_CA'),
        PDO::ATTR_PERSISTENT => true, // Enable persistent connections
        PDO::ATTR_EMULATE_PREPARES => false, // Use real prepared statements
    ]) : [],
],
```

### Migration to Custom Tables

For better performance with large datasets, migrate from posts/meta to custom tables:

```bash
# Generate migration for custom table
php artisan aura:resource Product --custom-table

# Migrate existing data
php artisan aura:migrate-to-custom products
```

Custom table benefits:
- Direct column access (no JSON parsing)
- Better indexing capabilities
- Improved query performance
- Type-safe columns

## Query Optimization

### Eager Loading

Prevent N+1 queries with eager loading:

```php
// In your Resource
public function indexQuery($query)
{
    return $query->with(['category', 'tags', 'author']);
}

// Or define default eager loading
protected $with = ['category', 'meta'];

// Conditional eager loading
public function scopeWithAll($query)
{
    return $query->with(['category', 'tags', 'images', 'reviews']);
}
```

### Query Scoping

Limit data retrieval with scopes:

```php
// Resource-specific scopes
public function scopeActive($query)
{
    return $query->where('status', 'active');
}

public function scopeForTable($query)
{
    return $query->select(['id', 'name', 'price', 'status', 'created_at']);
}

// Usage
$products = Product::active()->forTable()->paginate(20);
```

### Chunking Large Datasets

Process large datasets efficiently:

```php
// Process in chunks to avoid memory issues
Product::chunk(100, function ($products) {
    foreach ($products as $product) {
        // Process each product
        ProcessProduct::dispatch($product);
    }
});

// Or use cursor for even better memory efficiency
foreach (Product::cursor() as $product) {
    // Process one at a time
}
```

### Raw Queries for Complex Operations

Use raw queries when necessary:

```php
// Complex aggregation
$stats = DB::select("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as total_orders,
        SUM(total) as revenue,
        AVG(total) as avg_order_value
    FROM orders
    WHERE created_at >= ?
    GROUP BY DATE(created_at)
", [now()->subDays(30)]);

// Bulk updates
DB::update("
    UPDATE products 
    SET status = 'inactive' 
    WHERE last_sold_at < ? AND stock = 0
", [now()->subMonths(6)]);
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

### Queue Driver Selection

Choose the right queue driver:

```php
// .env for production
QUEUE_CONNECTION=redis
REDIS_QUEUE=default

// config/queue.php
'redis' => [
    'driver' => 'redis',
    'connection' => 'default',
    'queue' => env('REDIS_QUEUE', 'default'),
    'retry_after' => 90,
    'block_for' => null,
    'after_commit' => false,
],
```

### Job Optimization

Optimize job processing:

```php
// Dispatch jobs efficiently
class ProcessProductImport implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;
    
    public $timeout = 3600; // 1 hour for large imports
    public $tries = 3;
    public $backoff = [60, 300, 900]; // Exponential backoff
    
    public function handle()
    {
        // Process in chunks
        $this->importFile->chunkById(100, function ($records) {
            foreach ($records as $record) {
                ProcessSingleProduct::dispatch($record)->onQueue('imports');
            }
        });
    }
    
    public function failed(Throwable $exception)
    {
        // Notify user of failure
        $this->user->notify(new ImportFailedNotification($exception));
    }
}
```

### Queue Workers

Configure queue workers:

```bash
# Supervisor configuration
[program:aura-worker]
process_name=%(program_name)s_%(process_num)02d
command=php /path/to/artisan queue:work redis --sleep=3 --tries=3 --max-time=3600
autostart=true
autorestart=true
stopasgroup=true
killasgroup=true
user=www-data
numprocs=4
redirect_stderr=true
stdout_logfile=/path/to/logs/worker.log
stopwaitsecs=3600
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

### Image Processing

Configure image optimization:

```php
// config/aura.php
'media' => [
    'quality' => 80, // JPEG quality
    'format' => 'webp', // Modern format
    'dimensions' => [
        ['name' => 'thumbnail', 'width' => 150, 'height' => 150],
        ['name' => 'small', 'width' => 300],
        ['name' => 'medium', 'width' => 600],
        ['name' => 'large', 'width' => 1200],
    ],
    'lazy_loading' => true,
    'responsive' => true,
],
```

### Lazy Loading Images

Implement lazy loading:

```blade
<img 
    src="{{ $product->image->url('thumbnail') }}"
    data-src="{{ $product->image->url('large') }}"
    loading="lazy"
    class="lazyload"
    alt="{{ $product->name }}"
>

<!-- With responsive images -->
<picture>
    <source 
        type="image/webp"
        srcset="{{ $image->url('small') }} 300w,
                {{ $image->url('medium') }} 600w,
                {{ $image->url('large') }} 1200w"
        sizes="(max-width: 640px) 100vw, (max-width: 1024px) 50vw, 33vw"
    >
    <img 
        src="{{ $image->url('medium') }}"
        loading="lazy"
        alt="{{ $alt }}"
    >
</picture>
```

### Storage Optimization

Optimize file storage:

```php
// Use S3 for better performance
'disks' => [
    's3' => [
        'driver' => 's3',
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION'),
        'bucket' => env('AWS_BUCKET'),
        'url' => env('AWS_URL'),
        'endpoint' => env('AWS_ENDPOINT'),
        'use_path_style_endpoint' => env('AWS_USE_PATH_STYLE_ENDPOINT', false),
        'options' => [
            'CacheControl' => 'max-age=31536000, public',
        ],
    ],
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

## Performance Testing

### Load Testing

Test application performance:

```php
// tests/Performance/LoadTest.php
test('homepage loads under 200ms', function () {
    $times = [];
    
    for ($i = 0; $i < 10; $i++) {
        $start = microtime(true);
        $response = $this->get('/');
        $times[] = microtime(true) - $start;
    }
    
    $average = array_sum($times) / count($times);
    
    expect($average)->toBeLessThan(0.2); // 200ms
});

test('handles concurrent users', function () {
    $responses = collect(range(1, 100))->map(function () {
        return Http::async()->get(config('app.url'));
    });
    
    $results = Http::pool(fn ($pool) => $responses);
    
    $successRate = collect($results)
        ->filter(fn ($response) => $response->ok())
        ->count() / 100;
    
    expect($successRate)->toBeGreaterThan(0.95); // 95% success rate
});
```

### Database Performance Testing

```php
test('product query performs well with large dataset', function () {
    // Seed large dataset
    Product::factory()->count(10000)->create();
    
    $start = microtime(true);
    
    $products = Product::with(['category', 'tags'])
        ->where('status', 'active')
        ->orderBy('created_at', 'desc')
        ->paginate(20);
    
    $duration = microtime(true) - $start;
    
    expect($duration)->toBeLessThan(0.1); // 100ms
    expect(count(DB::getQueryLog()))->toBeLessThan(5); // Avoid N+1
});
```

## Best Practices

### 1. Cache Everything

```php
// Cache expensive operations
public function getExpensiveData()
{
    return Cache::remember('expensive-data', 3600, function () {
        return $this->calculateExpensiveData();
    });
}

// Use tagged cache for related data
Cache::tags(['products', 'inventory'])->remember('key', 3600, $callback);
```

### 2. Optimize Queries

```php
// Bad - N+1 query
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name; // Extra query per post
}

// Good - Eager loading
$posts = Post::with('author')->get();
foreach ($posts as $post) {
    echo $post->author->name; // No extra queries
}
```

### 3. Use Indexes Wisely

```php
// Add indexes for commonly queried columns
Schema::table('products', function (Blueprint $table) {
    $table->index(['category_id', 'status', 'created_at']);
});
```

### 4. Implement Pagination

```php
// Always paginate large datasets
$products = Product::paginate(20); // Not ->get() or ->all()
```

### 5. Queue Heavy Operations

```php
// Don't process in request
ProcessLargeImport::dispatch($file)->onQueue('imports');
```

### 6. Monitor Performance

```php
// Add monitoring to critical paths
$timer = app('performance.timer');
$timer->start('checkout-process');

// ... checkout logic ...

$timer->stop('checkout-process');
```

### 7. Optimize Assets

```javascript
// Lazy load components
const HeavyComponent = () => import('./HeavyComponent.vue');

// Use image optimization
import imagemin from 'imagemin';
```

### 8. Database Connection Pooling

```php
// Use persistent connections
'options' => [
    PDO::ATTR_PERSISTENT => true,
];
```

### 9. Use CDN

```nginx
# Serve static assets from CDN
location ~* \.(css|js|jpg|jpeg|png|gif|ico|woff|woff2)$ {
    return 301 https://cdn.example.com$request_uri;
}
```

### 10. Profile Regularly

```bash
# Use Blackfire or XHProf
blackfire run php artisan tinker
```

> 📹 **Video Placeholder**: [Performance profiling walkthrough showing how to identify and fix bottlenecks in Aura CMS]

## Pro Tips

1. **Use Read Replicas**: Distribute read queries to replica databases
2. **Implement HTTP/2**: Enable server push for critical assets
3. **Use Object Cache**: Cache Eloquent models in Redis
4. **Optimize Autoloader**: Use `composer dump-autoload -o`
5. **Enable Query Cache**: MySQL query cache for repeated queries
6. **Use Job Batching**: Process multiple jobs efficiently
7. **Implement ETags**: For efficient HTTP caching
8. **Use Compression**: Enable Gzip/Brotli for all text assets
9. **Optimize Images**: Use WebP format with fallbacks
10. **Monitor Everything**: Set up alerts for performance degradation

## Conclusion

Performance optimization is an ongoing process. Start with the basics:

1. Enable caching (Redis recommended)
2. Add database indexes
3. Implement eager loading
4. Use queues for heavy tasks
5. Monitor and profile regularly

Remember that premature optimization is the root of all evil. Measure first, optimize second, and always test the impact of your changes.

For additional performance resources, see [Laravel Performance](https://laravel.com/docs/performance) and [Livewire Performance](https://livewire.laravel.com/docs/performance).