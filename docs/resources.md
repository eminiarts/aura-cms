# Resources

Resources are the cornerstone of Aura CMS. They represent the primary data structures within the system, similar to Laravel's Eloquent models but enhanced with additional functionality tailored for content management. Understanding how to create and manage resources is essential for building robust applications with Aura CMS.

---

## Table of Contents

- [Introduction to Resources](#introduction-to-resources)
- [Defining a Resource](#defining-a-resource)
  - [Resource Structure](#resource-structure)
  - [Key Properties](#key-properties)
  - [Example Resource: Post](#example-resource-post)
- [Fields in Resources](#fields-in-resources)
  - [Defining Fields](#defining-fields)
  - [Field Attributes](#field-attributes)
- [Traits Used in Resources](#traits-used-in-resources)
- [Resource Methods](#resource-methods)
  - [Dynamic Method Handling](#dynamic-method-handling)
  - [Attribute Accessors](#attribute-accessors)
- [Customizing Resources](#customizing-resources)
  - [Custom Tables](#custom-tables)
  - [Using Meta Fields](#using-meta-fields)
- [Resource Relationships](#resource-relationships)
- [Global Scopes and Querying](#global-scopes-and-querying)

---

<a name="introduction-to-resources"></a>
## Introduction to Resources

In Aura CMS, a **Resource** is an enhanced version of a Laravel Eloquent model that includes additional features for content management. Resources allow you to define custom data types with specific fields, relationships, and behaviors. They are analogous to **Custom Post Types** in WordPress or standard models in Laravel but come with built-in support for:

- Dynamic field definitions
- Meta fields storage
- Conditional logic
- Customizable CRUD operations
- Integration with the Aura CMS admin interface

*Figure 1: Resource Structure in Aura CMS*

![Figure 1: Resource Structure](placeholder-image.png)

---

<a name="defining-a-resource"></a>
## Defining a Resource

Creating a resource involves defining a class that extends the base `Aura\Base\Resource` class. This base class provides the foundational functionality required for the resource to interact seamlessly with Aura CMS.

<a name="resource-structure"></a>
### Resource Structure

At its core, a resource class might look like this:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Project extends Resource
{
    // Define properties and methods here
}
```

<a name="key-properties"></a>
### Key Properties

Resources come with several static properties that you can set to customize their behavior:

- **$type**: The resource type name.
- **$slug**: The URL-friendly identifier for the resource.
- **$icon**: An SVG icon for the resource, displayed in the admin navigation.
- **$group**: The group name for organizing resources in the navigation menu.
- **$sort**: Determines the order in which resources appear in the navigation.
- **$showInNavigation**: Whether the resource should appear in the navigation menu.
- **$searchable**: An array of fields that are searchable via the global search.

<a name="example-resource-post"></a>
### Example Resource: Post

Below is an example of the `Post` resource provided by Aura CMS:

```php
<?php

namespace Aura\Base\Resources;

use Aura\Base\Resource;

class Post extends Resource
{
    public static $type = 'Post';
    public static $slug = 'post';
    public static $group = 'Content';
    public static $searchable = ['title', 'content'];

    // Define fields, actions, and methods here
}
```

*Figure 2: Example of a Resource Definition*

![Figure 2: Resource Definition](placeholder-image.png)

---

<a name="fields-in-resources"></a>
## Fields in Resources

Fields define the data attributes associated with a resource. They determine what inputs are available in the admin interface and how data is stored and retrieved.

<a name="defining-fields"></a>
### Defining Fields
Fields are defined in the `getFields` static method of the resource class:

```php
public static function getFields()
{
    return [
        [
            'name' => 'Title',
            'slug' => 'title',
            'type' => 'Aura\\Base\\Fields\\Text',
            'validation' => 'required|string|max:255',
            'on_index' => true,
            'on_forms' => true,
            'on_view' => true,
        ],
        // Additional fields...
    ];
}
```

<a name="field-attributes"></a>
### Field Attributes

Each field can have several attributes:

- **name**: The display name of the field.
- **slug**: The unique identifier for the field.
- **type**: The class representing the field type (e.g., Text, Number, Date).
- **validation**: Laravel validation rules applied to the field.
- **conditional_logic**: Conditions under which the field is displayed.
- **on_index**: Whether the field appears on the index (list) page.
- **on_forms**: Whether the field is included in forms (create/edit).
- **on_view**: Whether the field is shown on the detail view page.
- **style**: Styling options, such as field width.

**Example: Defining a Number Field**

```php
[
    'name' => 'Quantity',
    'slug' => 'quantity',
    'type' => 'Aura\\Base\\Fields\\Number',
    'validation' => 'required|integer|min:0',
    'on_index' => true,
    'on_forms' => true,
    'on_view' => true,
    'style' => [
        'width' => '50',
    ],
],
```

*Figure 3: Defining Fields in a Resource*

![Figure 3: Defining Fields](placeholder-image.png)

---

<a name="traits-used-in-resources"></a>
## Traits Used in Resources

Aura CMS resources utilize several traits to extend functionality:

- **AuraModelConfig**: Provides configuration options like actions, bulk actions, and navigation settings.
- **InitialPostFields**: Manages initial field definitions.
- **InputFields**: Handles input field processing and validation.
- **InteractsWithTable**: Manages interactions with the database table.
- **SaveFieldAttributes**: Handles saving of field attributes.
- **SaveMetaFields**: Manages saving of meta fields.

**Example: Using Traits in a Resource**

```php
<?php

namespace Aura\Base;

use Illuminate\Database\Eloquent\Model;
use Aura\Base\Traits\AuraModelConfig;
use Aura\Base\Traits\InputFields;

class Resource extends Model
{
    use AuraModelConfig;
    use InputFields;
    // Additional traits...
}
```

*Figure 4: Traits in the Resource Base Class*

![Figure 4: Traits in Resource](placeholder-image.png)

---

<a name="resource-methods"></a>
## Resource Methods

Resources inherit methods from the base `Resource` class and can define their own. Key methods include:

- **getFields()**: Returns the array of field definitions.
- **getActions()**: Defines actions available for the resource (e.g., edit, delete).
- **getBulkActions()**: Defines bulk actions for the resource.
- **navigation()**: Returns navigation configuration for the resource.
- **scopeWhereMeta()**: Allows querying based on meta fields.
- **__get()**: Magic method to access attributes and handle dynamic fields.

<a name="dynamic-method-handling"></a>
### Dynamic Method Handling

The `__call()` magic method allows resources to handle dynamic method calls, especially for fields that represent relationships.

**Example: Handling Dynamic Methods**

```php
public function __call($method, $parameters)
{
    if ($this->getFieldSlugs()->contains($method)) {
        $fieldClass = $this->fieldClassBySlug($method);

        if ($fieldClass->isRelation()) {
            $field = $this->fieldBySlug($method);
            return $fieldClass->relationship($this, $field);
        }
    }

    return parent::__call($method, $parameters);
}
```

<a name="attribute-accessors"></a>
### Attribute Accessors

The `__get()` magic method is overridden to provide custom access to resource attributes, including handling meta fields and relations.

**Example: Custom Attribute Access**

```php
public function __get($key)
{
    $value = parent::__get($key);

    if ($value) {
        return $value;
    }

    if ($this->getFieldSlugs()->contains($key)) {
        $fieldClass = $this->fieldClassBySlug($key);
        if ($fieldClass->isRelation()) {
            $field = $this->fieldBySlug($key);
            return $fieldClass->getRelation($this, $field);
        }
    }

    return $value;
}
```

---

<a name="customizing-resources"></a>
## Customizing Resources

<a name="custom-tables"></a>
### Custom Tables

By default, resources use the `posts` and `meta` tables for data storage. However, you can configure a resource to use a custom table by setting the `$customTable` property:

```php
public static $customTable = true;
```

When using a custom table, you may need to:

- Define the `$table` property to specify the table name.
- Run migrations to create the custom table.
- Update field definitions to match the custom table columns.

*Figure 5: Using a Custom Table in a Resource*

![Figure 5: Custom Tables](placeholder-image.png)

<a name="using-meta-fields"></a>
### Using Meta Fields

If your resource uses meta fields (key-value pairs stored in a polymorphic table), you can interact with them using the `meta()` relationship:

```php
public function meta()
{
    return $this->morphMany(Meta::class, 'metable');
}
```

**Example: Retrieving a Meta Value**

```php
$metaValue = $resource->getMeta('custom_field_key');
```

---

<a name="resource-relationships"></a>
## Resource Relationships

Resources can define relationships to other models or resources using standard Eloquent relationships.

**Example: Defining a BelongsTo Relationship**

```php
public function user()
{
    return $this->belongsTo(config('aura.resources.user'));
}
```

**Example: Defining a HasMany Relationship**

```php
public function comments()
{
    return $this->hasMany(Comment::class);
}
```

*Figure 6: Resource Relationships*

![Figure 6: Defining Relationships](placeholder-image.png)

---

<a name="global-scopes-and-querying"></a>
## Global Scopes and Querying

Resources can utilize global scopes to automatically apply query constraints. The base `Resource` class applies scopes such as `TypeScope` and `TeamScope`.

**Example: Booting Global Scopes**

```php
protected static function booted()
{
    if (!static::$customTable) {
        static::addGlobalScope(new TypeScope);
    }

    static::addGlobalScope(app(TeamScope::class));
    static::addGlobalScope(new ScopedScope);
}
```

**Custom Query Scopes**

You can define query scopes to filter resources based on meta fields:

```php
public function scopeWhereMeta($query, $key, $operator, $value)
{
    return $query->whereHas('meta', function ($query) use ($key, $operator, $value) {
        $query->where('key', $key)->where('value', $operator, $value);
    });
}
```

---

*Video 1: Deep Dive into Resources*

![Video 1: Deep Dive into Resources](placeholder-video.mp4)

---

By understanding and effectively utilizing resources in Aura CMS, you can build powerful and flexible applications tailored to your specific needs. Resources provide a structured yet extensible way to manage your data, making development more efficient and organized.
