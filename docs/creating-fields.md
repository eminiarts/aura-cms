# Creating Fields

Fields are the building blocks of resources in Aura CMS. While Aura provides a comprehensive set of built-in fields, you can create custom fields to meet specific requirements. This guide will walk you through the process of creating custom fields.

## Table of Contents

- [Basic Field Creation](#basic-field-creation)
- [Field Structure](#field-structure)
- [Field Properties](#field-properties)
- [Field Methods](#field-methods)
- [Field Views](#field-views)
- [Creating a Field as a Package](#creating-a-field-as-a-package)
- [Examples](#examples)

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

1. **Field Class**: `app/Aura/Fields/Rating.php`
2. **Edit View**: `resources/views/components/fields/rating.blade.php`
3. **Display View**: `resources/views/components/fields/rating-view.blade.php`

## Field Structure

A custom field class extends the base `Field` class and defines its behavior:

```php
<?php

namespace App\Aura\Fields;

use Aura\Base\Fields\Field;

class Rating extends Field
{
    // View templates (required)
    public $edit = 'fields.rating';       // Form view
    public $view = 'fields.rating-view';  // Display view

    // Optional configurations
    public $optionGroup = 'Custom Fields';  // Group in field selector
    public $tableColumnType = 'integer';    // Database column type
    public bool $group = false;             // Can contain child fields
    public string $type = 'input';          // Field type category

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

    // Transform the value before saving to database
    public function set($post, $field, $value)
    {
        return (float) $value;
    }

    // Transform the value when retrieving from database
    public function get($class, $value, $field = null)
    {
        return (float) $value;
    }

    // Format value for display (view pages, tables)
    public function display($field, $value, $model)
    {
        return sprintf('%.1f / %d', $value, $field['max_rating'] ?? 5);
    }
}
```

## Field Properties

All properties available on the base `Field` class:

| Property | Type | Default | Description |
|----------|------|---------|-------------|
| `$edit` | `string` | `null` | Blade view for the edit/create form (required) |
| `$view` | `string` | `null` | Blade view for display mode (required) |
| `$index` | `string` | `null` | Blade view for table/index display |
| `$optionGroup` | `string` | `'Fields'` | Group name in the field type selector |
| `$tableColumnType` | `string` | `'string'` | Database column type for custom tables |
| `$tableNullable` | `bool` | `true` | Whether database column is nullable |
| `$type` | `string` | `'input'` | Field category: `input`, `relation`, or `structure` |
| `$group` | `bool` | `false` | Whether field can contain child fields |
| `$on_forms` | `bool` | `true` | Whether field appears on create/edit forms |
| `$taxonomy` | `bool` | `false` | Whether field is a taxonomy field |
| `$sameLevelGrouping` | `bool` | `false` | Enable same-level field grouping |
| `$wrap` | `bool` | `false` | Enable view wrapping |
| `$wrapper` | `string` | `null` | Custom wrapper view |

### Field Types

The `$type` property categorizes field behavior:

- **`input`**: Standard data fields (Text, Number, Boolean, etc.)
- **`relation`**: Relationship fields (BelongsTo, HasMany, Tags, etc.)
- **`structure`**: Layout fields that don't store data (Panel, Tab, etc.)

### Database Column Types

Common values for `$tableColumnType`:

- `string` - VARCHAR (default)
- `text` - TEXT for longer content
- `integer` - INT
- `boolean` - BOOLEAN
- `json` - JSON column
- `datetime` - DATETIME
- `date` - DATE

## Field Methods

### Core Methods

#### `getFields()`

Define configuration options for your field in the Resource Editor:

```php
public function getFields()
{
    return array_merge(parent::getFields(), [
        [
            'name' => 'Max Rating',
            'type' => 'Aura\\Base\\Fields\\Number',
            'slug' => 'max_rating',
            'validation' => 'numeric|min:1|max:10',
            'default' => 5,
            'instructions' => 'Maximum rating value (1-10)',
        ],
    ]);
}
```

#### `set($post, $field, $value)`

Transform the value before saving to the database:

```php
public function set($post, $field, $value)
{
    // $post - The model instance being saved
    // $field - The field configuration array
    // $value - The raw value from the form
    return (float) $value;
}
```

#### `get($class, $value, $field = null)`

Transform the value when retrieving from the database:

```php
public function get($class, $value, $field = null)
{
    // $class - The model class
    // $value - The raw value from database
    // $field - The field configuration array
    return (float) $value;
}
```

#### `display($field, $value, $model)`

Format the value for display in views and tables:

```php
public function display($field, $value, $model)
{
    // $field - The field configuration array
    // $value - The transformed value
    // $model - The model instance
    
    // You can also use a custom view
    if (optional($field)['display_view']) {
        return view($field['display_view'], [
            'row' => $model,
            'field' => $field,
            'value' => $value
        ])->render();
    }
    
    return sprintf('%.1f', $value);
}
```

### Filter Methods

#### `filterOptions()`

Define available filter operators for table filtering:

```php
public function filterOptions()
{
    return [
        'is' => __('is'),
        'is_not' => __('is not'),
        'greater_than' => __('greater than'),
        'less_than' => __('less than'),
        'is_empty' => __('is empty'),
        'is_not_empty' => __('is not empty'),
    ];
}
```

#### `getFilterValues($model, $field)`

Provide predefined values for filter dropdowns:

```php
public function getFilterValues($model, $field)
{
    return [
        1 => '1 Star',
        2 => '2 Stars',
        3 => '3 Stars',
        4 => '4 Stars',
        5 => '5 Stars',
    ];
}
```

### Helper Methods

#### `isDisabled($model, $field)`

Control when the field is disabled (supports closures):

```php
public function isDisabled($model, $field)
{
    // Field config can have a closure
    if (optional($field)['disabled'] instanceof \Closure) {
        return $field['disabled']($model);
    }
    
    return $field['disabled'] ?? false;
}
```

#### `isInputField()`

Check if the field stores data:

```php
public function isInputField()
{
    return in_array($this->type, ['input', 'repeater', 'group']);
}
```

#### `isRelation()`

Check if the field is a relationship:

```php
public function isRelation()
{
    return in_array($this->type, ['relation']);
}
```

### Relationship Methods

For relationship fields, override these methods:

#### `queryFor($query, $component)`

Modify the query for relationship data:

```php
public function queryFor($query, $component)
{
    return $query->where('status', 'active');
}
```

#### `options($model, $field)`

Provide dynamic options (for Select-type fields):

```php
public function options($model, $field)
{
    // Check for model-specific options method
    $methodName = 'get' . ucfirst($field['slug']) . 'Options';
    if (method_exists($model, $methodName)) {
        return $model->{$methodName}();
    }
    
    return $field['options'] ?? [];
}
```

## Field Views

Fields require Blade views for rendering in different contexts.

### View Paths

For application fields (created with `aura:field`):
- Edit view: `resources/views/components/fields/{slug}.blade.php`
- Display view: `resources/views/components/fields/{slug}-view.blade.php`

For package fields:
- Use namespaced paths: `'vendor-name::fields.my-field'`

### Available Variables in Views

| Variable | Description |
|----------|-------------|
| `$field` | Field configuration array (name, slug, validation, custom options, etc.) |
| `$form` | The model instance (on edit) or form data |
| `$this->model` | The Livewire component's model instance |

### Edit View

The edit view (`rating.blade.php`) defines how the field appears in forms:

```blade
<x-aura::fields.wrapper :field="$field">
    <div
        x-data="{
            rating: $wire.entangle('form.fields.{{ $field['slug'] }}'),
            maxRating: {{ $field['max_rating'] ?? 5 }},
            hoverRating: 0
        }"
        class="flex gap-1"
    >
        <template x-for="star in maxRating" :key="star">
            <button
                type="button"
                @click="rating = star"
                @mouseenter="hoverRating = star"
                @mouseleave="hoverRating = 0"
                :class="(hoverRating || rating) >= star ? 'text-yellow-400' : 'text-gray-300'"
                class="text-2xl focus:outline-none"
                :disabled="{{ $field['field']->isDisabled($form, $field) ? 'true' : 'false' }}"
            >
                ★
            </button>
        </template>
    </div>
    
    {{-- Alternative: Use Aura's input component --}}
    {{-- 
    <x-aura::input.text
        :disabled="$field['field']->isDisabled($form, $field)"
        wire:model="form.fields.{{ $field['slug'] }}"
        error="form.fields.{{ $field['slug'] }}"
        type="number"
        min="0"
        max="{{ $field['max_rating'] ?? 5 }}"
        step="{{ $field['step_size'] ?? 1 }}"
    />
    --}}
</x-aura::fields.wrapper>
```

### Display View

The display view (`rating-view.blade.php`) defines how the field appears when viewing a resource:

```blade
<x-aura::fields.wrapper :field="$field">
    @php
        $value = $this->model->display($field['slug']);
        $maxRating = $field['max_rating'] ?? 5;
    @endphp
    
    @if(empty($value))
        <span class="text-gray-400">–</span>
    @else
        <div class="flex gap-1">
            @for($i = 1; $i <= $maxRating; $i++)
                <span class="{{ $i <= $value ? 'text-yellow-400' : 'text-gray-300' }}">★</span>
            @endfor
        </div>
    @endif
</x-aura::fields.wrapper>
```

### Index/Table View

For custom table column rendering, set the `$index` property and create a view:

```php
public $index = 'fields.rating-index';
```

```blade
{{-- resources/views/components/fields/rating-index.blade.php --}}
<div class="flex gap-0.5">
    @for($i = 1; $i <= ($field['max_rating'] ?? 5); $i++)
        <span class="text-sm {{ $i <= $value ? 'text-yellow-400' : 'text-gray-200' }}">★</span>
    @endfor
</div>
```

## Creating a Field as a Package

For reusable fields across projects, create a Laravel package:

```bash
php artisan aura:field-plugin MyField
```

This generates a package structure in `packages/`:

### Package Structure

```
packages/my-field/
├── src/
│   ├── MyField.php
│   └── MyFieldServiceProvider.php
├── resources/
│   └── views/
│       └── components/
│           └── fields/
│               ├── my-field.blade.php
│               └── my-field-view.blade.php
├── composer.json
└── README.md
```

### Field Class (Package)

```php
<?php

namespace YourVendor\MyField;

use Aura\Base\Fields\Field;

class MyField extends Field
{
    // Use namespaced view paths
    public $edit = 'my-field::fields.my-field';
    public $view = 'my-field::fields.my-field-view';
    
    public $optionGroup = 'Custom Fields';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            // Your field configuration options
        ]);
    }
}
```

### Service Provider

```php
<?php

namespace YourVendor\MyField;

use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MyFieldServiceProvider extends PackageServiceProvider
{
    public function configurePackage(Package $package): void
    {
        $package
            ->name('my-field')
            ->hasViews('my-field');
    }
}
```

### Register the Package

Add to your `composer.json`:

```json
{
    "require": {
        "your-vendor/my-field": "*"
    },
    "repositories": [
        {
            "type": "path",
            "url": "./packages/my-field"
        }
    ]
}
```

Then register the service provider in `config/app.php` or use Laravel's auto-discovery.

## Examples

### Simple Text Field with Prefix/Suffix

```php
<?php

namespace App\Aura\Fields;

use Aura\Base\Fields\Field;

class PrefixedText extends Field
{
    public $edit = 'fields.prefixed-text';
    public $view = 'fields.prefixed-text-view';
    public $optionGroup = 'Input Fields';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Prefix',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'prefix',
                'style' => ['width' => '50'],
            ],
            [
                'name' => 'Suffix',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'suffix',
                'style' => ['width' => '50'],
            ],
        ]);
    }

    public function display($field, $value, $model)
    {
        if (empty($value)) {
            return '';
        }
        $prefix = $field['prefix'] ?? '';
        $suffix = $field['suffix'] ?? '';
        return $prefix . $value . $suffix;
    }
}
```

### Phone Number Field with Formatting

```php
<?php

namespace App\Aura\Fields;

use Aura\Base\Fields\Field;

class PhoneNumber extends Field
{
    public $edit = 'fields.phone-number';
    public $view = 'fields.phone-number-view';
    public $tableColumnType = 'string';
    public $optionGroup = 'Input Fields';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Phone',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'phone-settings',
            ],
            [
                'name' => 'Country Code',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'country_code',
                'validation' => 'required|regex:/^\+\d{1,3}$/',
                'default' => '+1',
                'instructions' => 'Default country code (e.g., +1, +44)',
            ],
            [
                'name' => 'Format',
                'type' => 'Aura\\Base\\Fields\\Select',
                'slug' => 'format',
                'options' => [
                    'us' => 'US: +1 (234) 567-8900',
                    'international' => 'International: +1 234 567 8900',
                    'compact' => 'Compact: +12345678900',
                ],
                'default' => 'us',
            ],
        ]);
    }

    public function set($post, $field, $value)
    {
        // Store only digits and + sign
        return preg_replace('/[^\d+]/', '', $value);
    }

    public function display($field, $value, $model)
    {
        if (empty($value)) {
            return '';
        }
        
        $format = $field['format'] ?? 'us';
        $digits = preg_replace('/[^\d]/', '', $value);
        
        return match($format) {
            'us' => preg_replace('/(\d{1,3})(\d{3})(\d{3})(\d{4})/', '+$1 ($2) $3-$4', $digits),
            'international' => preg_replace('/(\d{1,3})(\d{3})(\d{3})(\d{4})/', '+$1 $2 $3 $4', $digits),
            default => $value,
        };
    }

    public function filterOptions()
    {
        return [
            'contains' => __('contains'),
            'starts_with' => __('starts with'),
            'is_empty' => __('is empty'),
            'is_not_empty' => __('is not empty'),
        ];
    }
}
```

### Select Field with Dynamic Options

```php
<?php

namespace App\Aura\Fields;

use Aura\Base\Fields\Field;

class CountrySelect extends Field
{
    public $edit = 'fields.country-select';
    public $view = 'aura::fields.view-value';  // Reuse Aura's default view
    public $optionGroup = 'Choice Fields';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Country',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'country-settings',
            ],
            [
                'name' => 'Show Flag',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'slug' => 'show_flag',
                'default' => true,
            ],
            [
                'name' => 'Region Filter',
                'type' => 'Aura\\Base\\Fields\\Select',
                'slug' => 'region',
                'options' => [
                    '' => 'All Regions',
                    'europe' => 'Europe',
                    'americas' => 'Americas',
                    'asia' => 'Asia Pacific',
                ],
            ],
        ]);
    }

    public function options($model, $field)
    {
        // Check for model-specific method first
        $methodName = 'get' . ucfirst($field['slug']) . 'Options';
        if (method_exists($model, $methodName)) {
            return $model->{$methodName}();
        }

        // Default countries list
        return [
            'US' => 'United States',
            'GB' => 'United Kingdom',
            'CA' => 'Canada',
            'DE' => 'Germany',
            'FR' => 'France',
            // Add more as needed
        ];
    }

    public function getFilterValues($model, $field)
    {
        return $this->options($model, $field);
    }

    public function filterOptions()
    {
        return [
            'is' => __('is'),
            'is_not' => __('is not'),
            'is_empty' => __('is empty'),
            'is_not_empty' => __('is not empty'),
        ];
    }

    public function display($field, $value, $model)
    {
        $options = $this->options($model, $field);
        $name = $options[$value] ?? $value;
        
        if ($field['show_flag'] ?? true) {
            // Convert country code to flag emoji
            $flag = $this->getFlag($value);
            return "{$flag} {$name}";
        }
        
        return $name;
    }

    private function getFlag(string $countryCode): string
    {
        $code = strtoupper($countryCode);
        return mb_chr(0x1F1E6 + ord($code[0]) - ord('A'))
             . mb_chr(0x1F1E6 + ord($code[1]) - ord('A'));
    }
}
```

### JSON/Array Field

```php
<?php

namespace App\Aura\Fields;

use Aura\Base\Fields\Field;

class Metadata extends Field
{
    public $edit = 'fields.metadata';
    public $view = 'fields.metadata-view';
    public $tableColumnType = 'json';
    public $optionGroup = 'Advanced Fields';

    public function get($class, $value, $field = null)
    {
        if (is_string($value)) {
            return json_decode($value, true) ?? [];
        }
        return $value ?? [];
    }

    public function set($post, $field, $value)
    {
        if (is_array($value)) {
            return json_encode($value);
        }
        return $value;
    }

    public function display($field, $value, $model)
    {
        if (empty($value)) {
            return '<span class="text-gray-400">No metadata</span>';
        }
        
        $items = is_string($value) ? json_decode($value, true) : $value;
        
        return collect($items)
            ->map(fn($v, $k) => "<strong>{$k}:</strong> {$v}")
            ->implode('<br>');
    }
}
```

## Best Practices

1. **Always extend parent::getFields()**: This preserves core field settings like name, slug, and validation.

2. **Use Aura's input components**: Leverage `<x-aura::input.text>`, `<x-aura::input.select>`, etc. for consistent styling.

3. **Wrap views properly**: Always use `<x-aura::fields.wrapper>` to ensure labels, instructions, and errors display correctly.

4. **Handle empty values**: Check for null/empty values in `display()` and return appropriate fallbacks.

5. **Validate transformations**: Ensure `set()` and `get()` handle all possible input types gracefully.

6. **Group related options**: Use Tabs in `getFields()` to organize complex field configurations.

7. **Document field options**: Add `instructions` to each configuration field to help users understand the options.
