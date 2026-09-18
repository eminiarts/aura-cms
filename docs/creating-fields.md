# Creating fields

A custom field controls how a value is edited, stored, read, and displayed. Create one by extending `Aura\Base\Fields\Field`, then add it to a resource's field definitions using its fully qualified class name.

## Generate a field

Run the field generator from a Laravel application that has Aura installed:

```bash
php artisan aura:field Rating
```

With the default field path configuration, the command creates these files:

```text
app/Aura/Fields/Rating.php
resources/views/components/fields/rating.blade.php
resources/views/components/fields/rating-view.blade.php
```

The command uses the name you provide for the class and a lowercase slug for the views. It does not overwrite existing class or view files.

The generated class points to the edit and display views and provides a method for adding field settings:

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

Aura discovers and registers fields under the configured path when its service provider boots. Discovery uses both the path and namespace settings. The current generator still writes the default namespace into the class, so update the generated file manually if you change the namespace setting.

For a field outside that path, register the class in your service provider to make it available in the Resource Editor's Type list:

```php
use App\Aura\Fields\Rating;
use Aura\Base\Facades\Aura;

public function boot(): void
{
    Aura::registerFields([Rating::class]);
}
```

Registration adds the field to the picker and its option group. Resources can also use any autoloadable field class directly, without registration.

## Reference a custom field

Add the field to the resource's static `getFields()` method. Each definition needs a `type` containing the field class and a `slug` identifying its value. You can also provide a label, validation rules, and other settings for forms and tables:

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

Your field can expose its own settings in the Resource Editor through `getFields()`. Merge the result of `parent::getFields()` with your additions to keep the standard settings available.

The field's base method has no declared PHP return type. The field hooks in this guide follow those untyped signatures. On a resource, however, the static `getFields()` method must declare an array return type.

Define each setting as a field array. The base settings and common built-in fields use these keys:

| Key | Purpose |
| --- | --- |
| `type` | Fully qualified field class name. Required. |
| `slug` | Key used in the resource field data. Required. |
| `name` | Label shown in the editor and field wrapper. |
| `validation` | Laravel validation rules for the setting or resource field. |
| `default` | Value placed in the create form when the setting has no value. |
| `instructions` | Help text shown by the field wrapper. |
| `options` | Field-specific choices, such as the items in a select field. |
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

Read the setting through `$field['maximum']` in your field views and methods. You can arrange settings with built-in fields such as tabs and repeaters.

## Field properties

The base field class defines the following properties. Several are untyped for compatibility with existing field classes.

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

Aura uses the type property to identify input and relationship fields. The base `isInputField()` method accepts the `input`, `repeater`, and `group` types. The base `isRelation()` method only accepts `relation`. Some built-in relationships use a different type and override that method, so your custom relationship field may need to do the same.

The column type property only affects resources that use a custom table. Fields stored as shared metadata do not create a column. See [Custom tables](/docs/custom-tables) and [Meta fields](/docs/meta-fields) before choosing a storage mode.

## Field value lifecycle

Forms bind each field's value to `form.fields.{slug}`. You can use the following hooks to prepare that value for editing, storage, or display. None are abstract methods, so implement only the hooks you need.

### Form initialization

The create form initializes each declared field with its configured default value. Without a default, boolean fields start as false and tag fields start as an empty array.

When the edit form loads a record, it calls your field's `hydrate($value, $field)` method if you define one. Use this hook to prepare stored data for the edit control.

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

After saving the resource row, Aura calls `saved($post, $field, $value)` if you define it. Use this hook for work that requires the resource's ID, such as saving relationships. Aura does not store the method's return value.

```php
public function saved($post, $field, $value)
{
    $post->tags()->sync((array) $value);
}
```

### Read and display hooks

Use `get($class, $value, $field = null)` to convert a stored value when Aura reads a field. Despite its historical name, the first parameter contains the field instance, not the resource model.

```php
public function get($class, $value, $field = null)
{
    return $value === null ? null : (int) $value;
}
```

Use `display($field, $value, $model)` to format a value for a resource view or table. By default, Aura uses the field definition's `display_view` component, then falls back to the class's `$index` component. If neither is set, it escapes scalar values. When you override this method to return HTML, you must escape any data you insert into the markup.

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

The field wrapper renders the label, instructions, field width, and validation error. Use Aura input components where they fit. A custom HTML control must bind to the same Livewire property and should show its validation error.

### Display view

The generated view asks the resource to format the value through your field's display hook:

```blade
<x-aura::fields.wrapper :field="$field">
    {!! $this->model->display($field['slug']) !!}
</x-aura::fields.wrapper>
```

The view uses Blade's unescaped output syntax because built-in fields can return markup. The base display method escapes ordinary scalar values. If your override returns markup, escape values read from the database before inserting them.

### Index components

Set `$index` when a field needs a separate Blade component for table cells:

```php
public $index = 'fields.rating-index';
```

Create `resources/views/components/fields/rating-index.blade.php` with the markup for a table cell:

```blade
<span>{{ $value }}</span>
```

The component receives the current row, field configuration, and resolved value as `$row`, `$field`, and `$value`. A field definition can override the component with `display_view`. That component receives the same variables.

## Filtering and field-specific options

Table filters offer the complete list of operators by default. Override `filterOptions()` to offer only the operators that apply to your field:

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

To provide a list of values for a filter, implement `getFilterValues($model, $field)`. The base method returns an empty array. Some built-in fields, such as select fields, also have an `options()` method. You only need that method if your view or table integration calls it.

## Structure fields and wrappers

Structure fields group other fields instead of storing a scalar value. Built-in panels, tabs, tab containers, and groups set `$group = true` and each define their own type. Repeaters also group child fields, but Aura still treats them as input fields.

To wrap a field in another field, set its `$wrapper` property to the wrapper's class name. Aura inserts the wrapper when it builds the field tree. Set `wrap` to true in a field definition when it needs an additional wrapper instance. Build a custom structure field only when the built-in grouping fields cannot represent your layout.

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

The current plugin generator names the edit view property `$component`. Rename it to `$edit`, as shown above, so Aura can find the view when rendering forms. Register the field with `Aura::registerFields([Rating::class])` if it should appear in the Resource Editor's Type list.

## Related documentation

- [Fields](/docs/fields) lists the built-in field classes and their configuration.
- [Meta fields](/docs/meta-fields) explains shared meta-table storage.
- [Custom tables](/docs/custom-tables) explains column-backed resource storage.
- [Resource Editor](/docs/resource-editor) explains field configuration in the browser.
- [Plugins](/docs/plugins) covers the complete plugin workflow.
