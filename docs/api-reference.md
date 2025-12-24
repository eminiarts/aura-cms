# API Reference

This document covers the PHP API for interacting with Aura CMS programmatically. It includes the Aura Facade, Resource class methods, and utility classes that power the CMS.

## Table of Contents

- [Aura Facade](#aura-facade)
- [Resource Class](#resource-class)
- [DynamicFunctions](#dynamicfunctions)
- [ConditionalLogic](#conditionallogic)
- [Building Custom REST APIs](#building-custom-rest-apis)

## Aura Facade

The `Aura` facade (`Aura\Base\Facades\Aura`) provides access to core CMS functionality. It proxies to the `Aura\Base\Aura` class.

### Resource Management

```php
use Aura\Base\Facades\Aura;

// Get all registered resources
$resources = Aura::getResources();
// Returns: ['App\Aura\Resources\Post', 'App\Aura\Resources\Page', ...]

// Register resources programmatically
Aura::registerResources([
    \App\Aura\Resources\Product::class,
    \App\Aura\Resources\Category::class,
]);

// Find a resource by its slug
$resource = Aura::findResourceBySlug('post');
// Returns: App\Aura\Resources\Post instance

// Get app-defined resources (from configured path)
$appResources = Aura::getAppResources();
```

### Field Management

```php
// Get all registered field types
$fields = Aura::getFields();
// Returns: ['Aura\Base\Fields\Text', 'Aura\Base\Fields\Number', ...]

// Get fields organized by group
$fieldGroups = Aura::getFieldsWithGroups();
// Returns: ['Fields' => ['Text' => 'Aura\Base\Fields\Text', ...], 'Relations' => [...]]

// Register custom field types
Aura::registerFields([
    \App\Fields\CustomField::class,
]);

// Get app-defined fields
$appFields = Aura::getAppFields();
```

### Widget Management

```php
// Get all registered widgets
$widgets = Aura::getWidgets();

// Register custom widgets
Aura::registerWidgets([
    \App\Widgets\SalesChart::class,
    \App\Widgets\RecentOrders::class,
]);

// Get app-defined widgets
$appWidgets = Aura::getAppWidgets();
```

### Options & Settings

```php
// Get an option value (cached, team-aware)
$settings = Aura::getOption('site-settings');
// Returns decoded JSON or array

// Update an option
Aura::updateOption('site-settings', ['logo' => 'path/to/logo.png']);

// Get a specific config value from aura.php
$value = Aura::option('features.teams');

// Get all aura config options
$allOptions = Aura::options();
// Returns: config('aura')
```

### Navigation

```php
// Get the navigation structure (cached per user/team)
$navigation = Aura::navigation();
// Returns grouped navigation items based on user permissions
```

### Route Registration

```php
// Register CRUD routes for a resource
Aura::registerRoutes('products');
// Creates: /admin/products, /admin/products/create, /admin/products/{id}, /admin/products/{id}/edit

// Clear route caches
Aura::clearRoutes();

// Clear all caches
Aura::clear();
```

### View Injection

```php
// Register a view injection hook
Aura::registerInjectView('dashboard.sidebar', function () {
    return view('my-sidebar-widget');
});

// Render injected views (in Blade templates)
{!! Aura::injectView('dashboard.sidebar') !!}

// Get all registered injection points
$injections = Aura::getInjectViews();
```

### Asset Management

```php
// Get compiled scripts view
{!! Aura::scripts() !!}

// Get compiled styles view  
{!! Aura::styles() !!}

// Vite integration for development
{!! Aura::viteScripts() !!}
{!! Aura::viteStyles() !!}

// Check if published assets are current
if (!Aura::assetsAreCurrent()) {
    // Assets need republishing
}
```

### User Model Configuration

```php
// Get the configured user model class
$userModel = Aura::userModel();
// Returns: 'Aura\Base\Resources\User' (default)

// Set a custom user model
Aura::useUserModel(\App\Models\CustomUser::class);
```

### Templates

```php
// Get all registered templates
$templates = Aura::templates();

// Find a template by slug
$template = Aura::findTemplateBySlug('panel-with-sidebar');
```

### Utilities

```php
// Check conditional logic for a field
$shouldShow = Aura::checkCondition($model, $field, $post);

// Clear conditional logic cache
Aura::clearConditionsCache();

// Get attachment path by ID
$path = Aura::getPath($attachmentId);

// Export array as PHP code
$code = Aura::varexport($array, true);
```

## Resource Class

The `Aura\Base\Resource` class is the base for all Aura resources. It extends Eloquent Model and provides extensive functionality.

### Static Properties

```php
class Post extends Resource
{
    // Resource type identifier
    public static string $type = 'Post';
    
    // URL slug for routes
    protected static ?string $slug = 'post';
    
    // Display names
    public static $singularName = 'Post';
    public static $pluralName = 'Posts';
    
    // Navigation group
    protected static ?string $group = 'Content';
    
    // Sort order in navigation
    protected static ?int $sort = 10;
    
    // Feature flags
    public static $createEnabled = true;
    public static $editEnabled = true;
    public static $viewEnabled = true;
    public static bool $indexViewEnabled = true;
    public static $contextMenu = true;
    public static $globalSearch = true;
    protected static bool $showInNavigation = true;
    
    // Table configuration
    public static $customTable = false;  // Use posts table with type column
    public static bool $usesMeta = true; // Store extra fields in meta table
    protected static bool $title = false; // Has title column
    
    // Dropdown grouping in navigation
    protected static $dropdown = false;
    
    // Taxonomy resource
    public static $taxonomy = false;
    
    // Show actions as buttons instead of dropdown
    public static $showActionsAsButtons = false;
}
```

### Defining Fields

```php
public static function getFields(): array
{
    return [
        [
            'type' => 'Aura\\Base\\Fields\\Text',
            'name' => 'Title',
            'slug' => 'title',
            'validation' => 'required|max:255',
            'on_index' => true,
            'searchable' => true,
        ],
        [
            'type' => 'Aura\\Base\\Fields\\Textarea',
            'name' => 'Content',
            'slug' => 'content',
            'on_index' => false,
        ],
        [
            'type' => 'Aura\\Base\\Fields\\BelongsTo',
            'name' => 'Category',
            'slug' => 'category_id',
            'resource' => 'App\\Aura\\Resources\\Category',
        ],
    ];
}
```

### Field Methods

```php
$resource = new Post();

// Get all input field slugs
$slugs = $resource->inputFieldsSlugs();
// Returns: ['title', 'content', 'category_id', ...]

// Get a field definition by slug
$field = $resource->fieldBySlug('title');
// Returns: ['type' => 'Aura\Base\Fields\Text', 'name' => 'Title', ...]

// Get the field class instance
$fieldClass = $resource->fieldClassBySlug('title');
// Returns: Aura\Base\Fields\Text instance

// Get all input fields as collection
$fields = $resource->inputFields();

// Get fields for different contexts
$createFields = $resource->createFields();  // Filtered for create form
$editFields = $resource->editFields();      // Filtered for edit form
$viewFields = $resource->viewFields();      // Filtered for view page
$indexFields = $resource->indexFields();    // Fields shown in table

// Get fields with IDs assigned
$fieldsWithIds = $resource->getFieldsWithIds();

// Get grouped/nested fields structure
$grouped = $resource->getGroupedFields();

// Check if field should display based on conditional logic
$shouldShow = $resource->shouldDisplayField($field);

// Display a field value (applies field transformations)
$displayValue = $resource->display('title');
$displayValue = $resource->displayFieldValue('status', 'active');

// Get searchable fields
$searchable = $resource->getSearchableFields();

// Get validation rules for all fields
$rules = $resource->validationRules();
// Returns: ['title' => 'required|max:255', 'status' => 'required', ...]

// Get validation rules prefixed for Livewire forms
$formRules = $resource->resourceFieldValidationRules();
// Returns: ['form.fields.title' => 'required|max:255', ...]
```

### Meta Fields

```php
// Check if resource uses meta table
$usesMeta = Post::usesMeta();

// Check if using custom table
$customTable = Post::usesCustomTable();

// Get meta values
$meta = $post->getMeta();           // All meta as collection
$value = $post->getMeta('custom');  // Specific meta key

// Check if a field is stored in meta
$isMeta = $post->isMetaField('custom_field');

// Check if field is in main table
$isTable = $post->isTableField('title');

// Query by meta values
Post::whereMeta('status', 'published')->get();
Post::whereMeta('views', '>', 100)->get();
Post::whereMeta(['status' => 'published', 'featured' => true])->get();

Post::orWhereMeta('status', 'draft')->get();
Post::whereInMeta('category', [1, 2, 3])->get();
Post::whereNotInMeta('category', [4, 5])->get();
Post::whereMetaContains('tags', 'featured')->get(); // JSON contains
```

### URL Methods

```php
$post = Post::find(1);

// Get various URLs
$indexUrl = $post->indexUrl();    // /admin/post
$createUrl = $post->createUrl();  // /admin/post/create
$editUrl = $post->editUrl();      // /admin/post/1/edit
$viewUrl = $post->viewUrl();      // /admin/post/1

// Get index route
$route = $post->getIndexRoute();
```

### View Methods

```php
// Get view paths for customization
$post->indexView();       // 'aura::livewire.resource.index'
$post->createView();      // 'aura::livewire.resource.create'
$post->editView();        // 'aura::livewire.resource.edit'
$post->viewView();        // 'aura::livewire.resource.view'

// Header views
$post->editHeaderView();  // 'aura::livewire.resource.edit-header'
$post->viewHeaderView();  // 'aura::livewire.resource.view-header'

// Table views
$post->tableView();           // 'aura::components.table.list-view'
$post->rowView();             // 'aura::components.table.row'
$post->tableComponentView();  // 'aura::livewire.table'
```

### Actions & Bulk Actions

```php
// Define actions on resource
public array $actions = [
    'publish' => 'Publish',
    'archive' => 'Archive',
];

// Or as a method for dynamic actions
public function actions(): array
{
    return [
        'publish' => 'Publish',
    ];
}

// Define bulk actions
public array $bulkActions = [
    'delete' => 'Delete Selected',
    'export' => 'Export',
];

// Get configured actions
$actions = $post->getActions();
$bulkActions = $post->getBulkActions();
```

### Table Configuration

```php
// Default table settings
public function defaultPerPage(): int
{
    return 10;
}

public function defaultTableSort(): string
{
    return 'id';
}

public function defaultTableSortDirection(): string
{
    return 'desc';
}

public function defaultTableView(): string
{
    return 'list';  // 'list', 'grid', 'kanban'
}

// Enable different view modes
public function tableGridView(): bool
{
    return true;
}

public function tableKanbanView(): bool
{
    return false;
}

public function kanbanQuery($query)
{
    return false; // Return query for kanban grouping
}

public function showTableSettings(): bool
{
    return true;
}

// Get table headers
$headers = $post->getHeaders();
```

### Navigation Configuration

```php
// Get navigation data for this resource
$nav = $post->navigation();
// Returns: [
//     'icon' => '<svg>...</svg>',
//     'resource' => 'App\Aura\Resources\Post',
//     'type' => 'Post',
//     'name' => 'Posts',
//     'slug' => 'post',
//     'sort' => 10,
//     'group' => 'Content',
//     'route' => '/admin/post',
//     'dropdown' => false,
//     'showInNavigation' => true,
//     'badge' => null,
//     'badgeColor' => null,
// ]

// Custom icon
public function getIcon(): string
{
    return '<svg>...</svg>';
}

// Badge for navigation item
public function getBadge()
{
    return Post::count();
}

public function getBadgeColor()
{
    return 'red';
}
```

### Widgets

```php
// Define widgets for resource dashboard
public static function getWidgets(): array
{
    return [
        [
            'type' => 'Aura\\Base\\Widgets\\ValueWidget',
            'name' => 'Total Posts',
            // widget configuration...
        ],
    ];
}

// Widget time range settings
public array $widgetSettings = [
    'default' => '30d',
    'options' => [
        '7d' => '7 Days',
        '30d' => '30 Days',
        // ...
    ],
];

// Get widgets
$widgets = $post->widgets();
```

### Relationships

```php
// Built-in relationships
$user = $post->user();      // BelongsTo user
$team = $post->team();      // BelongsTo team
$parent = $post->parent();  // BelongsTo parent (self-referential)
$children = $post->children(); // HasMany children
$meta = $post->meta();      // MorphMany meta records

// Dynamic relationships from fields are auto-generated
$category = $post->category; // From BelongsTo field
$tags = $post->tags;         // From Tags/HasMany field
```

### Accessors

```php
// Get all field values (with conditional logic applied)
$fields = $post->fields;
// Returns collection of field slug => value pairs

// Get field values without conditional logic filtering
$allFields = $post->getFieldsWithoutConditionalLogic();

// Access field values directly
$title = $post->title;
$category = $post->category;

// Title generation
$displayTitle = $post->title(); // "Post (#1)"

// Get singular/plural names
$singular = $post->singularName(); // "Post"  
$plural = $post->pluralName();     // "Posts"
```

### Utility Methods

```php
// Check resource type
$isApp = $post->isAppResource();      // Starts with 'App\'
$isVendor = $post->isVendorResource(); // Package resource

// Check field types
$isTaxonomy = $post->isTaxonomy();
$isTaxonomyField = $post->isTaxonomyField('tags');
$isNumberField = $post->isNumberField('price');

// Clear cached field values
$post->clearFieldsAttributeCache();

// Get base fillable columns
$baseFillable = $post->getBaseFillable();
$isBaseFillable = $post->isBaseFillable('title');
```

## DynamicFunctions

The `DynamicFunctions` class allows registering and calling closures dynamically, used primarily for conditional logic.

```php
use Aura\Base\Facades\DynamicFunctions;

// Register a closure, returns a hash
$hash = DynamicFunctions::add(function () {
    return auth()->user()->isAdmin();
});

// Call the registered closure by hash
$result = DynamicFunctions::call($hash);
```

## ConditionalLogic

The `ConditionalLogic` class handles field visibility based on conditions.

```php
use Aura\Base\ConditionalLogic;

// Check if a field should be displayed
$shouldShow = ConditionalLogic::shouldDisplayField($model, $field, $formData);

// Check if field is visible to a specific user (role-based)
$isVisible = ConditionalLogic::fieldIsVisibleTo($field, $user);

// Clear the conditions cache
ConditionalLogic::clearConditionsCache();
```

## Building Custom REST APIs

Aura CMS provides the foundation for building your own REST APIs using Laravel's standard patterns.

### Basic API Controller

```php
namespace App\Http\Controllers\Api;

use App\Aura\Resources\Post;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;

class PostController extends Controller
{
    public function index(Request $request)
    {
        $query = Post::query();
        
        // Apply search
        if ($request->has('search')) {
            $searchable = (new Post)->getSearchableFields();
            $query->where(function ($q) use ($request, $searchable) {
                foreach ($searchable as $field) {
                    $q->orWhere($field['slug'], 'like', '%' . $request->search . '%');
                }
            });
        }
        
        // Apply filters using meta
        if ($request->has('status')) {
            $query->whereMeta('status', $request->status);
        }
        
        return $query->paginate($request->get('per_page', 15));
    }
    
    public function show(Post $post)
    {
        return response()->json([
            'data' => $post->fields,
        ]);
    }
    
    public function store(Request $request)
    {
        $post = new Post();
        $rules = $post->validationRules();
        
        $validated = $request->validate($rules);
        
        $post = Post::create($validated);
        
        return response()->json(['data' => $post], 201);
    }
    
    public function update(Request $request, Post $post)
    {
        $rules = $post->validationRules();
        $validated = $request->validate($rules);
        
        $post->update($validated);
        
        return response()->json(['data' => $post]);
    }
    
    public function destroy(Post $post)
    {
        $post->delete();
        
        return response()->json(['message' => 'Deleted successfully']);
    }
}
```

### API Routes

```php
// routes/api.php
use App\Http\Controllers\Api\PostController;
use Illuminate\Support\Facades\Route;

Route::middleware('auth:sanctum')->group(function () {
    Route::apiResource('posts', PostController::class);
});
```

### Using Resource API Transformers

```php
namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class PostResource extends JsonResource
{
    public function toArray($request): array
    {
        return [
            'id' => $this->id,
            'type' => $this->getType(),
            'fields' => $this->fields,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }
}
```

### Dynamic Resource API

```php
use Aura\Base\Facades\Aura;

Route::get('/api/resources', function () {
    return collect(Aura::getResources())->map(function ($class) {
        $resource = app($class);
        return [
            'type' => $resource->getType(),
            'slug' => $resource->getSlug(),
            'fields' => $resource::getFields(),
        ];
    });
});

Route::get('/api/{resource}', function (string $resource) {
    $resourceInstance = Aura::findResourceBySlug($resource);
    
    if (!$resourceInstance) {
        abort(404, 'Resource not found');
    }
    
    return $resourceInstance::paginate(15);
});
```

The Aura CMS PHP API provides a powerful foundation for building content management systems, custom admin panels, and integrating with external services.