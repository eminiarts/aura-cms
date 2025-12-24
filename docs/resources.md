# Resources

> 📹 **Video Placeholder**: Complete overview of Aura CMS Resources - from basic concepts to advanced features

Resources are the heart of Aura CMS, transforming Laravel's Eloquent models into powerful, feature-rich content management entities. This comprehensive guide covers everything from basic resource creation to advanced patterns like soft deletes, versioning, and custom storage strategies.

## Table of Contents

- [Introduction](#introduction)
- [Creating Resources](#creating-resources)
- [Resource Properties](#resource-properties)
- [Resource Traits](#resource-traits)
- [Resource Methods](#resource-methods)
- [Fields Management](#fields-management)
- [Data Storage Strategies](#data-storage-strategies)
- [Relationships](#relationships)
- [Querying Resources](#querying-resources)
- [Actions and Permissions](#actions-and-permissions)
- [Table Configuration](#table-configuration)
- [Advanced Features](#advanced-features)
- [Resource Lifecycle](#resource-lifecycle)
- [Performance Optimization](#performance-optimization)
- [Best Practices](#best-practices)

## Introduction

Resources in Aura CMS are enhanced Eloquent models that provide:

- **Dynamic Field System**: Define fields without database migrations
- **Meta Storage**: Flexible key-value storage for additional data
- **Built-in Admin UI**: Automatic CRUD interface generation
- **Advanced Features**: Soft deletes, versioning, team scoping
- **Permission Integration**: Role-based access control out of the box
- **Global Search**: Integrated full-text search capabilities

Think of Resources as Laravel models on steroids - they handle everything from data definition to UI generation.

### Resource vs Model Comparison

| Feature | Laravel Model | Aura Resource |
|---------|--------------|---------------|
| Database Interaction | ✅ | ✅ |
| Relationships | ✅ | ✅ Enhanced |
| Admin UI | ❌ | ✅ Automatic |
| Field Definitions | ❌ | ✅ Dynamic |
| Meta Storage | ❌ | ✅ Built-in |
| Permissions | Manual | ✅ Automatic |
| Search | Manual | ✅ Integrated |
| Soft Deletes | ✅ | ✅ Enhanced |

## Creating Resources

### Using Artisan Command

The fastest way to create a resource:

```bash
# Basic resource
php artisan aura:resource Article

# Resource with custom table
php artisan aura:resource Product --custom
```

This generates a resource class in `app/Aura/Resources/`:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Article extends Resource
{
    public static string $type = 'Article';
    public static ?string $slug = 'article';
    protected static ?string $group = 'Content';
}
```

### Manual Resource Creation

For more control, create resources manually:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;
use Aura\Base\Fields\ID;
use Aura\Base\Fields\Text;
use Aura\Base\Fields\Wysiwyg;
use Aura\Base\Fields\BelongsTo;
use Aura\Base\Fields\Status;

class Article extends Resource
{
    // Resource identification
    public static string $type = 'Article';
    public static ?string $slug = 'articles';
    
    // Display configuration
    public static ?string $name = 'Article';
    public static ?string $pluralName = 'Articles';
    public static ?string $singularName = 'Article';
    
    // Navigation settings
    protected static ?string $group = 'Content';
    protected static ?int $sort = 10;
    public static ?string $icon = '<svg>...</svg>';
    
    // Feature flags
    public static bool $globalSearch = true;
    public static bool $showInNavigation = true;
    
    // Define fields
    public static function getFields()
    {
        return [
            [
                'name' => 'ID',
                'slug' => 'id',
                'type' => 'Aura\\Base\\Fields\\ID',
            ],
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|max:255',
                'on_index' => true,
                'on_forms' => true,
                'searchable' => true,
            ],
            // More fields...
        ];
    }
}
```

> **Pro Tip**: Use namespace imports for cleaner field definitions instead of full class names.

## Resource Properties

### Static Properties Reference

```php
class Product extends Resource
{
    // === IDENTIFICATION ===
    public static string $type = 'Product';              // Resource type identifier (required)
    public static ?string $slug = 'products';            // URL slug (defaults to slugified $name)
    
    // === DISPLAY ===
    public static ?string $name = 'Product';             // Display name
    public static ?string $pluralName = 'Products';      // Plural display name (auto-generated)
    public static ?string $singularName = 'Product';     // Singular display name (auto-generated)
    protected static ?string $icon = '<svg>...</svg>';   // Navigation icon (SVG string)
    
    // === NAVIGATION ===
    protected static ?string $group = 'Resources';       // Navigation group (default: 'Resources')
    protected static ?int $sort = 100;                   // Sort order (lower = higher priority)
    protected static bool $showInNavigation = true;      // Show in sidebar navigation
    protected static $dropdown = false;                  // Dropdown menu grouping (false or string)
    
    // === FEATURES ===
    public static $globalSearch = true;                  // Enable global search
    protected static array $searchable = ['title'];      // Fields to include in search
    public static $createEnabled = true;                 // Allow creation
    public static $editEnabled = true;                   // Allow editing
    public static $viewEnabled = true;                   // Allow viewing
    public static bool $indexViewEnabled = true;         // Show index page
    
    // === DATA STORAGE ===
    public static $customTable = false;                  // Use custom table (not posts)
    public static bool $usesMeta = true;                 // Store fields in meta table
    public static $taxonomy = false;                     // Is taxonomy/category resource
    protected static bool $title = false;                // Uses title field in posts table
    
    // === UI CONFIGURATION ===
    public static $showActionsAsButtons = false;         // Show actions as buttons vs dropdown
    public static $contextMenu = true;                   // Enable right-click context menu
    
    // === INSTANCE PROPERTIES ===
    public array $actions = [];                          // Available row actions
    public array $bulkActions = [];                      // Bulk actions for table
    public array $metaFields = [];                       // Meta fields to save
    public array $taxonomyFields = [];                   // Taxonomy fields to save
    public array $widgetSettings = [                      // Widget date range options
        'default' => '30d',
        'options' => ['1d', '7d', '30d', '60d', '90d', '180d', '365d', 'all', 'ytd', 'mtd', 'wtd'],
    ];
    protected $baseFillable = [];                        // Original fillable before merge
    
    // === ELOQUENT PROPERTIES ===
    protected $table = 'products';                       // Custom table name
    protected $fillable = ['name', 'sku', 'price'];     // Mass assignable fields
    protected $casts = [                                 // Attribute casting
        'price' => 'decimal:2',
        'features' => 'array',
    ];
    protected $hidden = ['internal_notes'];              // Hidden from JSON
    protected $appends = ['fields'];                     // Appended attributes (fields is default)
    protected $with = ['meta'];                          // Eager load (meta added when usesMeta)
}
```

### Dynamic Properties

Resources also support dynamic configuration through methods:

```php
class Product extends Resource
{
    // Dynamic icon based on context
    public function getIcon()
    {
        return '<svg class="w-5 h-5" viewBox="0 0 18 18">...</svg>';
    }
    
    // Conditional navigation display
    public static function getShowInNavigation(): bool
    {
        return auth()->user()->hasRole(['admin', 'editor']);
    }
    
    // Dynamic dropdown grouping
    public static function getDropdown()
    {
        return 'Commerce'; // Groups this resource under "Commerce" dropdown
    }
}
```

## Resource Traits

Resources use several traits that provide core functionality. Understanding these is key to extending behavior:

```php
class Resource extends Model
{
    // Core Aura traits
    use AuraModelConfig;      // Properties, navigation, meta, scopes
    use InitialPostFields;    // Auto-sets user_id, team_id, type on create
    use InputFields;          // Field processing pipeline
    use InteractsWithTable;   // Table/grid/kanban configuration
    use SaveFieldAttributes;  // Moves field values to fields array
    use SaveMetaFields;       // Persists meta fields after save
    
    // Laravel traits
    use HasFactory;
    use HasTimestamps;
}
```

### Trait: AuraModelConfig

Provides all static properties and core methods for resources:

- Navigation methods: `navigation()`, `getIcon()`, `indexUrl()`, `createUrl()`, `editUrl()`, `viewUrl()`
- Display methods: `pluralName()`, `singularName()`, `title()`, `display()`, `displayFieldValue()`
- Meta queries: `scopeWhereMeta()`, `scopeOrWhereMeta()`, `scopeWhereInMeta()`, `scopeWhereMetaContains()`, `scopeWhereNotInMeta()`
- Type checking: `isMetaField()`, `isTableField()`, `isTaxonomyField()`, `isAppResource()`, `isVendorResource()`

### Trait: InteractsWithTable

Controls table display settings:

```php
class Product extends Resource
{
    public function defaultPerPage() { return 10; }           // Items per page
    public function defaultTableSort() { return 'id'; }       // Default sort column
    public function defaultTableSortDirection() { return 'desc'; } // Sort direction
    public function defaultTableView() { return 'list'; }     // 'list', 'grid', or 'kanban'
    public function showTableSettings() { return true; }      // Show settings button
    public function tableView() { return 'aura::components.table.list-view'; }
    public function tableGridView() { return false; }         // Custom grid view
    public function tableKanbanView() { return false; }       // Custom kanban view
    public function kanbanQuery($query) { return false; }     // Kanban query modifier
}
```

### Trait: SaveMetaFields

Handles the meta field persistence lifecycle:

1. On `saving`: processes field values, calls `set()` methods on field classes
2. On `saved`: persists meta fields to the `meta` table via `updateOrCreate`
3. Fires `metaSaved` event after meta persistence

## Resource Methods

### Core Methods Reference

```php
class Article extends Resource
{
    // === FIELD MANAGEMENT ===
    public static function getFields() { }              // Define resource fields (override this)
    public function fieldBySlug($slug) { }              // Get field definition by slug
    public function fieldClassBySlug($slug) { }         // Get field class instance
    public function fieldsCollection() { }              // All fields as collection
    public function mappedFields() { }                  // Fields with field class instances
    public function inputFields() { }                   // Only input-type fields
    public function indexFields() { }                   // Fields for table display
    public function viewFields() { }                    // Fields for view page
    public function createFields() { }                  // Fields for create form
    public function editFields() { }                    // Fields for edit form
    public function getFieldSlugs() { }                 // All field slugs as collection
    public function inputFieldsSlugs() { }              // Input field slugs as array
    public function getGroupedFields() { }              // Fields processed into tree
    public function getFieldsBeforeTree() { }           // Flat fields with IDs
    public function getSearchableFields() { }           // Fields marked searchable
    
    // === DATA ACCESS ===
    public function getMeta($key = null) { }            // Get meta value(s)
    public function getFieldsAttribute() { }            // Virtual 'fields' attribute
    public function getFieldsWithoutConditionalLogic() { } // All field values
    public function display($key) { }                   // Display formatted value
    public function displayFieldValue($key, $value) { } // Format specific field
    public function getFieldValue($key) { }             // Get raw field value
    
    // === URLS ===
    public function indexUrl() { }                      // Index page URL
    public function createUrl() { }                     // Create page URL
    public function editUrl() { }                       // Edit page URL
    public function viewUrl() { }                       // View page URL
    public function getIndexRoute() { }                 // Named route for index
    
    // === VIEWS ===
    public function indexView() { }                     // Livewire view for index
    public function createView() { }                    // Livewire view for create
    public function editView() { }                      // Livewire view for edit
    public function viewView() { }                      // Livewire view for show
    public function editHeaderView() { }                // Edit page header partial
    public function viewHeaderView() { }                // View page header partial
    public function tableComponentView() { }            // Table component view
    public function rowView() { }                       // Table row view
    
    // === DISPLAY ===
    public function title() { }                         // Display title for instance
    public function pluralName() { }                    // Plural resource name
    public function singularName() { }                  // Singular resource name
    public function icon() { }                          // Icon (alias for getIcon)
    public function getIcon() { }                       // SVG icon string
    public function getBadge() { }                      // Navigation badge count
    public function getBadgeColor() { }                 // Badge color class
    public function navigation() { }                    // Full navigation config array
    
    // === PERMISSIONS & ACTIONS ===
    public function actions() { }                       // Define row actions (override)
    public function getActions() { }                    // Get available actions
    public function getBulkActions() { }                // Get bulk actions
    public function allowedToPerformActions() { }       // Check if actions allowed
    
    // === TYPE CHECKING ===
    public static function usesCustomTable() { }        // Uses custom table?
    public static function usesMeta() { }               // Uses meta storage?
    public static function usesTitle() { }              // Uses title field?
    public function isTaxonomy() { }                    // Is taxonomy resource?
    public function isMetaField($key) { }               // Field stored in meta?
    public function isTableField($key) { }              // Field stored in table?
    public function isTaxonomyField($key) { }           // Is taxonomy relation?
    public function isRelation($key) { }                // Is Eloquent relation?
    public function isBaseFillable($key) { }            // In base fillable array?
    public function isAppResource() { }                 // Defined in app namespace?
    public function isVendorResource() { }              // Defined in vendor?
    
    // === RELATIONSHIPS ===
    public function meta() { }                          // MorphMany to Meta model
    public function user() { }                          // BelongsTo user
    public function team() { }                          // BelongsTo team
    public function parent() { }                        // BelongsTo parent (self)
    public function children() { }                      // HasMany children (self)
    public function revision() { }                      // HasMany revisions
    public function attachment() { }                    // HasMany attachments
    
    // === CONFIGURATION ===
    public function getHeaders() { }                    // Table headers config
    public function getColumns() { }                    // Available columns
    public function getDefaultColumns() { }             // Default visible columns
    public function getTableHeaders() { }               // Filtered table headers
    public function indexTableSettings() { }            // Custom table settings
    public function getBaseFillable() { }               // Original fillable array
    public static function getWidgets() { }             // Dashboard widgets
    public function widgets() { }                       // Processed widgets
}
```

### Magic Methods

Resources override `__get` and `__call` to provide dynamic access to field values and relationships:

```php
// __get behavior (accessing $article->featured)
1. Try parent Eloquent __get
2. If field slug exists and is a relation field, resolve relationship
3. If key exists in $this->fields array, return that value
4. Return null

// __call behavior (calling $article->author())
1. If method name matches a field slug that is a relation
2. Return the relationship query builder
3. Otherwise, pass to parent __call
```

**Practical Examples**

```php
$article = Article::find(1);

// These are equivalent for accessing field values:
$article->featured;              // Via __get magic
$article->fields['featured'];    // Via fields accessor
$article->getMeta('featured');   // Explicit meta access (for meta fields)

// Relation fields work like Eloquent relations:
$article->categories;            // Returns collection (via __get)
$article->categories();          // Returns relationship builder (via __call)
```

### Implementing Custom Methods

```php
class Article extends Resource
{
    // Custom display title
    public function getDisplayTitle(): string
    {
        return $this->title ?: 'Untitled Article';
    }
    
    // Custom URL generation
    public function getPublicUrl(): string
    {
        return route('blog.show', $this->slug);
    }
    
    // Business logic
    public function publish(): bool
    {
        $this->update([
            'status' => 'published',
            'published_at' => now(),
        ]);
        
        event(new ArticlePublished($this));
        
        return true;
    }
    
    // Computed properties (Eloquent accessor)
    public function getReadingTimeAttribute(): int
    {
        $words = str_word_count(strip_tags($this->content));
        return ceil($words / 200);
    }
    
    // Custom field getter (called during field processing)
    public function getFeaturedField($value)
    {
        return $value ? 'Yes' : 'No';
    }
    
    // Custom field setter (called during save)
    public function setSlugField($value)
    {
        // Custom processing
        $this->attributes['slug'] = Str::slug($value);
        return $this;
    }
}
```

## Fields Management

### Field Pipeline

Aura processes fields through a sophisticated pipeline:

```php
// The field processing pipeline
$fields = collect($resource->getFields())
    ->pipe(new MapFields($request))              // Map field instances
    ->pipe(new AddIdsToFields())                 // Add unique IDs
    ->pipe(new FilterCreateFields($model))       // Filter for context
    ->pipe(new ApplyParentConditionalLogic())    // Parent conditions
    ->pipe(new DoNotDeferConditionalLogic())     // Immediate conditions
    ->pipe(new ApplyGroupedInputs())             // Group inputs
    ->pipe(new ApplyTabs())                      // Process tabs
    ->pipe(new ApplyWrappers())                  // Apply wrappers
    ->pipe(new BuildTreeFromFields())            // Build field tree
    ->pipe(new TransformSlugs($model))           // Transform slugs
    ->pipe(new ApplyLayoutFields($model));       // Apply layout
```

### Advanced Field Definition

```php
public static function getFields()
{
    return [
        // Basic field with all options
        [
            'name' => 'Title',
            'slug' => 'title',
            'type' => 'Aura\\Base\\Fields\\Text',
            'validation' => 'required|max:255',
            'placeholder' => 'Enter article title...',
            'helper' => 'SEO-friendly title for your article',
            'default' => '',
            'on_index' => true,
            'on_forms' => true,
            'on_view' => true,
            'searchable' => true,
            'style' => [
                'width' => '66.66',
                'wrapper_class' => 'mt-4',
            ],
        ],
        
        // Field with conditional logic
        [
            'name' => 'Featured Image Caption',
            'slug' => 'featured_caption',
            'type' => 'Aura\\Base\\Fields\\Text',
            'conditional_logic' => [
                [
                    'field' => 'featured_image',
                    'operator' => '!=',
                    'value' => '',
                ],
            ],
        ],
        
        // Field with dynamic options
        [
            'name' => 'Category',
            'slug' => 'category_id',
            'type' => 'Aura\\Base\\Fields\\Select',
            'options' => function() {
                return Category::pluck('name', 'id')->toArray();
            },
        ],
        
        // Complex validation with closures
        [
            'name' => 'Slug',
            'slug' => 'slug',
            'type' => 'Aura\\Base\\Fields\\Slug',
            'validation' => [
                'required',
                'regex:/^[a-z0-9-]+$/',
                function ($attribute, $value, $fail) {
                    if (Article::where('slug', $value)->exists()) {
                        $fail('This slug is already taken.');
                    }
                },
            ],
        ],
    ];
}
```

### Field Caching

Fields are cached for performance:

```php
class Article extends Resource
{
    // Clear cache when fields change
    public static function clearFieldCache()
    {
        cache()->forget('aura.resource.Article.fields');
        
        // Clear related caches
        cache()->tags(['aura-fields'])->flush();
    }
    
    // Custom field caching strategy
    public static function getFields()
    {
        return cache()->remember(
            'aura.resource.Article.fields',
            now()->addHours(24),
            fn() => static::defineFields()
        );
    }
}
```

## Data Storage Strategies

### Strategy 1: Posts Table (Default)

Uses the shared `posts` table with type discrimination:

```php
class Article extends Resource
{
    public static string $type = 'Article';
    // No additional configuration needed
}

// Database structure:
// posts table: id, type, title, content, slug, user_id, team_id...
// meta table: id, metable_type, metable_id, key, value
```

### Strategy 2: Custom Table

Uses a dedicated table for better performance:

```php
class Product extends Resource
{
    public static bool $customTable = true;
    protected $table = 'products';
    
    protected $fillable = [
        'name', 'sku', 'price', 'description',
        'stock', 'category_id', 'brand_id'
    ];
    
    // Migration example
    Schema::create('products', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->string('sku')->unique();
        $table->decimal('price', 10, 2);
        $table->text('description')->nullable();
        $table->integer('stock')->default(0);
        $table->foreignId('category_id')->constrained();
        $table->foreignId('brand_id')->nullable()->constrained();
        $table->foreignId('user_id')->constrained();
        $table->foreignId('team_id')->nullable()->constrained();
        $table->timestamps();
        $table->softDeletes();
        
        $table->index(['sku', 'name']);
        $table->index('category_id');
    });
}
```

### Strategy 3: Hybrid Approach

Combines custom table with meta storage:

```php
class Product extends Resource
{
    public static bool $customTable = true;
    public static bool $usesMeta = true;
    protected $table = 'products';
    
    // Core fields in table
    protected $fillable = ['name', 'sku', 'price'];
    
    // Additional fields in meta
    public static function getFields()
    {
        return [
            // Table fields
            ['slug' => 'name', 'type' => 'Text'],
            ['slug' => 'sku', 'type' => 'Text'],
            ['slug' => 'price', 'type' => 'Number'],
            
            // Meta fields
            ['slug' => 'specifications', 'type' => 'Json'],
            ['slug' => 'warranty_info', 'type' => 'Textarea'],
            ['slug' => 'shipping_notes', 'type' => 'Text'],
        ];
    }
}
```

> **Pro Tip**: Use custom tables for resources with many records or complex queries. Use meta storage for flexibility.

## Relationships

### Built-in Relationships

Every Resource inherits these relationships from the base class:

```php
class Resource extends Model
{
    // Meta storage - polymorphic relationship
    public function meta()
    {
        return $this->morphMany(Meta::class, 'metable');
    }
    
    // Owner of the resource
    public function user()
    {
        return $this->belongsTo(config('aura.resources.user'));
    }
    
    // Team (when multi-tenancy enabled)
    public function team()
    {
        return $this->belongsTo(config('aura.resources.team'));
    }
    
    // Self-referential parent
    public function parent()
    {
        return $this->belongsTo(get_class($this), 'parent_id');
    }
    
    // Self-referential children
    public function children()
    {
        return $this->hasMany(get_class($this), 'parent_id');
    }
    
    // Revisions (for versioning)
    public function revision()
    {
        return $this->hasMany(self::class, 'parent_id')
            ->where('post_type', 'revision');
    }
    
    // Attachments
    public function attachment()
    {
        return $this->hasMany(self::class, 'post_parent')
            ->where('post_type', 'attachment');
    }
}
```

### Custom Eloquent Relationships

```php
class Article extends Resource
{
    // Custom BelongsTo
    public function author()
    {
        return $this->belongsTo(User::class, 'user_id');
    }
    
    // BelongsToMany
    public function tags()
    {
        return $this->belongsToMany(Tag::class, 'article_tags');
    }
    
    // HasMany
    public function comments()
    {
        return $this->hasMany(Comment::class);
    }
    
    // HasOne
    public function featuredComment()
    {
        return $this->hasOne(Comment::class)->where('featured', true);
    }
    
    // MorphMany
    public function media()
    {
        return $this->morphMany(Media::class, 'mediable');
    }
}
```

### Dynamic Field Relationships

Aura handles relationships defined in fields automatically:

```php
public static function getFields()
{
    return [
        [
            'name' => 'Author',
            'slug' => 'author',
            'type' => 'Aura\\Base\\Fields\\BelongsTo',
            'resource' => 'User',
            'display_field' => 'name',
            'validation' => 'required|exists:users,id',
        ],
        [
            'name' => 'Categories',
            'slug' => 'categories',
            'type' => 'Aura\\Base\\Fields\\BelongsToMany',
            'resource' => 'Category',
            'pivot_table' => 'article_categories',
            'multiple' => true,
        ],
    ];
}

// Access relationships
$article->author;      // Automatically resolved
$article->categories;  // Automatically resolved
```

### Advanced Relationship Patterns

```php
class Article extends Resource
{
    // Polymorphic relations
    public function reactions()
    {
        return $this->morphMany(Reaction::class, 'reactable');
    }
    
    // Through relationships
    public function authorTeam()
    {
        return $this->hasOneThrough(
            Team::class,
            User::class,
            'id',
            'id',
            'user_id',
            'team_id'
        );
    }
    
    // Dynamic relationships
    public function relatedArticles()
    {
        return $this->belongsToMany(
            Article::class,
            'related_articles',
            'article_id',
            'related_id'
        )->withPivot('relevance_score')
          ->orderByPivot('relevance_score', 'desc');
    }
}
```

## Querying Resources

### Basic Queries

```php
// Standard Eloquent queries work
$articles = Article::where('status', 'published')->get();
$article = Article::find(1);
$latest = Article::latest()->take(10)->get();

// With scopes
$published = Article::published()->get();
$byAuthor = Article::byAuthor($userId)->get();
```

### Meta Field Queries

Resources provide query scopes for meta fields (from `AuraModelConfig` trait):

```php
// Basic meta query (2 arguments: key, value)
$featured = Article::whereMeta('featured', true)->get();

// With operator (3 arguments: key, operator, value)
$highPriority = Article::whereMeta('priority', '>', 5)->get();

// Multiple meta conditions
$special = Article::whereMeta('featured', true)
    ->whereMeta('priority', '>', 5)
    ->get();

// Array of conditions
$filtered = Article::whereMeta([
    'featured' => true,
    'status' => 'active',
])->get();

// OR conditions
$highlighted = Article::whereMeta('featured', true)
    ->orWhereMeta('spotlight', true)
    ->get();

// IN queries - match any value in array
$selected = Article::whereInMeta('category', ['news', 'updates'])->get();

// NOT IN queries - exclude values
$excluded = Article::whereNotInMeta('status', ['draft', 'archived'])->get();

// JSON contains - search within JSON meta values
$tagged = Article::whereMetaContains('tags', 'laravel')->get();
```

**Note**: Meta queries use `whereHas` internally, which may impact performance on large datasets. Consider using custom tables for frequently queried fields.

### Complex Queries

```php
class ArticleRepository
{
    public function findPublishedWithAuthor($limit = 10)
    {
        return Article::with(['author', 'categories', 'tags'])
            ->where('status', 'published')
            ->where('published_at', '<=', now())
            ->whereMeta('visibility', 'public')
            ->orderBy('published_at', 'desc')
            ->limit($limit)
            ->get();
    }
    
    public function searchArticles($term)
    {
        return Article::where(function ($query) use ($term) {
            $query->where('title', 'like', "%{$term}%")
                  ->orWhere('content', 'like', "%{$term}%");
        })
        ->orWhereHas('author', function ($query) use ($term) {
            $query->where('name', 'like', "%{$term}%");
        })
        ->orWhereHas('tags', function ($query) use ($term) {
            $query->where('name', 'like', "%{$term}%");
        })
        ->get();
    }
}
```

### Global Scopes

Resources automatically apply these scopes (defined in `Resource::booted()`):

```php
use Aura\Base\Models\Scopes\TypeScope;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Models\Scopes\ScopedScope;

// TypeScope - filters by resource type (only for non-custom tables)
// Applied when $customTable = false, filters posts by type column
Article::withoutGlobalScope(TypeScope::class)->get(); // All post types

// TeamScope - multi-tenancy filtering
// Filters by team_id when config('aura.teams') is true
Article::withoutGlobalScope(TeamScope::class)->get(); // All teams

// ScopedScope - user-based filtering
// Can restrict resources to owner based on configuration
Article::withoutGlobalScope(ScopedScope::class)->get(); // All users
```

**Removing Multiple Scopes**

```php
// Remove all global scopes
Article::withoutGlobalScopes()->get();

// Remove specific scopes
Article::withoutGlobalScopes([
    TypeScope::class,
    TeamScope::class,
])->get();
```

**Custom Global Scopes**

```php
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

class PublishedScope implements Scope
{
    public function apply(Builder $builder, Model $model)
    {
        $builder->where('status', 'published');
    }
}

// In your resource
protected static function booted()
{
    parent::booted(); // Important: call parent first!
    static::addGlobalScope(new PublishedScope);
}
```

## Actions and Permissions

### Defining Resource Actions

```php
class Article extends Resource
{
    public static array $actions = [
        'publish' => [
            'label' => 'Publish',
            'icon' => '<svg>...</svg>',
            'class' => 'text-green-600 hover:text-green-700',
            'confirm' => true,
            'confirm-title' => 'Publish Article?',
            'confirm-message' => 'This will make the article public.',
            'condition' => function($model) {
                return $model->status === 'draft';
            },
        ],
        'archive' => [
            'label' => 'Archive',
            'icon' => '<svg>...</svg>',
            'class' => 'text-yellow-600',
            'modal' => 'archive-modal',
        ],
    ];
    
    public static array $bulkActions = [
        'publish' => 'Publish Selected',
        'archive' => [
            'label' => 'Archive Selected',
            'confirm' => true,
        ],
        'export' => [
            'label' => 'Export to CSV',
            'handler' => 'exportToCsv',
        ],
    ];
}
```

### Permission Integration

```php
// Automatic permission generation
php artisan aura:permissions Article

// Creates permissions:
// - view Article
// - create Article
// - update Article
// - delete Article
// - publish Article (custom action)
// - archive Article (custom action)

// In your resource
public function allowedToPerformAction($action): bool
{
    // Super admin bypass
    if (auth()->user()->isSuperAdmin()) {
        return true;
    }
    
    // Check specific permission
    return auth()->user()->can("{$action} {$this->type}");
}

// Usage in views
@can('create', App\Aura\Resources\Article::class)
    <a href="{{ route('aura.article.create') }}">New Article</a>
@endcan
```

### Custom Permission Logic

```php
class Article extends Resource
{
    public static function canCreate(): bool
    {
        $user = auth()->user();
        
        // Custom logic
        if ($user->articles()->count() >= 10 && !$user->isPro()) {
            return false;
        }
        
        return $user->can('create Article');
    }
    
    public function canEdit(): bool
    {
        // Owner can always edit
        if ($this->user_id === auth()->id()) {
            return true;
        }
        
        // Editors can edit published articles
        if (auth()->user()->hasRole('editor')) {
            return $this->status === 'published';
        }
        
        return auth()->user()->can('update Article');
    }
}
```

## Table Configuration

### Basic Table Setup

```php
class Article extends Resource
{
    // Table defaults (instance methods, not static)
    public function defaultPerPage(): int
    {
        return 10; // Default is 10
    }
    
    public function defaultTableSort(): string
    {
        return 'id'; // Default sort column
    }
    
    public function defaultTableSortDirection(): string
    {
        return 'desc'; // 'asc' or 'desc'
    }
    
    public function defaultTableView(): string
    {
        return 'list'; // 'list', 'grid', or custom view
    }
    
    public function showTableSettings(): bool
    {
        return true; // Show/hide table settings button
    }
}
```

### Custom Table Columns

```php
public static function indexTableColumns(): array
{
    return [
        'thumbnail' => [
            'label' => '',
            'sortable' => false,
            'class' => 'w-16',
            'view' => function($model) {
                if ($model->featured_image) {
                    return '<img src="'.$model->featured_image.'" class="w-12 h-12 rounded">';
                }
                return '<div class="w-12 h-12 bg-gray-200 rounded"></div>';
            },
        ],
        'title' => [
            'label' => 'Title',
            'sortable' => true,
            'searchable' => true,
            'class' => 'font-medium',
            'href' => function($model) {
                return $model->editUrl();
            },
        ],
        'author.name' => [
            'label' => 'Author',
            'sortable' => true,
            'relation' => 'author',
        ],
        'status' => [
            'label' => 'Status',
            'sortable' => true,
            'badge' => true,
            'badge_color' => function($value) {
                return match($value) {
                    'published' => 'green',
                    'draft' => 'gray',
                    'archived' => 'red',
                    default => 'blue',
                };
            },
        ],
        'published_at' => [
            'label' => 'Published',
            'sortable' => true,
            'format' => function($value) {
                return $value?->format('M j, Y') ?? 'Not published';
            },
        ],
        'actions' => [
            'label' => '',
            'view' => 'aura::table.actions',
        ],
    ];
}
```

### Table Filters

```php
public static function tableFilters(): array
{
    return [
        'status' => [
            'label' => 'Status',
            'type' => 'select',
            'options' => [
                '' => 'All Statuses',
                'draft' => 'Draft',
                'published' => 'Published',
                'archived' => 'Archived',
            ],
        ],
        'author_id' => [
            'label' => 'Author',
            'type' => 'select',
            'options' => User::authors()->pluck('name', 'id')
                ->prepend('All Authors', ''),
        ],
        'date_range' => [
            'label' => 'Date Range',
            'type' => 'date_range',
            'default' => [
                'start' => now()->subMonth(),
                'end' => now(),
            ],
        ],
        'has_image' => [
            'label' => 'Has Image',
            'type' => 'boolean',
            'query' => function($query, $value) {
                if ($value) {
                    $query->whereNotNull('featured_image');
                }
            },
        ],
    ];
}
```

### Custom Table Views

```php
// Grid view
public static function tableGridView(): string
{
    return 'resources.articles.grid';
}

// Kanban view
public static function tableKanbanView(): string
{
    return 'resources.articles.kanban';
}

public static function kanbanColumns(): array
{
    return [
        'draft' => ['label' => 'Draft', 'class' => 'bg-gray-50'],
        'review' => ['label' => 'In Review', 'class' => 'bg-yellow-50'],
        'published' => ['label' => 'Published', 'class' => 'bg-green-50'],
    ];
}
```

## Advanced Features

### Soft Deletes

```php
class Article extends Resource
{
    use SoftDeletes;
    
    // Enable trash functionality
    public static bool $softDeletes = true;
    
    // Customize trash behavior
    public function prunable()
    {
        return static::where('deleted_at', '<=', now()->subDays(30));
    }
}

// Usage
$article->delete(); // Soft delete
$article->restore(); // Restore
$article->forceDelete(); // Permanent delete

// Querying
Article::withTrashed()->get(); // Include soft deleted
Article::onlyTrashed()->get(); // Only soft deleted
```

### Versioning/Revisions

```php
class Article extends Resource
{
    public static bool $versioning = true;
    
    // Relationship to revisions
    public function revisions()
    {
        return $this->hasMany(Post::class, 'parent_id')
            ->where('type', 'revision')
            ->orderBy('created_at', 'desc');
    }
    
    // Create a revision
    public function createRevision(): self
    {
        $revision = $this->replicate();
        $revision->type = 'revision';
        $revision->parent_id = $this->id;
        $revision->save();
        
        // Copy meta fields
        foreach ($this->meta as $meta) {
            $revision->meta()->create($meta->toArray());
        }
        
        return $revision;
    }
    
    // Restore from revision
    public function restoreFromRevision($revisionId): bool
    {
        $revision = $this->revisions()->findOrFail($revisionId);
        
        $this->fill($revision->only($this->fillable));
        $this->save();
        
        return true;
    }
}
```

### Import/Export

```php
class Article extends Resource
{
    public static function exportColumns(): array
    {
        return [
            'id' => 'ID',
            'title' => 'Title',
            'slug' => 'URL Slug',
            'author.name' => 'Author Name',
            'status' => 'Status',
            'published_at' => 'Published Date',
            'created_at' => 'Created Date',
        ];
    }
    
    public static function importRules(): array
    {
        return [
            'title' => 'required|string|max:255',
            'slug' => 'required|string|unique:posts,slug',
            'author_email' => 'required|email|exists:users,email',
            'status' => 'required|in:draft,published',
        ];
    }
    
    public static function importMap($row): array
    {
        return [
            'title' => $row['title'],
            'slug' => Str::slug($row['title']),
            'user_id' => User::where('email', $row['author_email'])->first()->id,
            'status' => $row['status'],
            'published_at' => $row['status'] === 'published' ? now() : null,
        ];
    }
}
```

### Webhooks & Events

```php
class Article extends Resource
{
    protected $dispatchesEvents = [
        'created' => ArticleCreated::class,
        'updated' => ArticleUpdated::class,
        'deleted' => ArticleDeleted::class,
    ];
    
    protected static function booted()
    {
        parent::booted();
        
        // Automatic slug generation
        static::creating(function ($article) {
            if (empty($article->slug)) {
                $article->slug = Str::slug($article->title);
            }
        });
        
        // Clear cache on changes
        static::saved(function ($article) {
            cache()->tags(['articles'])->flush();
        });
        
        // Send notifications
        static::created(function ($article) {
            $article->author->notify(new ArticlePublished($article));
        });
    }
}
```

### Media Attachments

```php
class Article extends Resource
{
    // Multiple media collections
    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('featured')
            ->singleFile()
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);
            
        $this->addMediaCollection('gallery')
            ->acceptsMimeTypes(['image/jpeg', 'image/png']);
            
        $this->addMediaCollection('downloads')
            ->useDisk('downloads');
    }
    
    // Custom media conversions
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(300)
            ->height(200)
            ->quality(90)
            ->optimize()
            ->nonQueued();
            
        $this->addMediaConversion('preview')
            ->width(800)
            ->height(600)
            ->quality(85);
    }
}
```

## Resource Lifecycle

### Complete Lifecycle Flow

```php
class Article extends Resource
{
    // 1. CREATING
    protected static function booting()
    {
        static::creating(function ($article) {
            $article->user_id = auth()->id();
            $article->team_id = auth()->user()->currentTeam->id;
            $article->generateSlug();
        });
    }
    
    // 2. CREATED
    protected static function booted()
    {
        static::created(function ($article) {
            // Generate initial revision
            $article->createRevision();
            
            // Process media uploads
            $article->processMediaUploads();
            
            // Update search index
            $article->updateSearchIndex();
        });
    }
    
    // 3. UPDATING
    public function updating()
    {
        // Track changes
        $this->trackChanges();
        
        // Validate business rules
        if ($this->status === 'published' && empty($this->published_at)) {
            $this->published_at = now();
        }
    }
    
    // 4. UPDATED
    public function updated()
    {
        // Create revision if significant changes
        if ($this->hasSignificantChanges()) {
            $this->createRevision();
        }
        
        // Clear caches
        $this->clearCaches();
        
        // Trigger webhooks
        $this->triggerWebhooks('updated');
    }
    
    // 5. DELETING
    public function deleting()
    {
        // Clean up relationships
        $this->comments()->delete();
        $this->tags()->detach();
        
        // Archive data
        $this->archiveData();
    }
    
    // 6. DELETED
    public function deleted()
    {
        // Remove from search index
        $this->removeFromSearchIndex();
        
        // Clean up media
        $this->clearMediaCollection();
    }
}
```

### Field Value Processing

Understanding how field values flow through the system is essential for customization:

```
SAVING FLOW:
1. Form input received (Livewire component)
2. Field validation (Laravel rules + custom)
3. SaveFieldAttributes trait (saving event):
   - Collects field values into $attributes['fields'] array
   - Removes non-base-fillable fields from $attributes
4. SaveMetaFields trait (saving event):
   - Processes each field through field class set() method
   - Calls saving() on field classes
   - Stores in $metaFields for later persistence
5. Eloquent saves to database (table fields)
6. SaveMetaFields trait (saved event):
   - Persists $metaFields to meta table
   - Calls saved() on field classes
   - Fires 'metaSaved' model event

READING FLOW:
1. Model loaded with meta relationship eager loaded
2. getFieldsAttribute() accessor called
3. getFieldsWithoutConditionalLogic() builds values:
   - Merges table attributes + meta values
   - Calls get() on field classes
4. Conditional logic filters visible fields
5. Result cached in $fieldsAttributeCache
```

**The `fields` Attribute**

Every resource has a computed `fields` attribute that combines table and meta values:

```php
// Access field values
$article = Article::find(1);

// All field values (filtered by conditional logic)
$article->fields;              // Collection

// Specific field value
$article->fields['featured'];  // Via fields array
$article->featured;            // Via __get magic method

// Raw value without conditional logic
$article->getFieldsWithoutConditionalLogic();

// Clear fields cache after updates
$article->clearFieldsAttributeCache();
```

## Performance Optimization

### Query Optimization

```php
class Article extends Resource
{
    // Eager load relationships
    protected $with = ['author', 'categories'];
    
    // Define indexes in migration
    public function up()
    {
        Schema::table('posts', function (Blueprint $table) {
            $table->index(['type', 'status', 'published_at']);
            $table->index(['type', 'user_id']);
            $table->fullText(['title', 'content']);
        });
    }
    
    // Optimize meta queries
    public function scopeOptimizedMeta($query, $key, $value)
    {
        return $query->whereExists(function ($query) use ($key, $value) {
            $query->select(DB::raw(1))
                  ->from('meta')
                  ->whereColumn('meta.metable_id', 'posts.id')
                  ->where('meta.metable_type', static::class)
                  ->where('meta.key', $key)
                  ->where('meta.value', $value);
        });
    }
}
```

### Caching Strategies

```php
class Article extends Resource
{
    // Cache individual resources
    public static function findCached($id)
    {
        return cache()->remember(
            "article.{$id}",
            now()->addHours(1),
            fn() => static::with(['author', 'categories'])->find($id)
        );
    }
    
    // Cache queries
    public static function popularThisWeek()
    {
        return cache()->remember(
            'articles.popular.week',
            now()->addHours(6),
            fn() => static::withCount('views')
                ->where('published_at', '>=', now()->subWeek())
                ->orderByDesc('views_count')
                ->limit(10)
                ->get()
        );
    }
    
    // Clear caches on update
    protected static function booted()
    {
        static::saved(function ($article) {
            cache()->forget("article.{$article->id}");
            cache()->tags(['articles'])->flush();
        });
    }
}
```

### Database Optimization

```php
// Use custom table for high-volume resources
class PageView extends Resource
{
    public static bool $customTable = true;
    protected $table = 'page_views';
    
    // Partition by date for better performance
    public function up()
    {
        DB::statement("
            CREATE TABLE page_views (
                id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
                page_id BIGINT UNSIGNED NOT NULL,
                user_id BIGINT UNSIGNED NULL,
                ip_address VARCHAR(45),
                user_agent TEXT,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (id, created_at)
            ) PARTITION BY RANGE (YEAR(created_at)) (
                PARTITION p2023 VALUES LESS THAN (2024),
                PARTITION p2024 VALUES LESS THAN (2025),
                PARTITION p2025 VALUES LESS THAN (2026)
            )
        ");
    }
}
```

## Best Practices

### 1. Resource Organization

```php
// Group related resources
app/Aura/Resources/
├── Blog/
│   ├── Article.php
│   ├── Category.php
│   └── Tag.php
├── Commerce/
│   ├── Product.php
│   ├── Order.php
│   └── Customer.php
└── System/
    ├── User.php
    ├── Role.php
    └── Permission.php
```

### 2. Field Organization

```php
class Article extends Resource
{
    public static function getFields()
    {
        return [
            ...static::contentFields(),
            ...static::seoFields(),
            ...static::mediaFields(),
            ...static::taxonomyFields(),
            ...static::metadataFields(),
        ];
    }
    
    protected static function contentFields(): array
    {
        return [
            ['name' => 'Title', 'slug' => 'title', 'type' => 'Text'],
            ['name' => 'Content', 'slug' => 'content', 'type' => 'Wysiwyg'],
        ];
    }
    
    protected static function seoFields(): array
    {
        return [
            ['name' => 'SEO Title', 'slug' => 'seo_title', 'type' => 'Text'],
            ['name' => 'SEO Description', 'slug' => 'seo_description', 'type' => 'Textarea'],
        ];
    }
}
```

### 3. Validation Patterns

```php
class Article extends Resource
{
    // Centralize validation rules
    public static function validationRules($id = null): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'slug' => [
                'required',
                'string',
                'max:255',
                Rule::unique('posts')->ignore($id)->where('type', 'Article'),
            ],
            'content' => ['required', 'string', 'min:100'],
            'published_at' => ['nullable', 'date', 'after_or_equal:today'],
        ];
    }
    
    // Custom validation messages
    public static function validationMessages(): array
    {
        return [
            'title.required' => 'Every article needs a title!',
            'content.min' => 'Articles should be at least 100 characters.',
        ];
    }
}
```

### 4. Security Patterns

```php
class Article extends Resource
{
    // Sanitize input
    protected static function booting()
    {
        static::saving(function ($article) {
            $article->title = strip_tags($article->title);
            $article->content = clean($article->content); // Use HTML purifier
        });
    }
    
    // Scope by permissions
    public function scopeAccessible($query)
    {
        if (!auth()->user()->isAdmin()) {
            $query->where(function ($q) {
                $q->where('user_id', auth()->id())
                  ->orWhere('status', 'published');
            });
        }
        
        return $query;
    }
}
```

### 5. Testing Resources

```php
class ArticleResourceTest extends TestCase
{
    public function test_article_creation_with_meta_fields()
    {
        $user = User::factory()->create();
        
        $article = Article::create([
            'title' => 'Test Article',
            'content' => 'Test content',
            'user_id' => $user->id,
            'meta' => [
                'featured' => true,
                'reading_time' => 5,
            ],
        ]);
        
        $this->assertEquals('Test Article', $article->title);
        $this->assertTrue($article->getMeta('featured'));
        $this->assertEquals(5, $article->getMeta('reading_time'));
    }
    
    public function test_article_query_scopes()
    {
        Article::factory()->count(5)->published()->create();
        Article::factory()->count(3)->draft()->create();
        
        $this->assertEquals(5, Article::published()->count());
        $this->assertEquals(3, Article::draft()->count());
    }
}
```

## Common Patterns

### Repository Pattern

```php
class ArticleRepository
{
    protected Article $model;
    
    public function __construct(Article $model)
    {
        $this->model = $model;
    }
    
    public function findPublished(int $limit = 10)
    {
        return $this->model
            ->with(['author', 'categories'])
            ->published()
            ->latest('published_at')
            ->limit($limit)
            ->get();
    }
    
    public function findByCategory(Category $category)
    {
        return $this->model
            ->whereHas('categories', fn($q) => $q->where('id', $category->id))
            ->published()
            ->get();
    }
}
```

### Service Pattern

```php
class ArticleService
{
    public function __construct(
        protected ArticleRepository $repository,
        protected MediaService $mediaService,
        protected NotificationService $notifications
    ) {}
    
    public function create(array $data): Article
    {
        DB::beginTransaction();
        
        try {
            $article = $this->repository->create($data);
            
            if (isset($data['featured_image'])) {
                $this->mediaService->attach($article, $data['featured_image']);
            }
            
            if ($article->status === 'published') {
                $this->notifications->notifySubscribers($article);
            }
            
            DB::commit();
            
            return $article;
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
```

### Factory Pattern

```php
class ResourceFactory
{
    public static function make(string $type): Resource
    {
        $class = match($type) {
            'article' => Article::class,
            'product' => Product::class,
            'page' => Page::class,
            default => throw new InvalidArgumentException("Unknown resource type: {$type}")
        };
        
        return new $class;
    }
}
```

## Troubleshooting

### Common Issues

1. **Fields not showing in forms**
   ```php
   // Check field configuration
   'on_forms' => true, // Must be true
   'conditional_logic' => [], // Check conditions
   ```

2. **Meta fields not saving**
   ```php
   // Ensure resource uses meta
   public static bool $usesMeta = true;
   
   // Check fillable doesn't include meta fields
   protected $fillable = ['title', 'content']; // Not meta fields
   ```

3. **Relationships not loading**
   ```php
   // Check field type and configuration
   [
       'type' => 'Aura\\Base\\Fields\\BelongsTo',
       'resource' => 'User', // Must match resource class
   ]
   ```

4. **Performance issues**
   ```php
   // Add eager loading
   protected $with = ['author', 'categories'];
   
   // Use custom table for large datasets
   public static bool $customTable = true;
   ```

> 📹 **Video Placeholder**: Debugging common Resource issues and performance optimization techniques

## Next Steps

Now that you understand Resources, explore:

1. 📝 **[Creating Resources](creating-resources.md)** - Step-by-step resource creation
2. 🎨 **[Fields Reference](fields.md)** - All 40+ field types
3. 🔧 **[Custom Fields](creating-fields.md)** - Build your own fields
4. 📊 **[Table Component](table.md)** - Advanced table features
5. 🔒 **[Permissions](roles-permissions.md)** - Access control

---

Resources are the foundation of every Aura CMS application. Master them, and you'll unlock the full potential of the platform. Happy building! 🚀