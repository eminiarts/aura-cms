# Meta Fields in Aura CMS

Meta fields in Aura CMS provide a flexible way to store additional data for your resources without modifying the database schema. This feature allows you to dynamically add custom fields to your resources while maintaining data integrity and performance.

## Table of Contents

- [Overview](#overview)
- [Configuration](#configuration)
- [Resource-Level Configuration](#resource-level-configuration)
- [Usage](#usage)
- [Querying Meta Fields](#querying-meta-fields)
- [Meta Field Storage](#meta-field-storage)
- [Custom Tables vs Meta Fields](#custom-tables-vs-meta-fields)
- [Migration and Data Transfer](#migration-and-data-transfer)
- [Events](#events)

## Overview

Meta fields allow you to:
- Store additional data for resources without altering table structures
- Dynamically add custom fields to resources
- Maintain flexibility in your data model
- Handle complex data types and relationships
- Query meta data using built-in scopes

## Configuration

### Global Configuration

You can control the default behavior for all resources in your configuration:

```php
// config/aura.php
return [
    'features' => [
        // false = use posts/meta tables (default)
        // true or 'single' = use custom tables
        // 'multiple' = generate separate migration files per resource
        'custom_tables_for_resources' => false,
    ],
];
```

### Defining Fields

Meta fields are defined in your resource's `getFields()` method:

```php
public static function getFields()
{
    return [
        [
            'name' => 'Custom Field',
            'slug' => 'custom_field',
            'type' => 'Aura\\Base\\Fields\\Text',
            'validation' => 'required|max:255',
        ],
        [
            'name' => 'Meta Description',
            'slug' => 'meta_description',
            'type' => 'Aura\\Base\\Fields\\Textarea',
        ]
    ];
}
```

## Resource-Level Configuration

Each resource can individually control whether it uses meta fields via two static properties:

### The `$usesMeta` Property

Controls whether the resource stores field data in the `meta` table:

```php
class Post extends Resource
{
    // Enable meta storage (default: true)
    public static bool $usesMeta = true;
}

class Role extends Resource
{
    // Disable meta storage - all fields stored in the resource's table
    public static bool $usesMeta = false;
}
```

### The `$customTable` Property

Controls whether the resource uses its own database table instead of the shared `posts` table:

```php
class Product extends Resource
{
    // Use a custom 'products' table instead of 'posts'
    public static $customTable = true;
    
    // Specify the table name (optional - defaults to pluralized class name)
    protected $table = 'products';
    
    // Can still use meta for additional fields
    public static bool $usesMeta = true;
}
```

### Checking Meta Configuration

Use the helper methods to check a resource's meta configuration:

```php
// Check if resource uses meta storage
$resource->usesMeta();      // returns bool

// Check if resource uses a custom table
$resource->usesCustomTable();  // returns bool

// Check if a specific field is stored in meta
$resource->isMetaField('custom_field');  // returns bool

// Check if a field is in the base table
$resource->isTableField('title');  // returns bool
```

## Usage

### Defining Meta Fields

1. **Basic Meta Field**:
```php
[
    'name' => 'Meta Title',
    'slug' => 'meta_title',
    'type' => 'Aura\\Base\\Fields\\Text',
]
```

2. **Meta Field with Validation**:
```php
[
    'name' => 'Meta Keywords',
    'slug' => 'meta_keywords',
    'type' => 'Aura\\Base\\Fields\\Text',
    'validation' => 'max:255',
]
```

3. **Complex Meta Field**:
```php
[
    'name' => 'Additional Data',
    'slug' => 'additional_data',
    'type' => 'Aura\\Base\\Fields\\JSON',
    'default' => '{}',
]
```

### Accessing Meta Fields

Meta fields are automatically accessible as properties on your resource models:

```php
// Get meta field value via the fields attribute
$value = $resource->fields['custom_field'];

// Or access directly (falls back to fields if not a table column)
$value = $resource->custom_field;

// Set meta field value using the fields array
$resource->fields = ['custom_field' => 'new value'];
$resource->save();
```

### Getting All Meta Data

Retrieve all meta values for a resource:

```php
// Get all meta as a collection (key => value)
$allMeta = $resource->getMeta();

// Get a specific meta value
$value = $resource->getMeta('custom_field');
```

### The Meta Relationship

Access the raw meta relationship for advanced queries:

```php
// Get the meta morphMany relationship
$resource->meta;  // Returns MetaCollection

// Access via relationship
$resource->meta()->where('key', 'custom_field')->first();
```

## Querying Meta Fields

Aura CMS provides several query scopes for filtering resources by meta values:

### Basic Where Queries

```php
// Simple key-value match
Post::whereMeta('status', 'published')->get();

// With operator
Post::whereMeta('views', '>', 100)->get();

// Multiple conditions (AND)
Post::whereMeta(['status' => 'published', 'featured' => true])->get();
```

### Or Where Queries

```php
// Simple OR condition
Post::whereMeta('status', 'published')
    ->orWhereMeta('featured', true)
    ->get();

// With operator
Post::orWhereMeta('priority', '>=', 5)->get();

// Multiple OR conditions
Post::orWhereMeta(['status' => 'draft', 'archived' => true])->get();
```

### Where In / Not In Queries

```php
// Match any of multiple values
Post::whereInMeta('category', ['tech', 'science', 'health'])->get();

// Exclude specific values
Post::whereNotInMeta('status', ['archived', 'deleted'])->get();
```

### JSON Contains Query

For meta fields storing JSON arrays:

```php
// Check if JSON array contains a value
Post::whereMetaContains('tags', 'laravel')->get();

// Works with numeric values too
Post::whereMetaContains('author_ids', 5)->get();
```

## Meta Field Storage

Meta fields are stored in the `meta` table using Laravel's polymorphic relationship pattern:

| Column | Type | Description |
|--------|------|-------------|
| `id` | bigint | Primary key |
| `metable_type` | string | The model class (e.g., `App\Aura\Resources\Post`) |
| `metable_id` | bigint | The ID of the parent resource |
| `key` | string | The field slug |
| `value` | longText | The stored value (serialized if complex) |

### Database Schema

```php
Schema::create('meta', function (Blueprint $table) {
    $table->id();
    $table->morphs('metable');  // Creates metable_type and metable_id
    $table->string('key')->nullable()->index();
    $table->longText('value')->nullable();

    // Composite index for efficient lookups
    $table->index(['metable_type', 'metable_id', 'key']);
});

// Additional MySQL-specific index for value lookups (first 255 chars)
if (config('database.default') === 'mysql') {
    DB::statement('CREATE INDEX idx_meta_metable_id_key_value ON meta (metable_id, `key`, value(255));');
}
```

### The Meta Model

The `Aura\Base\Models\Meta` model represents individual meta entries:

```php
use Aura\Base\Models\Meta;

// Meta model properties
$meta->key;           // Field slug
$meta->value;         // Stored value
$meta->metable_type;  // Parent model class
$meta->metable_id;    // Parent model ID
$meta->metable;       // Parent model instance (via morphTo)
```

The Meta model uses a custom `MetaCollection` class that allows direct property access:

```php
// Instead of iterating to find a key
$resource->meta->custom_field;  // Returns the value directly
```

## Custom Tables vs Meta Fields

### Configuration Combinations

| `$customTable` | `$usesMeta` | Storage Behavior |
|----------------|-------------|------------------|
| `false` | `true` | Posts table + Meta table (default) |
| `false` | `false` | Posts table only (limited fields) |
| `true` | `true` | Custom table + Meta table for overflow |
| `true` | `false` | Custom table only (all fields in table) |

### Meta Fields Approach (`$customTable = false`)

**Pros**:
- Flexible schema - add fields without migrations
- Quick prototyping and development
- Works well with dynamic/user-defined data
- Shared `posts` table reduces database complexity

**Cons**:
- Slower queries for large datasets (JOIN required)
- Complex sorting and filtering on meta fields
- Limited indexing options for meta values
- EAV (Entity-Attribute-Value) pattern overhead

### Custom Tables Approach (`$customTable = true`)

**Pros**:
- Better query performance (direct column access)
- Full indexing capabilities on any column
- Cleaner database design for stable schemas
- Direct relationships with foreign keys

**Cons**:
- Requires migrations for schema changes
- Less flexible for dynamic fields
- More maintenance overhead
- Separate table per resource type

### Hybrid Approach (`$customTable = true`, `$usesMeta = true`)

Best of both worlds - use a custom table for frequently queried fields while keeping meta available for optional/dynamic fields:

```php
class Product extends Resource
{
    public static $customTable = true;
    public static bool $usesMeta = true;
    protected $table = 'products';
    
    // Base table columns defined in migration
    protected $fillable = ['name', 'price', 'sku', 'status'];
    
    public static function getFields()
    {
        return [
            // These go in the products table
            ['slug' => 'name', 'type' => 'Aura\\Base\\Fields\\Text'],
            ['slug' => 'price', 'type' => 'Aura\\Base\\Fields\\Number'],
            
            // These go in meta table (not in baseFillable)
            ['slug' => 'custom_attributes', 'type' => 'Aura\\Base\\Fields\\JSON'],
        ];
    }
}
```

## Migration and Data Transfer

### Converting from Meta to Custom Tables

Aura CMS provides Artisan commands to help migrate resources from the posts/meta storage to custom tables.

#### Interactive Migration (Recommended)

The interactive command guides you through the entire process:

```bash
php artisan aura:migrate-from-posts-to-custom-table
```

This command will:
1. Prompt you to select a resource
2. Automatically add `$customTable = true` to your resource class
3. Add the `$table` property with the correct table name
4. Generate a migration file with all field columns
5. Optionally run the migration
6. Optionally transfer existing data

#### Step-by-Step Migration

For more control, run the steps individually:

**Step 1: Generate the migration**

```bash
php artisan aura:create-resource-migration "App\\Aura\\Resources\\Product"
```

This creates a migration based on your resource's fields, including appropriate column types.

**Step 2: Update your resource class**

```php
class Product extends Resource
{
    public static $customTable = true;
    
    // Optional: disable meta if storing everything in the table
    public static bool $usesMeta = false;
    
    protected $table = 'products';
}
```

**Step 3: Run the migration**

```bash
php artisan migrate
```

**Step 4: Transfer existing data**

```bash
php artisan aura:transfer-from-posts-to-custom-table "App\\Aura\\Resources\\Product"
```

This command:
- Fetches all records from `posts` table matching your resource type
- Retrieves associated meta data for each record
- Creates new records in your custom table
- Combines both table columns and meta values

> **Note**: The transfer command does not delete the original data from posts/meta tables. Verify the migration was successful before manually cleaning up old data.

## Events

Aura CMS fires events during the meta saving process that you can hook into:

### The `metaSaved` Event

Fired after all meta fields have been saved for a resource:

```php
use Illuminate\Support\Facades\Event;

// In a service provider or listener
Event::listen('eloquent.metaSaved: App\\Aura\\Resources\\Post', function ($post) {
    // Perform actions after meta is saved
    Cache::forget("post-{$post->id}-meta");
});
```

You can also use model observers:

```php
class PostObserver
{
    public function metaSaved(Post $post)
    {
        // Meta fields have been saved
    }
}
```

### Custom Field Setters

Define custom setter methods for specific fields:

```php
class Product extends Resource
{
    // Called when 'price' meta field is being saved
    public function setPriceField($value)
    {
        // Transform or validate the value
        $this->meta()->updateOrCreate(
            ['key' => 'price'],
            ['value' => round($value, 2)]
        );
        
        return $this;
    }
}
```
