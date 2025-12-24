# Custom Tables in Aura CMS

Custom tables in Aura CMS provide a way to store resource data in dedicated database tables instead of using the default posts and meta tables. This approach offers better performance, more direct database relationships, and improved query capabilities for resources with specific data structures.

## Table of Contents

- [Overview](#overview)
- [Storage Strategies](#storage-strategies)
- [Configuration](#configuration)
- [Migration Process](#migration-process)
- [Converting Existing Resources](#converting-existing-resources)
- [Hybrid Approach](#hybrid-approach)
- [Performance Considerations](#performance-considerations)

## Overview

By default, Aura CMS stores resource data in two tables:
- `posts`: Stores basic resource information (id, type, title, content, status, etc.)
- `meta`: Stores additional field data as key-value pairs (polymorphic relationship)

Custom tables allow you to:
- Create dedicated tables for specific resources
- Define precise database schemas with proper column types
- Optimize database queries (no JOINs to meta table)
- Establish direct foreign key relationships
- Improve performance for large datasets
- Use database-level constraints and indexes

## Storage Strategies

Aura CMS supports three storage strategies:

| Strategy | $customTable | $usesMeta | Description |
|----------|--------------|-----------|-------------|
| Default | `false` | `true` | Uses `posts` table with `meta` for fields |
| Custom Table Only | `true` | `false` | Dedicated table, no meta storage |
| Hybrid | `true` | `true` | Dedicated table + meta for overflow fields |

## Configuration

### Enabling Custom Tables

To use a custom table for a resource, configure these properties:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Product extends Resource
{
    // Enable custom table storage
    public static bool $customTable = true;

    // Disable meta table usage (optional, default is true)
    public static bool $usesMeta = false;

    // Define the table name (must match your migration)
    protected $table = 'products';

    // Define fillable fields (required for custom tables)
    protected $fillable = [
        'name',
        'price',
        'description',
        'status',
        'user_id',
        'team_id',
    ];

    // Define casts for proper type handling
    protected $casts = [
        'price' => 'decimal:2',
        'options' => 'array',
        'is_active' => 'boolean',
    ];

    public static string $type = 'Product';

    public static ?string $slug = 'product';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|max:255',
            ],
            [
                'name' => 'Price',
                'slug' => 'price',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'required|numeric|min:0',
            ],
            // ... more fields
        ];
    }
}
```

### Key Properties

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$customTable` | `bool` | `false` | Enable dedicated table storage |
| `$usesMeta` | `bool` | `true` | Store overflow fields in meta table |
| `$table` | `string` | `'posts'` | Database table name |
| `$fillable` | `array` | `[]` | Fields that can be mass assigned |
| `$casts` | `array` | `[]` | Attribute type casting |

### Global Configuration

You can configure the auto-migration behavior for custom tables in `config/aura.php`:

```php
return [
    'features' => [
        // Options: false, true, 'single', 'multiple'
        'custom_tables_for_resources' => false,
    ],
];
```

Configuration values:
- `false` (default): No automatic migration generation
- `true` or `'single'`: Generate a single migration file that gets updated
- `'multiple'`: Generate separate migration files for each change

## Migration Process

### 1. Generate Migration

Use the built-in command to generate a migration for your resource:

```bash
# Use the full class name
php artisan aura:create-resource-migration "App\Aura\Resources\Product"
```

This command will:
- Create a migration file based on your resource's `getFields()` definition
- Generate appropriate column types based on field types
- Include standard columns (id, user_id, team_id, timestamps)

### 2. Review Migration

The generated migration will include columns based on your field definitions. Each field type maps to a specific column type:

| Field Type | Column Type |
|------------|-------------|
| `Text`, `Email`, `Slug`, `Select`, `Radio` | `string` |
| `Textarea`, `Wysiwyg` | `text` |
| `Number` | `integer` |
| `Boolean`, `Toggle` | `string` (default) |
| `Date` | `date` |
| `DateTime` | `timestamp` |
| `BelongsTo` | `bigInteger` |
| `ID` | `bigIncrements` |

> **Note**: Most fields default to `string` column type. You can customize the migration after generation to use more specific types like `decimal` for prices.

Example generated migration:

```php
<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->id();
            $table->string('name')->nullable();
            $table->string('price')->nullable();
            $table->text('description')->nullable();
            $table->string('status')->nullable();
            $table->foreignId('user_id');
            $table->foreignId('team_id');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
```

### 3. Customize and Run Migration

Before running the migration, you may want to customize it:

```php
Schema::create('products', function (Blueprint $table) {
    $table->id();
    $table->string('name');  // Remove nullable if required
    $table->decimal('price', 10, 2)->default(0);  // Use decimal for money
    $table->text('description')->nullable();
    $table->string('status')->default('draft');
    $table->boolean('is_active')->default(true);
    $table->json('options')->nullable();

    // Foreign keys
    $table->foreignId('user_id')->constrained()->onDelete('cascade');
    $table->foreignId('team_id')->nullable()->constrained()->onDelete('cascade');

    // Indexes for performance
    $table->index('status');
    $table->index(['team_id', 'status']);

    $table->timestamps();
    $table->softDeletes();
});
```

Then run the migration:

```bash
php artisan migrate
```

## Converting Existing Resources

### From Posts/Meta to Custom Table

Use the interactive migration command to convert an existing resource:

```bash
php artisan aura:migrate-from-posts-to-custom-table
```

This command will:
1. Present a list of available resources to choose from
2. Modify the resource class to add `$customTable = true`
3. Add the `$table` property with the appropriate table name
4. Generate the migration file
5. Optionally run the migration
6. Optionally transfer existing data

You can also specify the resource directly:

```bash
php artisan aura:migrate-from-posts-to-custom-table "App\Aura\Resources\Product"
```

### Data Transfer Process

After the table is created, transfer data using:

```bash
php artisan aura:transfer-from-posts-to-custom-table "App\Aura\Resources\Product"
```

The transfer process:
1. Fetches all posts matching the resource type
2. Retrieves associated meta data for each post
3. Combines post fields and meta fields
4. Creates new records in the custom table
5. Preserves timestamps and team associations
6. Shows progress during transfer

### Best Practices

1. **Before Migration**
   - Back up your database completely
   - Test in development/staging environment first
   - Review resource configuration and field mappings
   - Ensure all field slugs match intended column names
   - Verify fillable array includes all necessary fields

2. **During Migration**
   - Monitor the transfer process
   - Check for any error messages
   - Verify data integrity with spot checks

3. **After Migration**
   - Verify all CRUD operations work correctly
   - Check relationships load properly
   - Test search and filtering functionality
   - Verify table sorting works
   - Monitor query performance
   - Consider cleaning up old posts/meta data after verification

## Hybrid Approach

You can use custom tables while still leveraging the meta system for additional fields. This is useful when you want:
- Core fields in dedicated columns for performance
- Flexible meta storage for optional/dynamic fields

```php
class Product extends Resource
{
    public static bool $customTable = true;
    public static bool $usesMeta = true;  // Enable meta alongside custom table

    protected $table = 'products';

    // Only core fields in the table
    protected $fillable = [
        'name',
        'price',
        'status',
        'user_id',
        'team_id',
    ];

    public static function getFields(): array
    {
        return [
            // These are stored in the products table
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
            ],
            [
                'name' => 'Price',
                'slug' => 'price',
                'type' => 'Aura\\Base\\Fields\\Number',
            ],
            // These are stored in the meta table (not in $fillable)
            [
                'name' => 'SEO Description',
                'slug' => 'seo_description',
                'type' => 'Aura\\Base\\Fields\\Textarea',
            ],
            [
                'name' => 'Custom Attributes',
                'slug' => 'custom_attributes',
                'type' => 'Aura\\Base\\Fields\\Repeater',
            ],
        ];
    }
}
```

Fields in `$fillable` are stored in the custom table; other fields go to the meta table.

## Performance Considerations

### When to Use Custom Tables

| Scenario | Recommendation |
|----------|----------------|
| < 1,000 records, simple fields | Default posts/meta is fine |
| > 10,000 records | Consider custom tables |
| Complex queries with filters | Use custom tables |
| Reporting/analytics needs | Use custom tables |
| Direct database access needed | Use custom tables |
| Rapid prototyping | Start with posts/meta |

### Query Performance Benefits

Custom tables provide significant performance improvements:

```php
// With posts/meta (requires JOINs)
// Slower for complex queries

// With custom tables (direct queries)
Product::where('status', 'active')
    ->where('price', '>', 100)
    ->orderBy('created_at', 'desc')
    ->get();
```

### Indexing Recommendations

Add indexes for frequently queried columns:

```php
Schema::create('products', function (Blueprint $table) {
    // ... columns ...

    // Single column indexes
    $table->index('status');
    $table->index('price');

    // Composite indexes for common queries
    $table->index(['team_id', 'status']);
    $table->index(['status', 'created_at']);
});
```

### Built-in Resources Using Custom Tables

Aura CMS uses custom tables for core resources as a reference:

| Resource | Table | Uses Meta |
|----------|-------|-----------|
| `User` | `users` | Yes |
| `Team` | `teams` | Yes |
| `Role` | `roles` | No |
| `Permission` | `permissions` | No |
| `Option` | `options` | No |

Examine these resources in `src/Resources/` for implementation patterns.

Remember to always test migrations in a development environment before applying them to production, and maintain proper backups throughout the process.
