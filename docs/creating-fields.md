# Creating fields

A field is a PHP class that extends `Aura\Base\Fields\Field`. It defines how a value is edited, stored, converted when it is read, and displayed. A resource refers to a field class by its fully qualified class name in the resource's static `getFields()` method.

## Generate a field

Run the field generator from a Laravel application that has Aura installed:

```bash
php artisan aura:field Rating
```

With the default `aura-settings.paths.fields` configuration, the command creates:

```text
app/Aura/Fields/Rating.php
resources/views/components/fields/rating.blade.php
resources/views/components/fields/rating-view.blade.php
```

The class name is taken from the command argument. Aura uses its slug for the two view names. The command does not overwrite an existing class or view file.

The generated class is intentionally small:

```php
<?php

namespace App\Aura\Fields;

use Aura\Base\Fields\Field;

class Rating extends Field
{
    public $edit = 'fields.rating';

    public $view = 'fields.rating-view';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            // Add settings for this field here.
        ]);
    }
}
```

The class namespace and path come from `config/aura-settings.php`:

```php
'paths' => [
    'fields' => [
        'namespace' => 'App\\Aura\\Fields',
        'path' => app_path('Aura/Fields'),
    ],
],
```

Aura registers field classes found under this configured path during service-provider boot. For a field outside that path, register the class explicitly if it should appear in the Resource Editor's Type list:

Aura's discovery code reads both configuration keys. The current field generator's class stub still contains the default namespace, so update the generated namespace manually if you replace `App\Aura\Fields` with a different value.

```php
use App\Aura\Fields\Rating;
use Aura\Base\Facades\Aura;

public function boot(): void
{
    Aura::registerFields([Rating::class]);
}
```

Registration populates the field picker and its option groups. A resource can also reference any autoloadable field class directly.

## Reference a custom field

Declare the field in a resource with its fully qualified class name. `type` and `slug` are required by Aura's field-definition mapper. `name`, `validation`, and other settings are part of the field configuration consumed by the form and table layers.

```php
<?php

namespace App\Aura\Resources;

use App\Aura\Fields\Rating;
use Aura\Base\Resource;

class Product extends Resource
{
    public static string $type = 'Product';

    public static ?string $slug = 'product';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Rating',
                'type' => Rating::class,
                'slug' => 'rating',
                'validation' => 'nullable|integer|min:0|max:5',
            ],
        ];
    }
}
```

## Define field settings

`getFields()` returns the settings that the Resource Editor exposes when someone configures an instance of your field. Merge `parent::getFields()` so the standard field settings remain available.

The base method has no declared PHP return type. The custom field hooks in this guide keep the same untyped signatures as `Field`. The resource contract is different: a resource's static `getFields()` must return an `array`.

A setting is a field-definition array. The keys used by the base settings and common built-in fields are:

| Key | Purpose |
| --- | --- |
| `type` | Fully qualified field class name. Required. |
| `slug` | Key used in the resource field data. Required. |
| `name` | Label shown in the editor and field wrapper. |
| `validation` | Laravel validation rules for the setting or resource field. |
| `default` | Value placed in the create form when the setting has no value. |
| `instructions` | Help text shown by the field wrapper. |
| `options` | Field-specific choices, for example for `Select`. |
| `on_index`, `on_forms`, `on_view` | Per-field visibility switches. |
| `disabled` | A boolean or a closure evaluated by `isDisabled()`. |
| `style.width` | Width percentage used by the field wrapper. |
| `conditional_logic` | Conditions that control whether the field is shown. |

For example, this adds a maximum value setting to `Rating`:

```php
public function getFields()
{
    return array_merge(parent::getFields(), [
        [
            'name' => 'Maximum rating',
            'type' => 'Aura\\Base\\Fields\\Number',
            'slug' => 'maximum',
            'validation' => 'required|integer|min:1|max:10',
            'default' => 5,
            'instructions' => 'Highest value allowed by the field.',
        ],
    ]);
}
```

The setting is available as `$field['maximum']` in the field views and in the field methods. Use `Tab`, `Repeater`, and other built-in field classes in the returned array when the settings need their own layout.

## Field properties

`Field` defines these properties. The base class leaves several of them untyped for compatibility with existing field classes.

| Property | Default | Use |
| --- | --- | --- |
| `$edit` | `null` | View name used for create and edit forms. |
| `$view` | `null` | View name used on the resource view page. `view()` falls back to `$edit`. |
| `$index` | `null` | Blade component name used for table display after `display_view` is checked. |
| `$optionGroup` | `'Fields'` | Group label in the Resource Editor's Type picker. |
| `$tableColumnType` | `'string'` | Laravel schema-builder method used for a field column in a generated custom-table migration. |
| `$tableNullable` | `true` | Whether the generated custom-table column is nullable. |
| `$type` | `'input'` | Behaviour category used by field pipelines. |
| `$group` | `false` | Whether the field contains child fields. |
| `$on_forms` | `true` | Default form visibility for the field type. A field definition can override it with `on_forms`. |
| `$taxonomy` | `false` | Marks the field as a taxonomy field. |
| `$sameLevelGrouping` | `false` | Controls grouping for structure fields. |
| `$wrapper` | `null` | Field class that Aura inserts around fields while building the field tree. |
| `$rawHtmlDisplay` | `false` | Allows the base `display()` implementation to return trusted HTML without escaping. |

The base `isInputField()` method treats `input`, `repeater`, and `group` as input fields. The base `isRelation()` method checks for the `relation` type. A custom relationship field may need to override `isRelation()`, as some built-in relationship fields use a different type and implement their own relationship behaviour.

`$tableColumnType` only affects a resource that uses a custom table. A field stored in the shared `meta` table does not create a column. See [Custom tables](/docs/custom-tables) and [Meta fields](/docs/meta-fields) before choosing a storage mode.

## Field value lifecycle

The form components bind field values to `form.fields.{slug}`. Aura then applies the following hooks when it saves, reads, and displays a value. None of the hooks below are abstract methods on `Field`; implement only the hooks you need.

### Form initialization

The create component initializes each declared field. If a field definition contains `default`, Aura puts that value in `form.fields.{slug}`. Boolean fields without a configured default start as `false`, and Tags fields without a configured default start as an empty array.

When the edit component loads a record, it calls an optional `hydrate($value, $field)` method for a field that defines it. Use this hook to reshape stored data for the edit control.

```php
public function hydrate($value, $field)
{
    return is_array($value) ? $value : [];
}
```

### Save hooks

For a normal field class, Aura applies the hooks in this order while the resource is being saved:

1. A resource method named `set{StudlySlug}Field($value)` takes precedence. Aura queues the raw value and calls that method after the resource row has been saved. The resource method owns persistence for that slug.
2. A `set` closure in the field definition runs as `$set($post, $field, $value)`.
3. The field class's `set($post, $field, $value)` method transforms the value.
4. An optional `saving($post, $field, $value)` method runs. If it returns a model instance, Aura continues with that model.
5. An optional `shouldSkip($post, $field)` method can return `true` to skip Aura's normal storage for the value.
6. Aura writes the result to a base-table column or a meta row according to the resource's storage configuration.

The base class does not declare `set`, `saving`, or `shouldSkip`. These signatures mirror the calls in the save pipeline:

```php
public function set($post, $field, $value)
{
    return $value === null ? null : (int) $value;
}

public function saving($post, $field, $value)
{
    return $post;
}

public function shouldSkip($post, $field)
{
    return false;
}
```

After the resource row is saved, Aura calls an optional `saved($post, $field, $value)` method. Relationship fields use this phase because the resource has an id. A `saved` method should perform its side effect; its return value is not stored by the save pipeline.

```php
public function saved($post, $field, $value)
{
    $post->tags()->sync((array) $value);
}
```

### Read and display hooks

`get($class, $value, $field = null)` converts a stored value when Aura resolves a field. The historical `$class` parameter is the field instance passed by Aura, not the resource model.

```php
public function get($class, $value, $field = null)
{
    return $value === null ? null : (int) $value;
}
```

`display($field, $value, $model)` formats the resolved value for a resource view or a table. The base implementation first checks the field definition's `display_view`, then the class's `$index` property, then escapes scalar values. A custom display method that returns HTML is responsible for escaping any data it interpolates.

```php
public function display($field, $value, $model)
{
    return $value === null ? '' : (string) $value;
}
```

Set `$rawHtmlDisplay = true` only when your field intentionally returns trusted markup through the base implementation. Do not mark user-provided text as raw HTML.

## Build the Blade views

The generated application views live under `resources/views/components/fields`. Aura includes them as dynamic components. On the resource create, edit, and view pages, the views receive:

| Variable | Value |
| --- | --- |
| `$field` | The field configuration array, including `slug`, `name`, settings, and the instantiated field under `$field['field']`. |
| `$form` | The current form data array. Field input values are under `$form['fields']`. |
| `$mode` | The current rendering mode passed by the Livewire resource component. |
| `$this` | The Livewire component rendering the field. In the generated display view, `$this->model` is the current resource. |

### Edit view

The generator writes this binding. Keep the `form.fields.{slug}` path when creating a custom control:

```blade
<x-aura::fields.wrapper :field="$field">
    <x-aura::input.text
        :disabled="optional($field)['disabled']"
        wire:model="form.fields.{{ optional($field)['slug'] }}"
        error="form.fields.{{ optional($field)['slug'] }}"
        placeholder="{{ optional($field)['placeholder'] ?? optional($field)['name'] }}"
        id="resource-field-{{ optional($field)['slug'] }}"
    />
</x-aura::fields.wrapper>
```

`x-aura::fields.wrapper` renders the label, instructions, field width, and validation error. Use Aura input components where they fit. A custom HTML control must still bind to the same Livewire property and should expose the validation error for that property.

### Display view

The generated view delegates formatting to the resource's `display()` method, which calls the field class's `display()` hook:

```blade
<x-aura::fields.wrapper :field="$field">
    {!! $this->model->display($field['slug']) !!}
</x-aura::fields.wrapper>
```

This view deliberately renders the result with `{!! !!}` because built-in fields can return markup. The base scalar path escapes ordinary values. If your `display()` override returns markup, escape database-backed values before concatenating them.

### Index components

Set `$index` when a field needs a separate Blade component for table cells:

```php
public $index = 'fields.rating-index';
```

Create `resources/views/components/fields/rating-index.blade.php`. Aura renders that component with these variables:

```blade
<span>{{ $value }}</span>
```

The component receives `$row`, `$field`, and `$value`. The base `display()` method passes those values to the dynamic component. A field definition's `display_view` takes precedence over `$index` and receives the same `$row`, `$field`, and `$value` variables.

## Filtering and field-specific options

`filterOptions()` returns the operators shown for the field in table filters. The base method returns the complete operator list. Override it when only a subset is valid:

```php
public function filterOptions()
{
    return [
        'is' => __('is'),
        'is_not' => __('is not'),
        'is_empty' => __('is empty'),
        'is_not_empty' => __('is not empty'),
    ];
}
```

`getFilterValues($model, $field)` returns predefined values for a filter. The base implementation returns an empty array. Built-in fields such as `Select` use their own `options()` method to provide values. A custom field should implement an options method only when its view or table integration calls it.

## Structure fields and wrappers

Structure fields group other fields instead of storing a scalar value. The built-in `Panel`, `Tab`, `Tabs`, and `Group` classes set `$group = true` and use their own `$type` values. `Repeater` also groups child fields while remaining an input field.

`$wrapper` contains another field class name. Aura's field-tree pipeline inserts that wrapper around fields that declare it. Set the `wrap` key on a field definition to `true` when the definition needs an additional wrapper instance. Build a custom structure field only when the existing grouping fields cannot represent the required layout.

## Package a field

Aura's plugin generator can create a field-plugin skeleton:

```bash
php artisan aura:plugin acme/rating
```

Choose **Field plugin** when prompted. The command creates `plugins/acme/rating`, runs the plugin configuration script, updates the application's Composer autoload mapping, and optionally adds the generated service provider to `config/app.php`.

The field-plugin stub uses a namespaced view directory and a service provider similar to this:

```php
public function configurePackage(Package $package): void
{
    $package
        ->name('rating')
        ->hasViews('acme-rating');
}
```

Point the field class at the namespaced views:

```php
class Rating extends Field
{
    public $edit = 'acme-rating::fields.rating';

    public $view = 'acme-rating::fields.rating-view';
}
```

The current field-plugin stub writes `$component` for the edit view, but Aura renders field forms through `Field::edit()`, which reads `$edit`. Replace `$component` with `$edit` as shown before using the generated plugin field. Register the class with `Aura::registerFields([Rating::class])` when it should appear in the Resource Editor's Type list.

## Related documentation

- [Fields](/docs/fields) lists the built-in field classes and their configuration.
- [Meta fields](/docs/meta-fields) explains shared meta-table storage.
- [Custom tables](/docs/custom-tables) explains column-backed resource storage.
- [Resource Editor](/docs/resource-editor) explains field configuration in the browser.
- [Plugins](/docs/plugins) covers the complete plugin workflow.
