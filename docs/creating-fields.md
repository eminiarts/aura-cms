# Creating Fields

Fields are the building blocks of resources in Aura CMS. While Aura provides a comprehensive set of built-in fields, you can create custom fields to meet specific requirements. This guide will walk you through the process of creating custom fields.

## Table of Contents

- [Basic Field Creation](#basic-field-creation)
- [Field Structure](#field-structure)
- [Field Properties](#field-properties)
- [Field Methods](#field-methods)
- [Field Value Lifecycle](#field-value-lifecycle)
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

## Field Value Lifecycle

Every class extending `Aura\Base\Fields\Field` supports one value contract across physical columns and Aura meta rows:

1. `normalizeForStorage()` converts submitted or imported input immediately before persistence.
2. `hydrateFromStorage()` converts a stored value to its application/form representation.
3. `presentValue()` presents an already hydrated value for a declared context.

The storage location and presentation context are explicit:

```php
use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Contracts\FieldValueStorage;
use Illuminate\Database\Eloquent\Model;

public function normalizeForStorage(
    mixed $value,
    array $field,
    ?Model $model,
    FieldValueStorage $storage,
): mixed {
    return $value;
}

public function hydrateFromStorage(
    mixed $value,
    array $field,
    ?Model $model,
    FieldValueStorage $storage,
    FieldValueContext $context = FieldValueContext::Model,
): mixed {
    return $value;
}

public function presentValue(
    mixed $value,
    array $field,
    ?Model $model,
    FieldValueContext $context = FieldValueContext::Index,
): mixed {
    return $value;
}
```

`FieldValueStorage` is either `Physical` or `Meta`. `FieldValueContext` is `Create`, `Edit`, `Model`, `Index`, or `View`. Use the context when a form needs a different shape from a table or detail view.

Resources keep the backward-compatible `display($slug)` index API. Use `$resource->displayInContext($slug, FieldValueContext::View)` when rendering another surface; existing Resource overrides of `display($slug)` continue to be honored.

The same compatibility rule applies to `displayFieldValue($key, $value)`, `getMeta($key)`, and `resolveFieldValue($slug, $meta = null)`. Their original signatures remain unchanged. Framework code that needs a form/view context uses the corresponding `*InContext()` method.

Existing custom fields remain compatible: the base class adapts `set()`, `get()`, and `display()` to the new methods. It also adapts the historically documented `displayValue($value, $model)` hook without declaring an incompatible parent signature. New fields should override `presentValue()` when presentation context matters. Preserve `null`, an empty string, `0`, and `false` as separate values unless the field explicitly defines another domain rule.

For a physical attribute declared in the model's Eloquent casts, the Eloquent cast remains the storage normalizer. Aura does not run the field normalizer a second time over the cast's raw storage value; this preserves existing JSON/array casts and prevents double encoding.

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

#### `ProvidesFilterCapability`

Table filters use a field-owned `FilterCapability` for both their input UI and
their query behavior. Fields that do not opt in receive the default text
capability. Choice, boolean, date, and relationship fields implement
`ProvidesFilterCapability` to declare a more specific capability. Discovery is
outside the `Field` inheritance method namespace, so existing package fields may
keep unrelated legacy methods without signature collisions. The table does not
inspect the field class name.

Table subclasses may continue overriding Aura's former protected query-filter
helpers during the compatibility window. Those helpers are deprecated adapters;
new field behavior belongs in `ProvidesFilterCapability` and
`AppliesFieldFilter`. The default adapters still resolve the server-owned field
capability and apply its query handler once.

```php
use Aura\Base\Contracts\ProvidesFilterCapability;
use Aura\Base\Fields\Filters\FilterCapability;
use Aura\Base\Resource;

class RatingField extends Field implements ProvidesFilterCapability
{
    public function provideAuraFilterCapability(Resource $model, array $field): FilterCapability
    {
        return FilterCapability::scalarOption(
            operators: $this->filterOptions(),
            values: $this->getFilterValues($model, $field),
        );
    }
}
```

The available factories are:

- `text($operators)` for scalar text or number input.
- `scalarOption($operators, $values)` for a fixed set stored in a scalar column.
- `option($operators, $values, ..., $multiple)` for typed values whose storage
  representation is owned by the supplied handler, including JSON lists.
- `boolean($operators)` for a typed yes/no value.
- `date($operators, $storageFormat)`, `datetime($operators, $storageFormat)`,
  and `dateRange($operators, $storageFormat)` for browser ISO input backed by a
  fixed-width stored format.
- `relationship(...)` for Aura's `post_relations` pivot.
- `custom(...)` for a package-owned Blade component and query handler.

The built-in `Number` field uses a custom capability handler with the standard
text input component. It validates the field's integer/decimal configuration
before applying exact comparisons, so the table does not need field-class
switches.

Option capabilities accept both associative `value => label` maps and
list-style rows such as `['key' => 'open', 'value' => 'Open']`. Aura converts
them to canonical `value`, `wire_value`, and `label` rows. Empty or malformed
options are omitted. Capability declarations accept arrays, traversable values, or null;
other option containers are rejected.
The original scalar value is restored before the query is applied, so an
integer option remains an integer even though HTML submits a string. Legacy
wire values remain unchanged when unambiguous. JSON-backed multiple-value
fields can use stable typed wire values for collisions such as `false`, `0`,
and `'0'`. `scalarOption()` rejects option sets whose values have the same
form/database string representation because a scalar query cannot distinguish
the stored identities. Built-in `Select`, `Status`, and `Radio` filters use this
opt-in capability. The check happens when building the filter capability; it
does not alter field write hooks or persistence. Use unique scalar keys or a
JSON-backed multiple-value field instead. `Select::getFilterValues()` remains
available, and `Status`, `Radio`, and `Checkbox` expose the same method through
this shared path.

Text capabilities accept only scalar values for scalar operators. Structured
values fail closed before the query handler runs. `in` and `not_in` accept a
flat scalar list or a comma-separated scalar string; associative, nested, or
object values fail closed.

Date and datetime controls submit timezone-free ISO values. Datetimes are local
wall times in `config('app.timezone')`; nonexistent DST-gap times and ambiguous
DST-fold times fail closed. Aura persists built-in meta-backed dates as `Y-m-d`
and datetimes as `Y-m-d H:i:s`, then constructs a portable chronological
expression for that fixed-width text. Custom temporal capabilities may declare
another fixed-width storage format composed of `Y`, `m`, `d`, `H`, `i`, and
optional `s`; an unsupported format fails closed.

Native custom-table `date` columns compare canonical `Y-m-d` values directly.
PostgreSQL `timestamp without time zone` and SQLite timestamp text compare the
canonical application-local wall time directly. MySQL/MariaDB `TIMESTAMP`
comparisons use the column's Unix timestamp and an application-timezone instant,
so changing the MySQL session timezone cannot change the result. Values outside
MySQL's portable `1970-01-01 00:00:01 UTC` through
`2038-01-19 03:14:07 UTC` range fail closed. Native empty operators use null
semantics only.

Filtering does not transform values during writes. Aura's field-value contract
normalizes built-in Date, Datetime, and Number values before physical or meta
persistence, while Eloquent casts and mutators still run once for physical
attributes. Existing columns and invalid legacy rows are never rewritten
implicitly; follow [Upgrading Aura CMS](../UPGRADING.md) for explicit data and
schema migrations.

JSON-backed multiple values can opt into exact, portable membership queries:

```php
use Aura\Base\Fields\Filters\JsonFieldFilter;

return FilterCapability::option(
    operators: $this->filterOptions(),
    values: $this->getFilterValues($model, $field),
    queryHandler: JsonFieldFilter::class,
    multiple: true,
);
```

This uses the database JSON operators rather than substring `LIKE` matching,
so value `1` does not match `10`.

For a completely custom filter, register the package's anonymous Blade
component in its service provider and declare a handler implementing
`AppliesFieldFilter`:

```php
use Aura\Base\Contracts\AppliesFieldFilter;
use Aura\Base\Contracts\ProvidesFilterCapability;
use Aura\Base\Fields\Filters\FilterCapability;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Blade;

Blade::anonymousComponentPath(__DIR__.'/../resources/views/components', 'acme');

class PriorityField extends Field implements ProvidesFilterCapability
{
    public function provideAuraFilterCapability(Resource $model, array $field): FilterCapability
    {
        return FilterCapability::custom(
            component: 'acme::priority-filter',
            operators: ['is' => __('is')],
            queryHandler: PriorityFilter::class,
            values: ['urgent' => __('Urgent'), 'routine' => __('Routine')],
        );
    }
}

final class PriorityFilter implements AppliesFieldFilter
{
    public function apply(
        Builder $query,
        Resource $resource,
        array $field,
        array $filter,
        FilterCapability $capability,
    ): void {
        $query->where(
            $query->getModel()->qualifyColumn('priority'),
            $filter['value'],
        );
    }
}
```

The component receives `model`, `field`, `capability`, and `size` props. Query
handlers receive only the server-resolved field and capability; client-provided
field classes, SQL columns, and handler names are never executed. Unsupported
operators and invalid fixed-option values fail closed. Handler classes are
validated when the capability is declared and must implement
`AppliesFieldFilter`. A null or blank value simply leaves an incomplete filter
inactive, except for explicit empty/not-empty operators.

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
php artisan aura:plugin your-vendor/my-field
```

Choose **Field plugin** when prompted. This generates a package structure in
`plugins/your-vendor/my-field/`:

### Package Structure

```
plugins/your-vendor/my-field/
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

use Aura\Base\Aura;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class MyFieldServiceProvider extends PackageServiceProvider
{
    public function registeringPackage(): void
    {
        Aura::registerFieldSource(
            key: 'your-vendor-my-field',
            namespace: 'YourVendor\\MyField',
            path: __DIR__,
        );
    }

    public function configurePackage(Package $package): void
    {
        $package
            ->name('my-field')
            ->hasViews('my-field');
    }
}
```

Register discovery sources through `Aura::registerFieldSource()` in
`registeringPackage()`. Laravel finishes registering every provider before Aura
scans the configured sources, so this works regardless of provider order and
for normal, custom-vendor-directory, and Composer path-repository installations.

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
            "url": "./plugins/your-vendor/my-field"
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
