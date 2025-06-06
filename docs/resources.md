# Resources

> 📹 **Video Placeholder**: Complete overview of Aura CMS Resources - from basic concepts to advanced features

Resources are the heart of Aura CMS, transforming Laravel's Eloquent models into powerful, feature-rich content management entities. This comprehensive guide covers everything from basic resource creation to advanced patterns like soft deletes, versioning, and custom storage strategies.

## Table of Contents

- [Introduction](#introduction)
- [Creating Resources](#creating-resources)
- [Resource Properties](#resource-properties)
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
    public static string $type = 'Product';              // Resource type identifier
    public static ?string $slug = 'products';            // URL slug
    
    // === DISPLAY ===
    public static ?string $name = 'Product';             // Display name (singular)
    public static ?string $pluralName = 'Products';      // Display name (plural)
    public static ?string $singularName = 'Product';     // Explicit singular name
    public static ?string $icon = '<svg>...</svg>';      // Navigation icon
    
    // === NAVIGATION ===
    protected static ?string $group = 'Commerce';        // Navigation group
    protected static ?int $sort = 10;                    // Sort order (lower = higher)
    public static bool $showInNavigation = true;         // Show in sidebar
    protected static ?string $dropdown = null;           // Dropdown menu label
    
    // === FEATURES ===
    public static bool $globalSearch = true;             // Enable global search
    public static array $searchable = ['title', 'sku']; // Searchable fields
    public static bool $createEnabled = true;            // Allow creation
    public static bool $editEnabled = true;              // Allow editing
    public static bool $viewEnabled = true;              // Allow viewing
    public static bool $deleteEnabled = true;            // Allow deletion
    public static bool $indexViewEnabled = true;         // Show index page
    
    // === DATA STORAGE ===
    public static bool $customTable = false;             // Use custom table
    public static bool $usesMeta = true;                 // Use meta storage
    public static bool $taxonomy = false;                // Is taxonomy resource
    public static bool $title = true;                    // Has title field
    
    // === UI CONFIGURATION ===
    public static bool $showActionsAsButtons = false;    // Action display style
    public static bool $contextMenu = true;              // Enable context menu
    
    // === TABLE CONFIGURATION ===
    protected $table = 'products';                       // Custom table name
    protected $fillable = ['name', 'sku', 'price'];    // Mass assignable
    protected $casts = [                                 // Attribute casting
        'price' => 'decimal:2',
        'features' => 'array',
        'published' => 'boolean',
    ];
    protected $hidden = ['internal_notes'];              // Hidden attributes
    protected $appends = ['formatted_price'];            // Appended attributes
    protected $with = ['category', 'brand'];             // Eager load relations
}
```

### Dynamic Properties

Resources also support dynamic configuration through methods:

```php
class Product extends Resource
{
    // Dynamic icon based on status
    public static function getIcon()
    {
        if (auth()->user()->can('manage products')) {
            return '<svg class="text-green-500">...</svg>';
        }
        return '<svg>...</svg>';
    }
    
    // Conditional navigation display
    public static function shouldShowInNavigation(): bool
    {
        return auth()->user()->hasRole(['admin', 'editor']);
    }
}
```

## Resource Methods

### Core Methods Reference

```php
class Article extends Resource
{
    // === FIELD MANAGEMENT ===
    public static function getFields() { }              // Define fields
    public function fieldBySlug($slug) { }              // Get field by slug
    public function fieldClassBySlug($slug) { }         // Get field class instance
    public function fieldsCollection() { }              // Fields as collection
    public function inputFields() { }                   // Get input fields
    public function indexFields() { }                   // Get table fields
    public function viewFields() { }                    // Get view fields
    public function createFields() { }                  // Get create form fields
    public function editFields() { }                    // Get edit form fields
    
    // === DATA ACCESS ===
    public function getMeta($key = null) { }            // Get meta values
    public function display($key) { }                   // Display formatted value
    public function displayFieldValue($key, $value) { } // Format field value
    public function getFieldValue($key) { }             // Get raw value
    
    // === URLS ===
    public function indexUrl() { }                      // Index page URL
    public function createUrl() { }                     // Create page URL
    public function editUrl() { }                       // Edit page URL
    public function viewUrl() { }                       // View page URL
    
    // === DISPLAY ===
    public static function title() { }                  // Resource title
    public static function pluralName() { }             // Plural name
    public static function singularName() { }           // Singular name
    public static function getBadge() { }               // Navigation badge
    public static function getBadgeColor() { }          // Badge color
    
    // === PERMISSIONS ===
    public static function actions() { }                // Define actions
    public static function getActions() { }             // Get available actions
    public static function getBulkActions() { }         // Get bulk actions
    public function allowedToPerformAction($action) { } // Check permission
}
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
    
    // Computed properties
    public function getReadingTimeAttribute(): int
    {
        $words = str_word_count(strip_tags($this->content));
        return ceil($words / 200); // Average reading speed
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

### Standard Eloquent Relationships

```php
class Article extends Resource
{
    // BelongsTo
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

```php
// Query by meta field
$featured = Article::whereMeta('featured', '=', true)->get();

// Multiple meta conditions
$special = Article::whereMeta('featured', true)
    ->whereMeta('priority', '>', 5)
    ->get();

// Meta field with JSON
$tagged = Article::whereMetaContains('tags', 'laravel')->get();

// OR conditions
$highlighted = Article::whereMeta('featured', true)
    ->orWhereMeta('spotlight', true)
    ->get();

// IN queries
$selected = Article::whereInMeta('category', ['news', 'updates'])->get();
```

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

Resources automatically apply these scopes:

```php
// TypeScope - filters by resource type
Article::withoutGlobalScope(TypeScope::class)->get(); // All posts

// TeamScope - multi-tenancy
Article::withoutGlobalScope(TeamScope::class)->get(); // All teams

// ScopedScope - user-based filtering
Article::withoutGlobalScope(ScopedScope::class)->get(); // All users

// Custom global scope
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
    parent::booted();
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
    // Table defaults
    public static function defaultPerPage(): int
    {
        return 25;
    }
    
    public static function defaultTableSort(): string
    {
        return 'published_at';
    }
    
    public static function defaultTableSortDirection(): string
    {
        return 'desc';
    }
    
    public static function defaultTableView(): string
    {
        return 'table'; // 'table', 'grid', 'kanban'
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

```php
// Field values go through this process:
1. Input from form
2. Field validation (Laravel rules)
3. Field transformation (slugs, dates, etc.)
4. Conditional logic evaluation
5. SaveFieldAttributes trait processing
6. Database storage (table or meta)
7. SaveFields event dispatched
8. Post-save hooks
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