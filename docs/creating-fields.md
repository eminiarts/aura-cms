# Creating Fields

Fields are the building blocks of resources in Aura CMS. While Aura provides a comprehensive set of built-in fields, you can create custom fields to meet specific requirements. This guide will walk you through the process of creating custom fields.

*Video 1: Creating Your First Custom Field*

![Creating Your First Custom Field](placeholder-video.mp4)

## Table of Contents

- [Basic Field Creation](#basic-field-creation)
- [Field Structure](#field-structure)
- [Field Views](#field-views)
- [Customizing Fields](#customizing-fields)

## Basic Field Creation

To create a new field, use the Aura CLI command:

```bash
php artisan aura:field {name}
```

For example, to create a custom rating field:

```bash
php artisan aura:field Rating
```

This command generates three files:

1. Field Class: `app/Aura/Fields/Rating.php`
2. Edit View: `resources/views/components/fields/rating.blade.php`
3. Display View: `resources/views/components/fields/rating-view.blade.php`

## Field Structure

A custom field class extends the base `Field` class and defines its behavior:

```php
<?php

namespace App\Aura\Fields;

use Aura\Base\Fields\Field;

class Rating extends Field
{
    // View templates
    public $edit = 'fields.rating';
    public $view = 'fields.rating-view';

    // Optional configurations
    public $tableColumnType = 'integer';
    public $optionGroup = 'Custom Fields';
    public bool $group = false;
    public string $type = 'input';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Max Rating',
                'type' => 'Aura\\Base\\Fields\\Number',
                'slug' => 'max_rating',
                'validation' => 'numeric|min:1|max:10',
                'default' => 5,
            ],
            [
                'name' => 'Step Size',
                'type' => 'Aura\\Base\\Fields\\Number',
                'slug' => 'step_size',
                'validation' => 'numeric|min:0.1|max:1',
                'default' => 1,
            ],
        ]);
    }

    // Optional: Transform the value before saving
    public function set($post, $field, $value)
    {
        return (float) $value;
    }

    // Optional: Transform the value when retrieving
    public function get($class, $value, $field = null)
    {
        return (float) $value;
    }

    // Optional: Custom display formatting
    public function display($field, $value, $model)
    {
        return sprintf('%.1f', $value);
    }
}
```

## Field Views

Fields require two Blade views for rendering in different contexts:

### Edit View

The edit view (`rating.blade.php`) defines how the field appears in forms:

```blade
<x-aura::fields.wrapper :field="$field">
    <div>
        <x-aura::input.text
            :disabled="optional($field)['disabled']"
            wire:model="form.fields.{{ optional($field)['slug'] }}"
            error="form.fields.{{ optional($field)['slug'] }}"
            placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
            id="resource-field-{{ optional($field)['slug'] }}"
            type="number"
            step="{{ optional($field)['step_size'] ?? 1 }}"
            min="0"
            max="{{ optional($field)['max_rating'] ?? 5 }}"
        />
    </div>
</x-aura::fields.wrapper>
```

### Display View

The display view (`rating-view.blade.php`) defines how the field appears when viewing a resource:

```blade
<x-aura::fields.wrapper :field="$field">
    {!! $this->model->display($field['slug']) !!}
</x-aura::fields.wrapper>
```

## Customizing Fields

### Field Properties

| Property | Description | Default |
|----------|-------------|---------|
| `$edit` | Blade view for edit form | Required |
| `$view` | Blade view for display | Required |
| `$tableColumnType` | Database column type | 'string' |
| `$optionGroup` | Group in field selector | null |
| `$group` | Can contain other fields | false |
| `$type` | Field type (input, relation, structure) | 'input' |

### Field Configuration Options

Define configuration options in the `getFields()` method:

```php
public function getFields()
{
    return array_merge(parent::getFields(), [
        [
            'name' => 'Option Name',
            'type' => 'Aura\\Base\\Fields\\Text',
            'slug' => 'option_slug',
            'validation' => 'required',
            'instructions' => 'Help text for the option',
            'default' => 'Default value',
        ],
    ]);
}
```

### Value Transformation

Customize how values are handled:

```php
// Transform input before saving
public function set($post, $field, $value)
{
    return transform($value);
}

// Transform value when retrieving
public function get($class, $value, $field = null)
{
    return transform($value);
}

// Format value for display
public function display($field, $value, $model)
{
    return format($value);
}
```

## Examples

### Simple Text Field with Prefix/Suffix

```php
class PrefixedText extends Field
{
    public $edit = 'fields.prefixed-text';
    public $view = 'fields.prefixed-text-view';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Prefix',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'prefix',
            ],
            [
                'name' => 'Suffix',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'suffix',
            ],
        ]);
    }

    public function display($field, $value, $model)
    {
        $prefix = $field['prefix'] ?? '';
        $suffix = $field['suffix'] ?? '';
        return $prefix . $value . $suffix;
    }
}
```

### Complex Field with Validation

```php
class PhoneNumber extends Field
{
    public $edit = 'fields.phone-number';
    public $view = 'fields.phone-number-view';
    public $tableColumnType = 'string';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Country Code',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'country_code',
                'validation' => 'required|regex:/^\+\d{1,3}$/',
                'default' => '+1',
            ],
        ]);
    }

    public function set($post, $field, $value)
    {
        // Remove non-numeric characters except +
        return preg_replace('/[^\d+]/', '', $value);
    }

    public function display($field, $value, $model)
    {
        // Format phone number: +1 (234) 567-8900
        $number = preg_replace('/[^\d]/', '', $value);
        return preg_replace('/(\+\d)(\d{3})(\d{3})(\d{4})/', '$1 ($2) $3-$4', $number);
    }
}
```
