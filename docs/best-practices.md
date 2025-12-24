# Best Practices & Patterns

This guide covers coding standards, design patterns, security best practices, and scalability patterns for developing with Aura CMS.

## Table of Contents

1. [Coding Standards](#coding-standards)
2. [Design Patterns](#design-patterns)
3. [Resource Development](#resource-development)
4. [Field Development](#field-development)
5. [Livewire Components](#livewire-components)
6. [Database Design](#database-design)
7. [Security Best Practices](#security-best-practices)
8. [Performance Patterns](#performance-patterns)
9. [Code Organization](#code-organization)
10. [Testing Practices](#testing-practices)
11. [Scalability Patterns](#scalability-patterns)
12. [Common Patterns](#common-patterns)
13. [Common Gotchas](#common-gotchas)
14. [Pro Tips](#pro-tips)

## Coding Standards

### PHP Standards

Aura CMS follows Laravel's coding standards with PSR-12 compliance:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Product extends Resource
{
    // 1. Use traits first
    use HasFactory;
    
    // 2. Public constants
    public const STATUS_ACTIVE = 'active';
    public const STATUS_INACTIVE = 'inactive';
    
    // 3. Protected/private constants (alphabetically within group)
    protected const CACHE_TTL = 3600;
    
    // 4. Public properties (static, then instance)
    public static $customTable = true;
    
    public static ?string $slug = 'product';
    
    public static string $type = 'Product';
    
    // 5. Protected properties
    protected static ?string $group = 'Shop';
    
    protected static array $searchable = ['name', 'description'];
    
    protected $fillable = ['name', 'price', 'status'];
    
    protected $table = 'products';
    
    // 6. Private properties
    private array $cache = [];
    
    // 7. Constructor
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    }
    
    // 8. Magic methods (__get, __set, __call, etc.)
    
    // 9. Public methods (alphabetically sorted by Pint)
    public static function getFields(): array
    {
        return [
            // Field definitions using array syntax
        ];
    }
    
    public function getIcon()
    {
        return '<svg>...</svg>';
    }
    
    public function title()
    {
        return $this->name ?? '';
    }
    
    // 10. Protected methods (alphabetically)
    protected function calculatePrice(): float
    {
        return $this->base_price * (1 + $this->tax_rate);
    }
    
    // 11. Private methods (alphabetically)
    private function clearCache(): void
    {
        $this->cache = [];
    }
}
```

> **Note**: Pint automatically sorts methods alphabetically within each visibility group. Run `composer format` to apply formatting.

### Laravel Pint Configuration

Aura CMS uses Laravel Pint for code formatting:

```json
{
    "preset": "laravel",
    "exclude": ["build"],
    "rules": {
        "simplified_null_return": true,
        "ordered_class_elements": {
            "order": [
                "use_trait",
                "constant_public",
                "constant_protected",
                "constant_private",
                "property_public",
                "property_protected",
                "property_private",
                "construct",
                "destruct",
                "magic",
                "phpunit",
                "method_public",
                "method_protected",
                "method_private"
            ],
            "sort_algorithm": "alpha"
        }
    }
}
```

Run formatting:
```bash
./vendor/bin/pint
./vendor/bin/pint --test # Check without fixing
```

### Naming Conventions

```php
// Classes - PascalCase
class ProductResource extends Resource

// Methods - camelCase
public function getActiveProducts()

// Variables - camelCase
$productCount = Product::count();

// Constants - UPPER_SNAKE_CASE
const MAX_UPLOAD_SIZE = 10240;

// Database columns - snake_case
$table->string('product_name');

// Routes - kebab-case
Route::get('/product-categories', [ProductController::class, 'categories']);

// Blade files - kebab-case
resources/views/products/create-form.blade.php
```

## Design Patterns

### Repository Pattern (Optional)

While Aura CMS uses Eloquent directly, you can implement repositories for complex business logic:

```php
// app/Repositories/ProductRepository.php
namespace App\Repositories;

use App\Models\Product;
use Illuminate\Support\Collection;

class ProductRepository
{
    public function __construct(
        private Product $model
    ) {}
    
    public function findActive(): Collection
    {
        return $this->model
            ->where('status', Product::STATUS_ACTIVE)
            ->with(['category', 'tags'])
            ->orderBy('name')
            ->get();
    }
    
    public function findByCategory(int $categoryId): Collection
    {
        return $this->model
            ->where('category_id', $categoryId)
            ->where('status', Product::STATUS_ACTIVE)
            ->get();
    }
}

// In your resource or service
class ProductService
{
    public function __construct(
        private ProductRepository $repository
    ) {}
    
    public function getActiveProducts(): Collection
    {
        return Cache::remember('active-products', 3600, function () {
            return $this->repository->findActive();
        });
    }
}
```

### Service Pattern

Encapsulate business logic in service classes:

```php
// app/Services/OrderService.php
namespace App\Services;

use App\Models\Order;
use App\Models\Product;
use App\Notifications\OrderConfirmation;
use Illuminate\Support\Facades\DB;

class OrderService
{
    public function createOrder(array $data): Order
    {
        return DB::transaction(function () use ($data) {
            // Create order
            $order = Order::create([
                'user_id' => auth()->id(),
                'status' => Order::STATUS_PENDING,
                'total' => 0,
            ]);
            
            // Add items
            $total = 0;
            foreach ($data['items'] as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                $order->items()->create([
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'price' => $product->price,
                ]);
                
                $total += $product->price * $item['quantity'];
                
                // Update stock
                $product->decrement('stock', $item['quantity']);
            }
            
            // Update total
            $order->update(['total' => $total]);
            
            // Send notification
            $order->user->notify(new OrderConfirmation($order));
            
            return $order->fresh();
        });
    }
}
```

### Action Classes

For single-responsibility operations:

```php
// app/Actions/PublishProduct.php
namespace App\Actions;

use App\Models\Product;
use App\Events\ProductPublished;
use App\Jobs\GenerateProductThumbnails;

class PublishProduct
{
    public function execute(Product $product): Product
    {
        // Validate product is ready
        if (!$product->hasRequiredFields()) {
            throw new \Exception('Product missing required fields');
        }
        
        // Update status
        $product->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        // Dispatch jobs
        GenerateProductThumbnails::dispatch($product);
        
        // Fire event
        event(new ProductPublished($product));
        
        // Clear caches
        Cache::tags(['products'])->flush();
        
        return $product->fresh();
    }
    
    private function hasRequiredFields(): bool
    {
        return $this->name 
            && $this->price 
            && $this->description 
            && $this->images->isNotEmpty();
    }
}
```

### Observer Pattern

Use Eloquent observers for model events:

```php
// app/Observers/ProductObserver.php
namespace App\Observers;

use App\Models\Product;
use Illuminate\Support\Facades\Cache;

class ProductObserver
{
    public function created(Product $product): void
    {
        // Generate SKU
        if (!$product->sku) {
            $product->update(['sku' => $this->generateSku($product)]);
        }
        
        // Clear cache
        $this->clearCache();
    }
    
    public function updated(Product $product): void
    {
        // Log price changes
        if ($product->isDirty('price')) {
            $product->priceHistory()->create([
                'old_price' => $product->getOriginal('price'),
                'new_price' => $product->price,
                'changed_by' => auth()->id(),
            ]);
        }
        
        $this->clearCache();
    }
    
    public function deleted(Product $product): void
    {
        // Clean up relationships
        $product->images()->delete();
        $product->reviews()->delete();
        
        $this->clearCache();
    }
    
    private function clearCache(): void
    {
        Cache::tags(['products'])->flush();
    }
}

// Register in AppServiceProvider
Product::observe(ProductObserver::class);
```

### Pipeline Pattern

Aura CMS uses pipelines for field processing:

```php
// app/Pipeline/ValidateProductData.php
namespace App\Pipeline;

use Closure;

class ValidateProductData
{
    public function handle($product, Closure $next)
    {
        // Validate data
        if ($product->price < 0) {
            throw new \InvalidArgumentException('Price cannot be negative');
        }
        
        if (strlen($product->name) < 3) {
            throw new \InvalidArgumentException('Name too short');
        }
        
        return $next($product);
    }
}

// Usage
use Illuminate\Pipeline\Pipeline;

$product = app(Pipeline::class)
    ->send($productData)
    ->through([
        ValidateProductData::class,
        SanitizeProductData::class,
        EnrichProductData::class,
    ])
    ->thenReturn();
```

## Resource Development

### Resource Structure

Follow this structure for resources. Aura CMS uses array-based field definitions with fully qualified class names:

```php
namespace App\Aura\Resources;

use Aura\Base\Resource;

class Product extends Resource
{
    // Use traits first (enforced by Pint)
    
    // Public static properties
    public static ?string $slug = 'product';
    
    public static ?int $sort = 10;
    
    public static string $type = 'Product';
    
    // Protected static properties
    protected static ?string $group = 'Shop';
    
    protected static array $searchable = ['name', 'description', 'sku'];
    
    // Protected properties
    protected $hidden = ['password'];
    
    public static function getFields(): array
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Tab',
                'name' => 'Details',
                'slug' => 'tab-details',
                'global' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Product Info',
                'slug' => 'product-info',
                'style' => [
                    'width' => '70',
                ],
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Name',
                'slug' => 'name',
                'validation' => 'required|min:3|max:255',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Number',
                'name' => 'Price',
                'slug' => 'price',
                'validation' => 'required|numeric|min:0',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Select',
                'name' => 'Status',
                'slug' => 'status',
                'options' => [
                    'draft' => 'Draft',
                    'active' => 'Active',
                    'inactive' => 'Inactive',
                ],
                'validation' => 'required|in:draft,active,inactive',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Panel',
                'name' => 'Sidebar',
                'slug' => 'sidebar',
                'style' => [
                    'width' => '30',
                ],
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Tags',
                'name' => 'Tags',
                'slug' => 'tags',
                'resource' => 'Aura\\Base\\Resources\\Tag',
                'create' => true,
                'validation' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\BelongsTo',
                'name' => 'Category',
                'slug' => 'category_id',
                'resource' => 'App\\Aura\\Resources\\Category',
                'validation' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
            ],
        ];
    }
    
    public function indexQuery($query)
    {
        return $query->with(['category', 'tags']);
    }
    
    public function getIcon()
    {
        return '<svg>...</svg>';
    }
    
    public function title()
    {
        return $this->name ?? '';
    }
}
```

### Custom Table Resources

For better performance with large datasets, use custom tables instead of the shared `posts` table with meta:

```php
class Product extends Resource
{
    // Enable custom table mode
    public static $customTable = true;
    
    // Optionally disable meta fields if all data is in columns
    public static bool $usesMeta = false;
    
    public static ?string $slug = 'product';
    
    public static string $type = 'Product';
    
    protected $table = 'products';
    
    protected $fillable = [
        'name',
        'slug',
        'price',
        'description',
        'status',
        'featured',
        'category_id',
        'stock',
        'sku',
        'team_id',
        'user_id',
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'featured' => 'boolean',
    ];
    
    // Fields map directly to database columns
    public static function getFields(): array
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Name',
                'slug' => 'name',
                'validation' => 'required|max:255',
                'on_index' => true,
                'searchable' => true,
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Number',
                'name' => 'Price',
                'slug' => 'price',
                'validation' => 'required|numeric|min:0',
                'on_index' => true,
            ],
            // ... more fields
        ];
    }
}
```

## Field Development

### Custom Field Best Practices

```php
namespace App\Fields;

use Aura\Base\Fields\Field;

class ColorPicker extends Field
{
    public $component = 'fields.color-picker';
    
    protected string $default = '#000000';
    
    protected array $swatches = [];
    
    public function mount()
    {
        $this->swatches = config('aura.color_swatches', []);
    }
    
    public function swatches(array $colors): static
    {
        $this->swatches = $colors;
        return $this;
    }
    
    public function get($value)
    {
        // Transform stored value for display
        return $value ?: $this->default;
    }
    
    public function set($value)
    {
        // Transform input for storage
        return strtoupper($value);
    }
    
    public function getValidationRules(): array
    {
        return array_merge(parent::getValidationRules(), [
            'regex:/^#[0-9A-F]{6}$/i',
        ]);
    }
    
    public function getSearchableValue($model)
    {
        // Return null to exclude from search
        return null;
    }
}
```

### Field View Components

```blade
{{-- resources/views/fields/color-picker.blade.php --}}
<x-aura::fields.wrapper :field="$field" :model="$model ?? null">
    <div 
        x-data="colorPicker(@js($field), @entangle('form.fields.' . $field['slug']))"
        class="relative"
    >
        <div class="flex items-center space-x-2">
            <input
                type="text"
                x-model="value"
                @input="updateColor"
                class="form-input"
                placeholder="#000000"
                maxlength="7"
            >
            <div
                class="w-10 h-10 rounded border cursor-pointer"
                :style="`background-color: ${value}`"
                @click="showPicker = !showPicker"
            ></div>
        </div>
        
        {{-- Swatches --}}
        @if($field->swatches)
            <div class="flex flex-wrap gap-2 mt-2">
                @foreach($field->swatches as $color => $label)
                    <button
                        type="button"
                        @click="value = '{{ $color }}'"
                        class="w-8 h-8 rounded border"
                        style="background-color: {{ $color }}"
                        title="{{ $label }}"
                    ></button>
                @endforeach
            </div>
        @endif
    </div>
</x-aura::fields.wrapper>

@pushOnce('scripts')
<script>
    function colorPicker(field, value) {
        return {
            field: field,
            value: value || field.default || '#000000',
            showPicker: false,
            
            updateColor() {
                // Validate hex format
                if (/^#[0-9A-F]{6}$/i.test(this.value)) {
                    this.$wire.set(`form.fields.${field.slug}`, this.value);
                }
            }
        }
    }
</script>
@endPushOnce
```

## Livewire Components

### Component Best Practices

```php
namespace Aura\Base\Livewire;

use Aura\Base\Traits\WithLivewireHelpers;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Component;
use Livewire\WithPagination;

class ProductManager extends Component
{
    use AuthorizesRequests;
    use WithLivewireHelpers;
    use WithPagination;
    
    // Public properties for binding
    public $search = '';
    public $filters = [
        'status' => '',
        'category' => '',
    ];
    
    // Protected properties
    protected $queryString = [
        'search' => ['except' => ''],
        'filters' => ['except' => []],
    ];
    
    protected $listeners = [
        'productCreated' => '$refresh',
        'productDeleted' => '$refresh',
    ];
    
    // Validation rules
    protected $rules = [
        'search' => 'nullable|string|max:255',
        'filters.status' => 'nullable|in:active,inactive,draft',
        'filters.category' => 'nullable|exists:categories,id',
    ];
    
    // Lifecycle hooks
    public function mount()
    {
        $this->authorize('viewAny', Product::class);
    }
    
    // Real-time validation
    public function updated($propertyName)
    {
        $this->validateOnly($propertyName);
    }
    
    // Reset pagination on search
    public function updatingSearch()
    {
        $this->resetPage();
    }
    
    // Computed properties
    #[Computed]
    public function products()
    {
        return Product::query()
            ->when($this->search, fn($q) => $q->search($this->search))
            ->when($this->filters['status'], fn($q, $status) => 
                $q->where('status', $status)
            )
            ->when($this->filters['category'], fn($q, $category) => 
                $q->where('category_id', $category)
            )
            ->with(['category', 'tags'])
            ->latest()
            ->paginate(20);
    }
    
    // Actions
    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        
        $this->authorize('delete', $product);
        
        $product->delete();
        
        $this->notify('Product deleted successfully');
    }
    
    public function render()
    {
        return view('livewire.product-manager', [
            'products' => $this->products,
        ]);
    }
}
```

### Component View

```blade
<div>
    {{-- Filters --}}
    <div class="flex items-center space-x-4 mb-4">
        <x-aura::input.text
            wire:model.live.debounce.300ms="search"
            placeholder="Search products..."
            class="flex-1"
        />
        
        <x-aura::input.select
            wire:model.live="filters.status"
            :options="$statusOptions"
            placeholder="All Statuses"
        />
        
        <x-aura::input.select
            wire:model.live="filters.category"
            :options="$categoryOptions"
            placeholder="All Categories"
        />
    </div>
    
    {{-- Table --}}
    <x-aura::table>
        <x-slot name="head">
            <x-aura::table.heading>Name</x-aura::table.heading>
            <x-aura::table.heading>Price</x-aura::table.heading>
            <x-aura::table.heading>Status</x-aura::table.heading>
            <x-aura::table.heading>Actions</x-aura::table.heading>
        </x-slot>
        
        <x-slot name="body">
            @forelse($products as $product)
                <x-aura::table.row wire:key="product-{{ $product->id }}">
                    <x-aura::table.cell>{{ $product->name }}</x-aura::table.cell>
                    <x-aura::table.cell>${{ number_format($product->price, 2) }}</x-aura::table.cell>
                    <x-aura::table.cell>
                        <x-aura::badge :type="$product->status">
                            {{ $product->status }}
                        </x-aura::badge>
                    </x-aura::table.cell>
                    <x-aura::table.cell>
                        <x-aura::button.link href="{{ route('products.edit', $product) }}">
                            Edit
                        </x-aura::button.link>
                        <x-aura::button.link
                            wire:click="deleteProduct({{ $product->id }})"
                            wire:confirm="Are you sure?"
                            class="text-red-600"
                        >
                            Delete
                        </x-aura::button.link>
                    </x-aura::table.cell>
                </x-aura::table.row>
            @empty
                <x-aura::table.row>
                    <x-aura::table.cell colspan="4" class="text-center">
                        No products found
                    </x-aura::table.cell>
                </x-aura::table.row>
            @endforelse
        </x-slot>
    </x-aura::table>
    
    {{-- Pagination --}}
    {{ $products->links() }}
</div>
```

## Database Design

### Schema Best Practices

```php
// Good migration example
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('slug')->unique();
            $table->text('description')->nullable();
            $table->decimal('price', 10, 2);
            $table->decimal('cost', 10, 2)->default(0);
            $table->integer('stock')->default(0);
            $table->string('sku')->unique()->nullable();
            $table->enum('status', ['draft', 'active', 'inactive'])
                ->default('draft');
            $table->boolean('featured')->default(false);
            
            // Foreign keys
            $table->foreignId('category_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();
            
            $table->foreignId('user_id')
                ->constrained()
                ->cascadeOnDelete();
            
            // Team support
            if (config('aura.teams')) {
                $table->foreignId('team_id')
                    ->constrained()
                    ->cascadeOnDelete();
            }
            
            // Metadata
            $table->json('meta')->nullable();
            
            // Timestamps
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes for performance
            $table->index(['status', 'featured']);
            $table->index(['category_id', 'status']);
            $table->index('created_at');
            
            if (config('aura.teams')) {
                $table->index(['team_id', 'status']);
            }
            
            // Full-text search
            $table->fullText(['name', 'description']);
        });
    }
    
    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

### Model Best Practices

```php
namespace App\Models;

use Aura\Base\Traits\HasMeta;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Laravel\Scout\Searchable;

class Product extends Model
{
    use HasFactory;
    use HasMeta;
    use Searchable;
    use SoftDeletes;
    
    protected $fillable = [
        'name',
        'slug',
        'description',
        'price',
        'cost',
        'stock',
        'sku',
        'status',
        'featured',
        'category_id',
        'user_id',
        'team_id',
        'meta',
    ];
    
    protected $casts = [
        'price' => 'decimal:2',
        'cost' => 'decimal:2',
        'featured' => 'boolean',
        'meta' => 'array',
        'published_at' => 'datetime',
    ];
    
    protected $attributes = [
        'status' => 'draft',
        'featured' => false,
        'stock' => 0,
    ];
    
    // Relationships
    public function category()
    {
        return $this->belongsTo(Category::class);
    }
    
    public function reviews()
    {
        return $this->hasMany(Review::class);
    }
    
    public function tags()
    {
        return $this->belongsToMany(Tag::class);
    }
    
    // Scopes
    public function scopeActive($query)
    {
        return $query->where('status', 'active');
    }
    
    public function scopeFeatured($query)
    {
        return $query->where('featured', true);
    }
    
    public function scopeInStock($query)
    {
        return $query->where('stock', '>', 0);
    }
    
    // Accessors & Mutators
    public function getProfitAttribute()
    {
        return $this->price - $this->cost;
    }
    
    public function getProfitMarginAttribute()
    {
        if ($this->price == 0) return 0;
        return ($this->profit / $this->price) * 100;
    }
    
    // Methods
    public function isAvailable(): bool
    {
        return $this->status === 'active' && $this->stock > 0;
    }
    
    public function decrementStock(int $quantity = 1): bool
    {
        if ($this->stock < $quantity) {
            return false;
        }
        
        return $this->decrement('stock', $quantity);
    }
    
    // Scout search
    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'description' => $this->description,
            'sku' => $this->sku,
            'category' => $this->category?->name,
            'tags' => $this->tags->pluck('name')->implode(' '),
        ];
    }
}
```

## Security Best Practices

### Authorization

Always check permissions:

```php
// In Controllers
public function update(Request $request, Product $product)
{
    $this->authorize('update', $product);
    
    // Update logic
}

// In Livewire Components
public function mount($productId)
{
    $this->product = Product::findOrFail($productId);
    $this->authorize('view', $this->product);
}

// In Blade Views
@can('update', $product)
    <x-aura::button href="{{ route('products.edit', $product) }}">
        Edit
    </x-aura::button>
@endcan

// In Resources
public static function can($ability, $model = null)
{
    return auth()->user()->can($ability, $model ?? static::$model);
}
```

### Input Validation

```php
// Form Request
namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('product'));
    }
    
    public function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'slug' => 'required|string|max:255|unique:products,slug,' . $this->route('product')->id,
            'price' => 'required|numeric|min:0|max:999999.99',
            'description' => 'nullable|string|max:5000',
            'category_id' => 'nullable|exists:categories,id',
            'status' => 'required|in:draft,active,inactive',
            'featured' => 'boolean',
            'images' => 'array|max:10',
            'images.*' => 'image|max:5120', // 5MB
        ];
    }
    
    public function messages(): array
    {
        return [
            'price.min' => 'Price cannot be negative.',
            'images.*.max' => 'Each image must be less than 5MB.',
        ];
    }
    
    protected function prepareForValidation(): void
    {
        $this->merge([
            'slug' => Str::slug($this->slug ?: $this->name),
            'featured' => $this->boolean('featured'),
        ]);
    }
}
```

### XSS Prevention

```blade
{{-- Always escape output --}}
{{ $product->name }}

{{-- Only use unescaped for trusted content --}}
{!! $product->trusted_html_content !!}

{{-- Escape in JavaScript --}}
<script>
    const productName = @js($product->name);
    const productData = @json($product->toArray());
</script>

{{-- Escape in attributes --}}
<div title="{{ $product->description }}">
```

### SQL Injection Prevention

```php
// Always use parameter binding
$products = DB::select('SELECT * FROM products WHERE price > ?', [$minPrice]);

// Or use query builder
$products = DB::table('products')
    ->where('price', '>', $minPrice)
    ->get();

// Never do this
$products = DB::select("SELECT * FROM products WHERE price > $minPrice");

// Use whereIn safely
$ids = $request->collect('ids')->filter()->values();
$products = Product::whereIn('id', $ids)->get();
```

### File Upload Security

```php
// Validate file types and size
$request->validate([
    'document' => 'required|file|mimes:pdf,doc,docx|max:10240',
    'image' => 'required|image|dimensions:min_width=100,min_height=100|max:5120',
]);

// Store files securely
$path = $request->file('document')->store('documents', 'private');

// Generate secure download URLs
return Storage::disk('private')->temporaryUrl(
    $path,
    now()->addMinutes(5),
    ['ResponseContentDisposition' => 'attachment']
);
```

## Performance Patterns

### Query Optimization

```php
// Eager load relationships
$products = Product::with(['category', 'tags', 'images'])->get();

// Select only needed columns
$products = Product::select(['id', 'name', 'price', 'status'])->get();

// Use chunking for large operations
Product::chunk(100, function ($products) {
    foreach ($products as $product) {
        // Process product
    }
});

// Cache expensive queries
$categories = Cache::remember('categories', 3600, function () {
    return Category::with('children')->get();
});
```

### Lazy Loading Components

```php
// Livewire component
public $readyToLoad = false;

public function loadData()
{
    $this->readyToLoad = true;
}

public function getProductsProperty()
{
    if (!$this->readyToLoad) {
        return collect();
    }
    
    return Product::with('category')->paginate();
}
```

```blade
<div wire:init="loadData">
    @if($readyToLoad)
        @foreach($this->products as $product)
            {{-- Product display --}}
        @endforeach
    @else
        <x-aura::loading />
    @endif
</div>
```

## Code Organization

### Directory Structure

```
app/
├── Actions/              # Single-purpose action classes
├── Aura/
│   ├── Resources/       # Aura resources
│   └── Fields/          # Custom fields
├── Events/              # Custom events
├── Exceptions/          # Custom exceptions
├── Http/
│   ├── Controllers/     # HTTP controllers
│   ├── Livewire/       # Livewire components
│   ├── Middleware/     # Custom middleware
│   └── Requests/       # Form requests
├── Jobs/               # Queued jobs
├── Listeners/          # Event listeners
├── Mail/               # Mailable classes
├── Models/             # Eloquent models
├── Notifications/      # Notification classes
├── Observers/          # Model observers
├── Policies/           # Authorization policies
├── Providers/          # Service providers
├── Repositories/       # Repository classes (optional)
├── Rules/              # Custom validation rules
├── Services/           # Business logic services
└── Traits/             # Reusable traits
```

### Service Provider Organization

```php
namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class AuraCustomizationServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        // Register bindings
        $this->app->bind(ProductRepository::class, function ($app) {
            return new ProductRepository(new Product);
        });
    }
    
    public function boot(): void
    {
        // Boot customizations
        $this->bootResources();
        $this->bootFields();
        $this->bootMacros();
        $this->bootObservers();
    }
    
    private function bootResources(): void
    {
        // Register custom resources
        Aura::resources([
            Product::class,
            Category::class,
            Order::class,
        ]);
    }
    
    private function bootFields(): void
    {
        // Register custom fields
        Aura::fields([
            ColorPicker::class,
            PriceRange::class,
            LocationPicker::class,
        ]);
    }
    
    private function bootMacros(): void
    {
        // Add collection macros
        Collection::macro('formatCurrency', function () {
            return $this->map(function ($value) {
                return '$' . number_format($value, 2);
            });
        });
    }
    
    private function bootObservers(): void
    {
        Product::observe(ProductObserver::class);
        Order::observe(OrderObserver::class);
    }
}
```

## Testing Practices

### Test Organization

Aura CMS uses Pest for testing with helper functions defined in `tests/Pest.php`:

```php
<?php

use Aura\Base\Livewire\CreateResource;
use Aura\Base\Resources\User;

use function Pest\Livewire\livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('only superadmins can access this component', function () {
    livewire(CreateResource::class)
        ->assertOk();
});

test('user without role can not access component', function () {
    // Create User without super admin role
    $user = User::factory()->create();
    
    $this->actingAs($user);
    
    livewire(CreateResource::class)
        ->assertForbidden();
});

test('validation works correctly', function () {
    livewire(CreateResource::class)
        ->call('save')
        ->assertHasErrors(['form.fields.name' => 'required']);
});

test('can save with valid data', function () {
    livewire(CreateResource::class)
        ->set('form.fields.name', 'Test')
        ->call('save')
        ->assertHasNoErrors();
});
```

### Test Helper Functions

These helpers are defined in `tests/Pest.php`:

```php
// Creates a super admin user with team (for teams-enabled tests)
$user = createSuperAdmin();

// Creates a super admin without team context
$user = createSuperAdminWithoutTeam();

// Creates an admin user with limited permissions
$user = createAdmin();

// Creates a test post
$post = createPost(['title' => 'Test Post']);
```

### Livewire Component Testing

Use the `livewire()` function from Pest Livewire:

```php
use function Pest\Livewire\livewire;

test('component renders correctly', function () {
    livewire(ProductTable::class)
        ->assertOk()
        ->assertSee('Products');
});

test('can set properties and call methods', function () {
    livewire(EditResource::class, ['slug' => 'post', 'id' => 1])
        ->set('form.fields.title', 'Updated Title')
        ->call('save')
        ->assertHasNoErrors();
});

test('filters work correctly', function () {
    livewire(Table::class, ['slug' => 'post'])
        ->set('search', 'test')
        ->assertSet('search', 'test');
});
```

### Database Testing

- Feature tests automatically use `RefreshDatabase` trait
- Tests in `FeatureWithDatabaseMigrations/` use `DatabaseMigrations`
- Use factories for creating test data

```php
test('can create user with factory', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'test@example.com',
    ]);
    
    expect($user->name)->toBe('Test User');
});
```

### Test Groups

Run tests by group using Pest:

```bash
vendor/bin/pest --group=fields
vendor/bin/pest --group=flows
vendor/bin/pest --group=table
vendor/bin/pest --group=resource
```

## Scalability Patterns

### Horizontal Scaling

```php
// Use cache tags for easy invalidation
Cache::tags(['products', 'team-' . $teamId])->remember($key, 3600, $callback);

// Implement read/write splitting
config([
    'database.connections.mysql.read' => [
        'host' => [
            '192.168.1.1',
            '192.168.1.2',
        ],
    ],
    'database.connections.mysql.write' => [
        'host' => ['192.168.1.3'],
    ],
]);

// Use job queues for heavy operations
ProcessProductImport::dispatch($file)->onQueue('imports');
```

### Microservices Integration

```php
// Service class for external APIs
namespace App\Services;

use Illuminate\Support\Facades\Http;

class InventoryService
{
    private string $baseUrl;
    
    public function __construct()
    {
        $this->baseUrl = config('services.inventory.url');
    }
    
    public function getStock(int $productId): int
    {
        $response = Http::withToken(config('services.inventory.token'))
            ->get("{$this->baseUrl}/products/{$productId}/stock");
        
        return $response->json('stock', 0);
    }
    
    public function updateStock(int $productId, int $quantity): bool
    {
        $response = Http::withToken(config('services.inventory.token'))
            ->patch("{$this->baseUrl}/products/{$productId}/stock", [
                'quantity' => $quantity,
            ]);
        
        return $response->successful();
    }
}
```

## Common Patterns

### Settings Pattern

```php
// Create a settings resource
class Settings extends Resource
{
    public static $customTable = true;
    
    public static ?string $slug = 'settings';
    
    public static string $type = 'Settings';
    
    protected $table = 'settings';
    
    public static function getFields(): array
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Site Name',
                'slug' => 'site_name',
                'validation' => 'required|max:255',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'name' => 'Site Description',
                'slug' => 'site_description',
                'validation' => 'max:500',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Image',
                'name' => 'Logo',
                'slug' => 'logo',
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Select',
                'name' => 'Timezone',
                'slug' => 'timezone',
                'options' => timezone_identifiers_list(),
            ],
            [
                'type' => 'Aura\\Base\\Fields\\Toggle',
                'name' => 'Maintenance Mode',
                'slug' => 'maintenance_mode',
            ],
        ];
    }
    
    public static function get($key, $default = null)
    {
        return Cache::remember("settings.{$key}", 3600, function () use ($key, $default) {
            return static::where('key', $key)->value('value') ?? $default;
        });
    }
    
    public static function set($key, $value)
    {
        static::updateOrCreate(['key' => $key], ['value' => $value]);
        Cache::forget("settings.{$key}");
    }
}
```

### Trait Composition

```php
// Combine traits for functionality
trait Publishable
{
    public function initializePublishable()
    {
        $this->fillable[] = 'published_at';
        $this->casts['published_at'] = 'datetime';
    }
    
    public function publish()
    {
        $this->update(['published_at' => now()]);
    }
    
    public function unpublish()
    {
        $this->update(['published_at' => null]);
    }
    
    public function isPublished(): bool
    {
        return $this->published_at && $this->published_at->isPast();
    }
    
    public function scopePublished($query)
    {
        return $query->whereNotNull('published_at')
            ->where('published_at', '<=', now());
    }
}

// Use in models
class Product extends Model
{
    use Publishable;
    use HasSlug;
    use Searchable;
}
```

> 📹 **Video Placeholder**: [Best practices walkthrough showing real-world implementation of these patterns in an Aura CMS application]

## Common Gotchas

Be aware of these common issues when developing with Aura CMS:

1. **Team Scope**: Most models use `TeamScope` global scope. Use `withoutGlobalScope()` to bypass in tests:
   ```php
   $role = Role::withoutGlobalScope(\Aura\Base\Models\Scopes\TeamScope::class)
       ->where('slug', 'super_admin')
       ->first();
   ```

2. **Aura Facade Reset**: Tests automatically reset the Aura facade after each test to prevent pollution. This is configured in `tests/Pest.php`.

3. **Meta Fields**: Resources can store fields in a `meta` table. Check `usesMeta()` to determine storage method:
   ```php
   if ($this->usesMeta()) {
       // Fields stored in meta table
   }
   ```

4. **Type Column**: The `posts` table uses a `type` column for single-table inheritance. Custom table resources should set `$customTable = true`.

5. **Field Type Strings**: Always use fully qualified class names with escaped backslashes in field definitions:
   ```php
   'type' => 'Aura\\Base\\Fields\\Text',  // Correct
   'type' => 'Aura\Base\Fields\Text',     // Wrong - will fail
   ```

6. **Static Analysis**: PHPStan is configured at level 3. Run `composer analyse` before committing.

## Pro Tips

1. **Use Type Declarations**: Always use type hints for parameters and return types
2. **Leverage Laravel Features**: Use Laravel's built-in features before creating custom solutions
3. **Keep It Simple**: Don't over-engineer; start simple and refactor as needed
4. **Document Complex Logic**: Add comments for non-obvious code
5. **Use Dependency Injection**: Inject dependencies rather than using facades in classes
6. **Follow PSR Standards**: Use PSR-12 for coding style and PSR-4 for autoloading
7. **Write Tests First**: TDD helps design better APIs
8. **Use Value Objects**: For complex data structures
9. **Implement Caching Early**: But make it configurable
10. **Monitor Performance**: Use tools like Telescope and Debugbar

## Conclusion

Following these best practices and patterns will help you build maintainable, scalable, and secure Aura CMS applications. Remember:

- **Consistency** is more important than perfection
- **Readability** trumps cleverness
- **Security** should never be an afterthought
- **Performance** matters at scale
- **Testing** saves time in the long run

For more resources, consult the [Laravel Best Practices](https://github.com/alexeymezenin/laravel-best-practices) and contribute your own patterns to the Aura CMS community.