# Meta Fields in Aura CMS

Meta fields in Aura CMS provide a flexible way to store additional data for your resources without modifying the database schema. This feature allows you to dynamically add custom fields to your resources while maintaining data integrity and performance.

## Table of Contents

- [Overview](#overview)
- [Configuration](#configuration)
- [Usage](#usage)
- [Meta Field Storage](#meta-field-storage)
- [Custom Tables vs Meta Fields](#custom-tables-vs-meta-fields)
- [Migration and Data Transfer](#migration-and-data-transfer)

## Overview

Meta fields allow you to:
- Store additional data for resources without altering table structures
- Dynamically add custom fields to resources
- Maintain flexibility in your data model
- Handle complex data types and relationships

## Configuration

### Meta Field Settings

Meta fields can be configured in your resource classes:

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

### Enabling/Disabling Meta Fields

You can control whether a resource uses meta fields or custom tables in your configuration:

```php
// config/aura.php
return [
    'features' => [
        'custom_tables_for_resources' => false, // Set to true to use custom tables instead of meta
    ],
];
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

Meta fields can be accessed through your resource models:

```php
// Get meta field value
$value = $resource->meta_field;

// Set meta field value
$resource->meta_field = 'new value';
$resource->save();
```

## Meta Field Storage

Meta fields are stored in the `meta` table with the following structure:

- `id`: Primary key
- `metable_type`: The model class using the meta field
- `metable_id`: The ID of the model instance
- `key`: The field slug
- `value`: The stored value

### Database Schema

```php
Schema::create('meta', function (Blueprint $table) {
    $table->id();
    $table->morphs('metable');
    $table->string('key')->nullable()->index();
    $table->longText('value')->nullable();
    $table->index(['metable_type', 'metable_id', 'key']);
});
```

## Custom Tables vs Meta Fields

### Meta Fields Approach
- **Pros**:
  - Flexible schema
  - Easy to add new fields
  - No migrations needed
  - Works well with dynamic data
- **Cons**:
  - Slower queries for large datasets
  - More complex relationships
  - Limited indexing options

### Custom Tables Approach
- **Pros**:
  - Better query performance
  - Direct database relationships
  - Full indexing capabilities
- **Cons**:
  - Requires migrations
  - Less flexible schema
  - More maintenance overhead

## Migration and Data Transfer

### Converting from Meta to Custom Tables

Use the provided Artisan commands to migrate data:

```bash
# Migrate a resource from posts/meta to custom table
php artisan aura:migrate-from-posts-to-custom-table {resource}

# Transfer data from posts/meta to custom table
php artisan aura:transfer-from-posts-to-custom-table {resource}
```

### Migration Process

1. **Generate Migration**:
```bash
php artisan aura:create-resource-migration {resource}
```

2. **Update Resource Configuration**:
```php
class YourResource extends Resource
{
    public static $customTable = true;
    protected $table = 'your_custom_table';
}
```

3. **Transfer Data**:
```bash
php artisan aura:transfer-from-posts-to-custom-table YourResource
```
