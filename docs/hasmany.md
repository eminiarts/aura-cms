# Using HasMany Field in Aura CMS Resources

The HasMany field type in Aura CMS allows you to define one-to-many relationships between resources. This guide will walk you through how to use the HasMany field in your resource definitions.

## Basic Usage

To add a HasMany relationship to your resource, use the following structure in your `getFields()` method:

```php
public static function getFields()
{
    return [
        [
            'name' => 'Items',
            'type' => 'Aura\\Base\\Fields\\HasMany',
            'resource' => RelatedModel::class,
            'slug' => 'items',
        ],
    ];
}
```

- `name`: The display name for the relationship.
- `type`: Always set to `'Aura\\Base\\Fields\\HasMany'` for HasMany relationships.
- `resource`: The class name of the related model.
- `slug`: A unique identifier for this relationship.

## Advanced Configuration

### Custom Foreign Key

If your relationship uses a non-standard foreign key, you can specify it using the `column` option:

```php
[
    'name' => 'Items',
    'type' => 'Aura\\Base\\Fields\\HasMany',
    'resource' => RelatedModel::class,
    'slug' => 'items',
    'column' => 'custom_parent_id',
]
```

### Using Meta Fields for Relationships

If your relationship is stored in meta fields, you can use the `relation` option:

```php
[
    'name' => 'Items',
    'type' => 'Aura\\Base\\Fields\\HasMany',
    'resource' => RelatedModel::class,
    'slug' => 'items',
    'relation' => 'meta_key_for_relationship',
]
```

### Custom Relationship Logic

For complex relationships, you can use a closure in the `relation` option:

```php
[
    'name' => 'Items',
    'type' => 'Aura\\Base\\Fields\\HasMany',
    'resource' => RelatedModel::class,
    'slug' => 'items',
    'relation' => function ($query, $model) {
        // Custom query logic here
        return $query->where('custom_condition', $model->some_attribute);
    },
]
```

## Querying Related Items

Once you've defined a HasMany relationship, you can access the related items using the relationship method:

```php
$relatedItems = $model->items;
```

Or if you need to add additional query constraints:

```php
$filteredItems = $model->items()->where('status', 'active')->get();
```

## Displaying in Forms

The HasMany field uses the `aura::fields.has-many` template for editing and `aura::fields.has-many-view` template for viewing. Make sure these templates are properly defined in your views.

## Notes

- The HasMany field automatically handles polymorphic relationships using the `post_relations` table if a `column` is not specified.
- For User and Team resources, the relationship behaves differently and doesn't add additional where clauses.
- Special handling is included for Flow, Operation, and FlowLog resources.

Remember to adjust your database schema and migrations to support the relationships you define using HasMany fields.