# Custom Tables in Aura CMS

Custom tables in Aura CMS provide a way to store resource data in dedicated database tables instead of using the default posts and meta tables. This approach offers better performance, more direct database relationships, and improved query capabilities for resources with specific data structures.

## Table of Contents

- [Overview](#overview)
- [Configuration](#configuration)
- [Migration Process](#migration-process)
- [Converting Existing Resources](#converting-existing-resources)
- [Performance Considerations](#performance-considerations)

## Overview

By default, Aura CMS stores resource data in two tables:
- `posts`: Stores basic resource information
- `meta`: Stores additional field data

Custom tables allow you to:
- Create dedicated tables for specific resources
- Define precise database schemas
- Optimize database queries
- Establish direct relationships
- Improve performance for large datasets

## Configuration

### Enabling Custom Tables

To use a custom table for a resource:

```php
class CustomResource extends Resource
{
    // Enable custom table
    public static $customTable = true;

    // Define table name (optional)
    protected $table = 'custom_resources';

    // Define fillable fields
    protected $fillable = [
        'title',
        'content',
        'status',
        // ... other fields
    ];
}
```

### Global Configuration

You can enable custom tables globally in your `config/aura.php`:

```php
return [
    'features' => [
        'custom_tables_for_resources' => true,
    ],
];
```

## Migration Process

### 1. Generate Migration

Use the built-in command to generate a migration for your resource:

```bash
php artisan aura:create-resource-migration YourResource
```

This command will:
- Create a migration file based on your resource fields
- Update your resource class with custom table configuration
- Set the appropriate table name

### 2. Review Migration

The generated migration will include:
- Basic fields (id, timestamps, etc.)
- Custom fields based on your resource definition
- Team support if enabled
- Proper indexes for optimization

Example migration structure:

```php
Schema::create('custom_resources', function (Blueprint $table) {
    $table->id();
    $table->string('title')->nullable();
    $table->text('content')->nullable();
    $table->string('status')->default('draft');
    // ... other fields

    if (config('aura.teams')) {
        $table->foreignId('team_id')->nullable()->constrained();
    }

    $table->timestamps();
    $table->softDeletes();
});
```

## Converting Existing Resources

### From Posts/Meta to Custom Table

Use the migration command to convert an existing resource:

```bash
php artisan aura:migrate-from-posts-to-custom-table YourResource
```

This command will:
1. Generate the appropriate migration
2. Modify the resource class
3. Optionally run the migration
4. Transfer existing data

### Data Transfer Process

The transfer process:
1. Creates the new table structure
2. Moves basic data from posts table
3. Transfers meta fields to dedicated columns
4. Maintains relationships and timestamps
5. Preserves team associations if enabled


### Best Practices

1. **Before Migration**
   - Back up your database
   - Test in development environment
   - Review resource configuration
   - Document field mappings

2. **During Migration**
   - Monitor the process
   - Keep error logs
   - Verify data integrity
   - Test functionality

3. **After Migration**
   - Verify all features work
   - Check relationships
   - Test search functionality
   - Monitor performance

Remember to always test migrations in a development environment before applying them to production, and maintain proper backups throughout the process.
