# Fields

Fields control how users enter a value and how Aura validates, stores, and displays it. Declare them as arrays in your resource's `getFields()` method. Aura includes 44 field classes in the `Aura\Base\Fields` namespace, defined in `src/Fields`.

This page describes the available field types and their options. Start with [Resources](/docs/resources) to learn how to declare fields on a resource. For storage behavior, see [Meta Fields](/docs/meta-fields) and [Custom Tables](/docs/custom-tables).

## Defining fields

Each field definition needs a class name in `type` and a unique key in `slug`. Layout fields also need a slug, even though they do not store a value:

```php
use Aura\Base\Resource;

class Article extends Resource
{
    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|max:255',
            ],
            [
                'name' => 'Body',
                'slug' => 'body',
                'type' => 'Aura\\Base\\Fields\\Wysiwyg',
                'validation' => 'nullable',
            ],
        ];
    }
}
```

Use a fully qualified class name, such as `Aura\Base\Fields\Text`, for the field type. Aura resolves it through Laravel's container, so a container binding also works. Short aliases such as `Text` do not resolve.

## Shared options

All field classes extend `Aura\Base\Fields\Field` and share the following options in the resource editor:

| Key | Type | Purpose |
|-----|------|---------|
| `name` | string | Label shown in forms and table headers. |
| `slug` | string | Unique field key. Layout fields need a slug even though they store no value. |
| `type` | string | The field class (fully-qualified). |
| `validation` | string/array | Laravel validation rules, applied on create and update. |
| `instructions` | string | Help text rendered under the field. |
| `searchable` | bool | Include the field in resource search. |
| `on_index` | bool | Show as a column in the table. |
| `on_forms` | bool | Show on create and edit forms. |
| `on_view` | bool | Show on the view page. |
| `style.width` | string/int | Field width in the form, in percent (`'style' => ['width' => '50']`). |
| `conditional_logic` | array/Closure | Show/hide rules. See [Conditional logic](#conditional-logic). |

You can also set two options that the base editor does not expose. Use `default` to provide an initial value on the create form, as described in [Defaults](#defaults). On supported inputs, including text fields, set `live` to update the form state on each keystroke instead of when the input loses focus. This uses Livewire's `wire:model.live` binding.

Other options, such as `placeholder`, depend on the field type. The descriptions below explain where they apply. Input views and the shared `Field::isDisabled()` helper also read the `disabled` option.

## Visibility

Use these options to choose where a field appears. Aura applies them while preparing the fields for each page:

- `on_forms => false` removes the field from both the create and edit forms.
- `on_create => false` removes it from the create form only.
- `on_edit => false` removes it from the edit form only.
- `on_view => false` removes it from the view page.
- `on_index => false` removes it from the table.

If you omit the create and edit options, form visibility depends only on `on_forms`. Fields inside a group, panel, or tab inherit its display settings.

<a id="defaults"></a>

## Defaults

The create form uses the `default` value from each input field's definition. For a checkbox field with an options array, a scalar default becomes a single-element array.

Without an explicit default, boolean fields start as false, tag fields start as an empty array, and other fields start as null.

Set defaults explicitly when writing a resource definition by hand. The resource editor copies defaults from the field class when it creates a definition. Aura does not apply those class defaults to handwritten arrays when rendering a form.

The `Aura\Base\Traits\DefaultFields` trait provides reusable definitions through `Aura::fields($key)`. The `created_at` and `updated_at` definitions are date fields with `enable_time => true`. The `user_id` definition is a belongs-to field. Add these definitions where you need them. The trait does not add them to every resource automatically.

<a id="conditional-logic"></a>

## Conditional logic

Use `conditional_logic` to show a field only when all its conditions pass. Each condition identifies a field, a comparison operator, and the value to compare against:

```php
[
    'name' => 'Shipping Address',
    'slug' => 'shipping_address',
    'type' => 'Aura\\Base\\Fields\\Textarea',
    'conditional_logic' => [
        [
            'field' => 'needs_shipping',
            'operator' => '==',
            'value' => true,
        ],
    ],
]
```

Supported operators are `==`, `!=`, `>`, `>=`, `<`, and `<=`. Any other operator, including a single `=`, leaves the field hidden.

To check the current user's role, set `field` to `role`. Role conditions support equality and inequality comparisons. Super admins always pass these conditions:

```php
'conditional_logic' => [
    ['field' => 'role', 'operator' => '==', 'value' => 'admin'],
],
```

For logic the operator table cannot express, pass a closure. It receives the model and the current form state and returns a boolean:

```php
'conditional_logic' => function ($model, $form) {
    return data_get($form, 'fields.status') === 'published'
        && data_get($form, 'fields.price') > 0;
},
```

## Input fields

### Text

A text field accepts a single line of text.

![Text field](/images/Fields/Text.png)

```php
[
    'name' => 'Title',
    'slug' => 'title',
    'type' => 'Aura\\Base\\Fields\\Text',
    'validation' => 'required|max:255',
    'placeholder' => 'Enter a title',
    'default' => '',
    'prefix' => 'https://',
    'suffix' => '.com',
    'autocomplete' => 'off',
    'max_length' => 255,
    'live' => true,
]
```

Use `default`, `placeholder`, and `autocomplete` to configure the input. A prefix or suffix displays text before or after the input value.

To limit the value's length, add a `max:` validation rule. The field accepts a `max_length` option, but the input does not enforce it.

Column type: `string`.

### Textarea

A textarea accepts multiple lines of text.

```php
[
    'name' => 'Excerpt',
    'slug' => 'excerpt',
    'type' => 'Aura\\Base\\Fields\\Textarea',
    'validation' => 'nullable|max:300',
    'placeholder' => 'Short summary',
    'rows' => 3,
    'max_length' => 300,
]
```

The input supports `default`, `placeholder`, and `autocomplete`. Set `rows` to choose its height. The resource editor uses three rows by default, while an input with no rows setting uses four.

Use a validation rule to limit the value's length. The field accepts `max_length`, but the input does not enforce it.

Column type: `text`.

### Number

A number field accepts numeric input.

```php
[
    'name' => 'Price',
    'slug' => 'price',
    'type' => 'Aura\\Base\\Fields\\Number',
    'validation' => 'required|numeric|min:0',
    'placeholder' => '0',
    'prefix' => '$',
    'suffix' => 'USD',
]
```

Number fields support the same default value, placeholder, autocomplete, prefix, and suffix options as text fields. Aura casts the stored value to an integer when reading it.

For exact-query configuration, the class also reads `number_type`, `precision`, and `scale`. The editor does not expose these options, and they do not change the integer cast.

Numeric filter operators are `equals`, `not_equals`, `greater_than`, `less_than`, `greater_than_or_equal`, `less_than_or_equal`, `is_empty`, and `is_not_empty`.

Column type: `integer`.

### Email

An email field provides an email input.

```php
[
    'name' => 'Email',
    'slug' => 'email',
    'type' => 'Aura\\Base\\Fields\\Email',
    'validation' => 'required|email',
    'placeholder' => 'user@example.com',
]
```

Options: `default`, `placeholder`, and `autocomplete`.

Column type: `string`.

### Phone

A phone field uses a telephone input. It does not format numbers or handle international dialing codes.

```php
[
    'name' => 'Phone',
    'slug' => 'phone',
    'type' => 'Aura\\Base\\Fields\\Phone',
    'validation' => 'nullable|string',
]
```

The class adds no configuration fields. Its view reads the shared `placeholder`, `autocomplete`, `disabled`, and `live` keys.

Column type: `string`.

### Password

A password field hashes its value with `Hash::make()` when saved, unless the value is already hashed. Leaving the input blank keeps the stored password unchanged.

```php
[
    'name' => 'Password',
    'slug' => 'password',
    'type' => 'Aura\\Base\\Fields\\Password',
    'validation' => 'nullable|min:8',
]
```

Options: none beyond the shared options. There is no built-in confirmation, strength meter, or show/hide toggle on the field itself.

Column type: `string`.

### Slug

Text that the browser derives from another field and slugifies as the user types. The current script lowercases text, removes accents, and replaces spaces and punctuation with underscores.

```php
[
    'name' => 'Slug',
    'slug' => 'slug',
    'type' => 'Aura\\Base\\Fields\\Slug',
    'validation' => 'required',
    'based_on' => 'title',
    'custom' => true,
    'disabled' => true,
]
```

Set `based_on` to the source field's slug. It is required, and the form throws an exception if it is missing.

Set `custom` to show a toggle for manual editing. With `disabled => true`, the input starts locked and derives its value from the source field. Otherwise, it starts editable. Default value and placeholder options are also supported.

Aura does not check uniqueness automatically. Add a `unique:` validation rule if the slug must be unique.

Column type: `string`.

<a id="date"></a>

### Date

A date field lets users pick a date.

![Date field](/images/Fields/Date.png)

```php
[
    'name' => 'Published Date',
    'slug' => 'published_at',
    'type' => 'Aura\\Base\\Fields\\Date',
    'validation' => 'nullable|date',
    'format' => 'd.m.Y',
    'display_format' => 'd.m.Y',
    'enable_input' => true,
    'maxDate' => 30,
    'weekStartsOn' => 1,
]
```

Configure the picker with the following options. Date formats use PHP `date()` tokens.

| Option | Behavior |
|--------|----------|
| `format` | Defaults to `d.m.Y`. |
| `display_format` | Defaults to `d.m.Y`. |
| `enable_input` | Allows typed input. Defaults to true. |
| `maxDate` | Number of days from today to the latest selectable date. The resource editor accepts 0 to 365. |
| `minDate` | Sets the earliest date when supplied to the view. |
| `weekStartsOn` | First day of the week, from 0 for Sunday to 6 for Saturday. Defaults to 1. |
| `options.native` | Uses the browser's native date input. |

Filter operators are `date_is`, `date_is_not`, `date_before`, `date_after`, `date_on_or_before`, `date_on_or_after`, `date_is_empty`, and `date_is_not_empty`.

Column type: `date`.

### Datetime

A datetime field lets users pick both a date and a time.

```php
[
    'name' => 'Event Start',
    'slug' => 'event_start',
    'type' => 'Aura\\Base\\Fields\\Datetime',
    'validation' => 'nullable|date',
    'format' => 'd.m.Y H:i',
    'display_format' => 'd.m.Y H:i',
    'enable_input' => true,
    'maxDate' => 30,
    'minTime' => '09:00',
    'maxTime' => '18:00',
    'weekStartsOn' => 1,
]
```

The datetime picker supports the [date picker options](#date), plus `minTime` and `maxTime` to limit the selectable time. Both format options default to `d.m.Y H:i`. Typed input is enabled by default, and the week starts on Monday.

It supports the same filter operators as date fields. Saved filters using the older bare range names remain supported.

Column type: `timestamp`.

### Time

A time field lets users pick a time.

```php
[
    'name' => 'Opening Time',
    'slug' => 'opening_time',
    'type' => 'Aura\\Base\\Fields\\Time',
    'validation' => 'nullable',
    'format' => 'H:i',
    'display_format' => 'H:i',
    'enable_input' => true,
    'enable_seconds' => false,
    'minTime' => '09:00',
    'maxTime' => '17:00',
]
```

Both `format` and `display_format` default to `H:i`. Typed input is enabled by default through `enable_input`. Use `minTime` and `maxTime` to limit the selectable time, or `options.native` to use a native input. The field also accepts `weekStartsOn`, with a default of 1.

The resource editor exposes `enable_seconds` with a default of false, but the current template does not pass that setting to the picker.

Column type: `string`.

## Choice fields

### Boolean

A boolean field displays a toggle switch and casts its value to a boolean on read and write. Tables and view pages show a check or cross icon.

![Boolean field](/images/Fields/Boolean.png)

```php
[
    'name' => 'Featured',
    'slug' => 'is_featured',
    'type' => 'Aura\\Base\\Fields\\Boolean',
    'default' => false,
]
```

Options: `default` (default false).

Column type: `string`.

### Select

A select field displays a dropdown with choices defined as key/value pairs.

![Select field](/images/Fields/Select.png)

```php
[
    'name' => 'Category',
    'slug' => 'category',
    'type' => 'Aura\\Base\\Fields\\Select',
    'validation' => 'required',
    'options' => [
        ['key' => 'news', 'value' => 'News'],
        ['key' => 'blog', 'value' => 'Blog Post'],
        ['key' => 'tutorial', 'value' => 'Tutorial'],
    ],
    'default' => 'blog',
]
```

Define choices as key/value pairs in `options`, and use `default` to choose the initial value. The dropdown currently allows only one selection. Its template does not add the HTML multiple attribute, so `allow_multiple` has no effect.

To calculate choices at runtime, add a `get{Slug}Options()` method to the resource. Return an array whose keys are stored values and whose values are labels:

```php
public function getCategoryOptions()
{
    return ['news' => 'News', 'blog' => 'Blog Post'];
}
```

Filter operators: `is`, `is_not`, `is_empty`, `is_not_empty`. Column type: `string`.

### Radio

A radio field lets users choose one value from a set of radio buttons.

```php
[
    'name' => 'Plan',
    'slug' => 'plan',
    'type' => 'Aura\\Base\\Fields\\Radio',
    'validation' => 'required',
    'options' => [
        ['key' => 'basic', 'value' => 'Basic'],
        ['key' => 'pro', 'value' => 'Pro'],
        ['key' => 'enterprise', 'value' => 'Enterprise'],
    ],
    'default' => 'basic',
]
```

Options: `options` (key/value repeater) and `default`. The view reads the options from the field definition and renders one selected value.

Column type: `string`.

### Checkbox

A checkbox field lets users choose several values. Aura stores the selection as JSON and reads it back as an array.

![Checkbox field](/images/Fields/Checkbox.png)

```php
[
    'name' => 'Features',
    'slug' => 'features',
    'type' => 'Aura\\Base\\Fields\\Checkbox',
    'options' => [
        ['key' => 'api', 'value' => 'API Access'],
        ['key' => 'support', 'value' => 'Priority Support'],
        ['key' => 'export', 'value' => 'Data Export'],
    ],
    'default' => ['api'],
]
```

Define choices as key/value pairs in `options`, and use `default` for the initial selection. The form always reads choices from the field definition. A `get{Slug}Options()` method cannot replace them, even though the field class provides an `options()` helper for other callers.

Column type: `string`, holding JSON.

### Status

A status field is a dropdown whose choices each have a color. Tables and view pages display the selected status as a colored badge.

```php
[
    'name' => 'Status',
    'slug' => 'status',
    'type' => 'Aura\\Base\\Fields\\Status',
    'default' => 'draft',
    'options' => [
        ['key' => 'draft', 'value' => 'Draft', 'color' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300'],
        ['key' => 'review', 'value' => 'In Review', 'color' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300'],
        ['key' => 'published', 'value' => 'Published', 'color' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300'],
    ],
]
```

Define each choice with a key, label, and color. The color value contains Tailwind classes. The editor offers blue, green, red, yellow, indigo, purple, pink, gray, orange, and teal presets, including dark-mode variants. Use `default` to choose the initial status.

The form reads choices directly from the field definition. A `get{Slug}Options()` method cannot replace them, even though the field class provides an `options()` helper for other callers. Only one status can be selected, so `allow_multiple` has no effect.

Column type: `string`.

## Media fields

Media fields store attachment IDs and integrate with the [Media Library](/docs/media-manager).

### Image

An image field provides uploads with thumbnail previews. Aura stores the attachment IDs as a JSON array. The table shows the first image and a badge with the number of remaining images.

![Image field](/images/Fields/Image.png)

```php
[
    'name' => 'Featured Image',
    'slug' => 'featured_image',
    'type' => 'Aura\\Base\\Fields\\Image',
    'validation' => 'nullable',
    'use_media_manager' => true,
    'min_files' => 1,
    'max_files' => 5,
    'allowed_file_types' => 'jpg, png, gif',
]
```

Use `max_files` to limit how many files users can select in the media picker. The uploader accepts at most 20 files per batch and applies its own MIME type and blocked-extension rules. Its file size limit comes from `aura.media.max_file_size`, which defaults to 102400 KiB.

The field also accepts `use_media_manager`, `min_files`, and `allowed_file_types`, a comma-separated list of extensions. These settings currently have no effect. The form always uses the media uploader and does not enforce the field's minimum file count or extension list.

Column type: `string` (holds a JSON array of attachment IDs).

### File

A file field accepts general file uploads and stores multiple files as JSON.

```php
[
    'name' => 'Attachments',
    'slug' => 'attachments',
    'type' => 'Aura\\Base\\Fields\\File',
    'validation' => 'nullable',
]
```

File fields have no options beyond the shared options. They use the uploader limits described above but do not expose the image field's media settings.

Column type: `string`.

## JavaScript fields

<a id="advancedselect"></a>

### AdvancedSelect

This selector loads selected records first, then fetches search results in pages of ten. To preload every related record and filter in the browser instead, set `api => false`. You must provide the related resource class.

```php
[
    'name' => 'Products',
    'slug' => 'products',
    'type' => 'Aura\\Base\\Fields\\AdvancedSelect',
    'resource' => 'App\\Aura\\Resources\\Product',
    'multiple' => true,
    'create' => true,
    'return_type' => 'id',
    'polymorphic_relation' => true,
    'thumbnail' => 'featured_image',
    'view_select' => 'custom.select',
    'view_selected' => 'custom.selected',
    'view_view' => 'custom.view',
    'view_index' => 'custom.index',
]
```

The selector supports these options:

| Option | Behavior |
|--------|----------|
| `resource` | Required related resource class. |
| `multiple` | Allows multiple selections. The editor default is true. |
| `create` | Shows the inline create action. Defaults to false. |
| `return_type` | Accepts `id` or `object` in the field configuration. |
| `polymorphic_relation` | Defaults to true when resolving the relationship. See storage behavior below. |
| `api` | Loads search results over AJAX. Defaults to true on the field class. |
| `reverse` | Runtime relationship option. |
| `thumbnail` | Field slug used by custom views. |
| `view_select`, `view_selected`, `view_view`, `view_index` | Custom view slugs. |

The editor does not expose the API and reverse relationship options. Set them directly in the field definition.

When `polymorphic_relation` is truthy, Aura syncs the selected records through the `post_relations` pivot table. Set it to false to store selected IDs as JSON in the resource's meta instead.

Selected records remain visible even when they fall outside the first page of search results.

Option group: JavaScript fields.

### Color

A color field lets users pick a color.

```php
[
    'name' => 'Brand Color',
    'slug' => 'brand_color',
    'type' => 'Aura\\Base\\Fields\\Color',
    'validation' => 'nullable',
    'format' => 'hex',
    'options' => ['native' => true],
    'default' => '#3B82F6',
]
```

Choose a color format with `format`. Supported values are `hex`, `rgb`, `hsl`, `hsv`, and `cmyk`.

To use the browser's native color input, set `'options' => ['native' => true]` as shown above. The resource editor currently saves this setting at the top level rather than inside options, so its native-input toggle has no effect.

Column type: `string`.

### Code

A code field provides an editor with syntax highlighting. It formats JSON values for readability when loading them.

![Code field](/images/Fields/Code.png)

```php
[
    'name' => 'Custom CSS',
    'slug' => 'custom_css',
    'type' => 'Aura\\Base\\Fields\\Code',
    'language' => 'css',
    'line_numbers' => true,
    'min_height' => 200,
]
```

The editor supports HTML, CSS, JavaScript, PHP, JSON, YAML, and Markdown. Set the required `language` option to the lowercase language name. Use `line_numbers` to control line numbering and `min_height` to set the editor height in pixels, with a minimum of 100.

Column type: `string`.

### Wysiwyg

This rich text editor produces HTML. Before displaying a string value, Aura sanitizes it with Symfony's HTML sanitizer.

```php
[
    'name' => 'Content',
    'slug' => 'content',
    'type' => 'Aura\\Base\\Fields\\Wysiwyg',
    'validation' => 'nullable',
]
```

Options: none beyond the shared options.

Column type: `text`.

## Relationship fields

### BelongsTo

This field selects one related record, stores its ID, and links to it from the table. Set `resource` to the related resource class. By default, the selector loads all related records and filters them in the browser. Set `api => true` to search over AJAX instead.

```php
[
    'name' => 'Author',
    'slug' => 'author_id',
    'type' => 'Aura\\Base\\Fields\\BelongsTo',
    'resource' => 'App\\Aura\\Resources\\Author',
    'on_index' => true,
]
```

Options: `resource` (required related resource class), `api` (search over AJAX instead of preloading all records), and `relation` when the field is used to resolve a named relationship. In AJAX mode the search covers the related resource's searchable fields, including meta fields. The API returns up to 20 results for shared-table resources and up to 50 for custom-table resources.

Column type: `bigInteger`.

### HasMany

This field displays an embedded table of related records and stores no value on the resource itself. Set `resource` to the related resource class unless you provide a custom relationship closure.

```php
[
    'name' => 'Comments',
    'slug' => 'comments',
    'type' => 'Aura\\Base\\Fields\\HasMany',
    'resource' => 'App\\Aura\\Resources\\Comment',
    'foreign_key' => 'post_id',
]
```

By default, Aura finds related records through the `post_relations` pivot using the field slug. Set `column` to resolve a direct Eloquent has-many relationship on that column. Use `reverse` and `reverse_slug` to resolve the inverse through the pivot.

For a custom query, supply a `relation` closure that receives the query and parent model. You can also set `foreign_key` to prefill the create link.

Type: `relation`.

### HasOne

This field does not yet provide a working form selector. Its editor view only displays "Has one". To let users pick one related record, use `AdvancedSelect` with `multiple => false`.

The class extends the advanced selector with multiple selection disabled, API loading enabled, and search enabled.

```php
[
    'name' => 'Profile',
    'slug' => 'profile',
    'type' => 'Aura\\Base\\Fields\\HasOne',
    'resource' => 'App\\Aura\\Resources\\Profile',
    'create' => true,
]
```

The field inherits the [advanced selector options](#advancedselect), including the required related resource, inline creation, return type, relationship storage, and custom views. Although the class fixes multiple selection to false, handwritten field definitions should still set `multiple => false` so Aura normalizes the value correctly.

Type: `relation`.

### BelongsToMany

This field displays a table of records from a many-to-many relationship on the parent record. Set the field slug to the parent's relationship method, or provide that method name as a string in `relation`. The required `resource` option identifies the model to display. It does not choose or sync the relationship.

If the parent has no matching relationship method, the table returns no rows. Without a parent record, the field leaves the target query unchanged. Its field metadata marks it as a relationship and a group.

```php
[
    'name' => 'Categories',
    'slug' => 'categories',
    'type' => 'Aura\\Base\\Fields\\BelongsToMany',
    'resource' => 'App\\Aura\\Resources\\Category',
    // The parent record must expose a categories() many-to-many relation.
]
```

Type: `relation`.

### Tags

A tags field displays selected tags as badges. Aura stores the polymorphic relationship in the `post_relations` pivot and sorts tags by its `order` column. The related resource class is required.

```php
[
    'name' => 'Categories',
    'slug' => 'categories',
    'type' => 'Aura\\Base\\Fields\\Tags',
    'resource' => 'App\\Aura\\Resources\\Category',
    'create' => true,
    'max_tags' => 10,
]
```

Set `resource` to the tag resource class. Existing tag IDs are checked through that resource's scoped query.

Set `create => true` to show the controls for adding tags and allow new labels. Aura creates a label only if the current user can create the target resource. Setting creation to false rejects new labels, and the resource editor uses false by default.

Use `max_tags` to limit selections in the browser through Tagify. This taxonomy field supports the `contains` and `does_not_contain` filter operators.

Type: `input` (relation-backed via `post_relations`).

## Structure fields

Structure fields arrange child fields into groups. The child fields hold the values to store.

> Declare child fields immediately after their parent in the same flat `getFields()` array. Aura uses that order to build the nesting. Do not place children inside a `fields` key. Aura skips that nested array, so those children will not become working fields.

Use these rules to close or change the current nesting:

- Set `'exclude_level' => N` to move a field up N levels, outside a repeater or group.
- A tab with `'global' => true` starts at the top level. Aura groups global tabs in a tabs container.
- Set `same_level_grouping => false` to disable the usual same-level grouping for a panel or tab.

If a repeater or group is the last field in its tab or panel, the end of the array closes it.

### Group

A group places child fields in one visual block on the form. Declare the child fields immediately after it.

```php
[
    'name' => 'Contact',
    'slug' => 'contact',
    'type' => 'Aura\\Base\\Fields\\Group',
],
['name' => 'Email', 'slug' => 'email', 'type' => 'Aura\\Base\\Fields\\Email'],
['name' => 'Phone', 'slug' => 'phone', 'type' => 'Aura\\Base\\Fields\\Phone'],
// The next field belongs to the group too. Add 'exclude_level' => 1 to it
// (or start a new Panel/Tab) if it should sit outside the group instead.
```

Type: `group`.

### Repeater

A repeater lets users add rows containing the same set of child fields. Aura stores the rows as JSON. Declare the child fields immediately after the repeater. Their runtime slugs include the repeater slug and row index.

```php
[
    'name' => 'FAQ',
    'slug' => 'faq',
    'type' => 'Aura\\Base\\Fields\\Repeater',
    'min' => 0,
    'max' => 10,
],
['name' => 'Question', 'slug' => 'question', 'type' => 'Aura\\Base\\Fields\\Text'],
['name' => 'Answer', 'slug' => 'answer', 'type' => 'Aura\\Base\\Fields\\Textarea'],
// A field that should come AFTER the repeater, not inside it:
['name' => 'Note', 'slug' => 'note', 'type' => 'Aura\\Base\\Fields\\Text', 'exclude_level' => 1],
```

Options: `min` and `max` are written by the Resource Editor with a default of 0. The current repeater view does not enforce these values when adding rows.

Column type: `string` (holds JSON).

### Panel

A panel groups the fields declared after it. By default, the next panel or tab starts another group at the same level.

```php
[
    'name' => 'Sidebar',
    'slug' => 'panel-sidebar',
    'type' => 'Aura\\Base\\Fields\\Panel',
    'style' => ['width' => '30'],
],
['name' => 'Status', 'slug' => 'status', 'type' => 'Aura\\Base\\Fields\\Text'],
// Fields keep flowing into this panel until the next Panel or Tab is declared.
```

Type: `panel`.

### Tab

A tab contains the fields declared after it. Usually, you should set `'global' => true` so consecutive tabs sit side by side. Aura adds the tabs container automatically.

```php
['name' => 'Content', 'slug' => 'tab-content', 'type' => 'Aura\\Base\\Fields\\Tab', 'global' => true],
['name' => 'Body', 'slug' => 'body', 'type' => 'Aura\\Base\\Fields\\Wysiwyg'],

['name' => 'Settings', 'slug' => 'tab-settings', 'type' => 'Aura\\Base\\Fields\\Tab', 'global' => true],
['name' => 'Published', 'slug' => 'published', 'type' => 'Aura\\Base\\Fields\\Boolean'],
```

Type: `tab`.

### Tabs

This container groups tabs into a tabbed interface. You rarely need to declare it yourself because Aura adds it around consecutive tab fields.

Type: `tabs`.

## Layout fields

Layout fields render presentation only and store no value.

### Heading

A heading labels a section of the form. Like other field definitions, it requires a slug.

```php
[
    'name' => 'User Settings',
    'slug' => 'user-settings-heading',
    'type' => 'Aura\\Base\\Fields\\Heading',
]
```

### HorizontalLine

A horizontal line separates sections of the form. It requires a slug.

```php
[
    'slug' => 'content-divider',
    'type' => 'Aura\\Base\\Fields\\HorizontalLine',
]
```

### View

Use this field to display a Blade view within the form.

```php
[
    'name' => 'Custom Block',
    'slug' => 'custom-block',
    'type' => 'Aura\\Base\\Fields\\View',
    'view' => 'custom.field-block',
]
```

Options: `view` (the Blade view to include). The view receives the field and model; there is no separate `data` option.

Type: `view`.

### ViewValue

Displays the stored value read-only, using the shared value view. Use it for a computed or system value that should appear on the form without an input.

```php
[
    'name' => 'Created At',
    'slug' => 'created_at_display',
    'type' => 'Aura\\Base\\Fields\\ViewValue',
]
```

### LivewireComponent

Use this field to embed a Livewire component.

```php
[
    'name' => 'Custom Widget',
    'slug' => 'custom-widget-field',
    'type' => 'Aura\\Base\\Fields\\LivewireComponent',
    'component' => 'custom-widget',
]
```

Options: `component` (the component name). The component is passed the current `model` and `field`; there is no `params` option.

Type: `livewire-component`.

## Utility fields

### ID

This field represents the primary key. It is hidden on forms by default.

```php
[
    'name' => 'ID',
    'slug' => 'id',
    'type' => 'Aura\\Base\\Fields\\ID',
]
```

Column type: `bigIncrements`, non-nullable.

### Hidden

Stores a value without rendering an input.

```php
[
    'name' => 'Type',
    'slug' => 'type',
    'type' => 'Aura\\Base\\Fields\\Hidden',
    'default' => 'post',
]
```

Options: none beyond the shared options; `default` works as described in [Defaults](#defaults).

Column type: `string`.

### Embed

This field embeds content using the model's `url` and `mime_type` attributes. It works only on resources that provide those attributes, such as the built-in attachment resource. The field's own slug and stored value are ignored.

There are no field-specific options for a provider or URL.

```php
[
    'name' => 'Preview',
    'slug' => 'preview',
    'type' => 'Aura\\Base\\Fields\\Embed',
]
```

Column type: `string`.

### Json

Stores structured data. Arrays are JSON-encoded on save and decoded on read.

```php
[
    'name' => 'Settings',
    'slug' => 'settings',
    'type' => 'Aura\\Base\\Fields\\Json',
    'validation' => 'nullable',
]
```

Options: `height` (editor height in pixels, default 300) plus the shared options. The class does not override the column type, so on custom tables it maps to `string`.

Column type: `string`.

### Permissions

This field displays the permission matrix used by the [Roles & Permissions](/docs/roles-permissions) system and stores its value as JSON.

```php
[
    'name' => 'Permissions',
    'slug' => 'permissions',
    'type' => 'Aura\\Base\\Fields\\Permissions',
    'resource' => 'App\\Aura\\Resources\\Permission',
]
```

Options: `resource`.

Column type: `string` (holds JSON).

### Roles

This field assigns roles to a user and respects the current team when [teams](/docs/teams) are enabled. It extends the advanced selector and syncs the user's roles when saved. The built-in user resource is its main use.

```php
[
    'name' => 'Roles',
    'slug' => 'roles',
    'type' => 'Aura\\Base\\Fields\\Roles',
    'resource' => 'Aura\\Base\\Resources\\Role',
    'multiple' => false,
]
```

Type: `input`. Although it inherits the input type, the class reports a relationship through `isRelation()`. The built-in user resource supplies the related role resource and allows only one selection.

### GlobalAdmin

This boolean field controls the instance-wide `global_admin` flag on the built-in user resource. It looks and behaves like a boolean toggle.

Only users allowed by the `AuraGlobalAdmin` gate can change the flag. The save hook checks this permission before updating the users table. Attempts by other users leave the stored flag unchanged.

```php
[
    'name' => 'Global Admin',
    'slug' => 'global_admin',
    'type' => 'Aura\\Base\\Fields\\GlobalAdmin',
    'default' => false,
]
```

Option group: Choice Fields. The inherited field metadata declares a `string` column type. The built-in user resource stores the value in its global admin column through the permission-checked save hook.

### UserTeams

This field manages team membership on the built-in user resource. It extends the many-to-many table field to scope memberships to the user and provides a dedicated Livewire editor on the user's view page.

```php
[
    'name' => 'Teams',
    'slug' => 'teams',
    'type' => 'Aura\\Base\\Fields\\UserTeams',
    'resource' => 'Aura\\Base\\Resources\\Team',
    'on_forms' => false,
    'on_view' => true,
]
```

Type: `relation`. The built-in definition shows this field only on the user's view page, and only when teams are enabled.

## Field reference

Column types apply only to [custom tables](/docs/custom-tables). In the shared meta store, scalar values use the meta table's `value` column. Aura does not generate value columns for relationship or layout fields.

The option group is the heading under which a field appears in the resource editor's field picker.

| Field | `type` | `group` | Column type | Option group |
|-------|--------|---------|-------------|--------------|
| Text | input | no | string | Input Fields |
| Textarea | input | no | text | Input Fields |
| Number | input | no | integer | Input Fields |
| Email | input | no | string | Input Fields |
| Phone | input | no | string | Input Fields |
| Password | input | no | string | Input Fields |
| Slug | input | no | string | Input Fields |
| Date | input | no | date | Input Fields |
| Datetime | input | no | timestamp | Input Fields |
| Time | input | no | string | Input Fields |
| Boolean | input | no | string | Choice Fields |
| GlobalAdmin | input | no | string | Choice Fields |
| Select | input | no | string | Choice Fields |
| Radio | input | no | string | Choice Fields |
| Checkbox | input | no | string | Choice Fields |
| Status | input | no | string | Choice Fields |
| Image | input | no | string | Media Fields |
| File | input | no | string | Media Fields |
| AdvancedSelect | input | no | string or pivot | JS Fields |
| Color | input | no | string | JS Fields |
| Code | input | no | string | JS Fields |
| Wysiwyg | input | no | text | JS Fields |
| BelongsTo | input | no | bigInteger | Relationship Fields |
| HasMany | relation | no | none | Relationship Fields |
| HasOne | relation | no | none | Relationship Fields |
| BelongsToMany | relation | yes | none | Relationship Fields |
| Tags | input | no | string or pivot | Fields |
| Group | group | yes | none | Structure Fields |
| Repeater | input | yes | string | Structure Fields |
| Panel | panel | yes | none | Structure Fields |
| Tab | tab | yes | none | Structure Fields |
| Tabs | tabs | yes | none | Fields |
| Heading | input | no | none | Layout Fields |
| HorizontalLine | input | no | none | Layout Fields |
| View | view | no | none | Fields |
| ViewValue | input | no | none | Fields |
| LivewireComponent | livewire-component | no | none | Fields |
| ID | input | no | bigIncrements | Fields |
| Hidden | input | no | string | Fields |
| Embed | input | no | string | Fields |
| Json | input | no | string | Fields |
| Permissions | input | no | string | Fields |
| Roles | input | no | string or pivot | Fields |
| UserTeams | relation | yes | none | Relationship Fields |

## Field lifecycle and storage hooks

Field hooks let you transform values, control storage, and customize display. When saving a resource, Aura calls the save hooks that your field class defines. Implement only the hooks you need.

The base field class defines `display`, `get`, and `value`. The remaining hooks in the table are optional and are called only if they exist on your class:

| Method | When it runs |
|--------|--------------|
| `set($post, $field, $value)` | Transforms the value before it is written. Return the value to store. |
| `saving($post, $field, $value)` | Runs during the model's `saving` event; return a modified model to replace it. |
| `shouldSkip($post, $field)` | Return true to skip writing this field entirely. |
| `saved($post, $field, $value)` | Runs after the model is saved. Relationship fields use it to sync pivots. |
| `get($class, $value, $field = null)` | Transforms the raw stored value when it is read back. |
| `value($value)` | Casts the value for display/use (e.g. `Number` casts to int). |
| `display($field, $value, $model)` | Returns the HTML shown on the table and view page. |

You can also attach a per-field `set` closure in the field array, which runs before the field class's `set()`:

```php
[
    'name' => 'Title',
    'slug' => 'title',
    'type' => 'Aura\\Base\\Fields\\Text',
    'set' => fn ($post, $field, $value) => trim($value),
]
```

## Custom fields

Generate a field class with the Artisan command:

```bash
php artisan aura:field Rating
```

The command creates a field class at `app/Aura/Fields/Rating.php` that extends the base field class. It also creates two Blade files in `resources/views/components/fields/`: `rating.blade.php` for the editor and `rating-view.blade.php` for display.

Aura discovers and registers fields in `app/Aura/Fields` automatically.

The generated class looks like this:

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
            // Configuration options for this field
        ]);
    }
}
```

Return field definitions from `getFields()` to add configuration options to the resource editor. Use the lifecycle hooks above to control storage and display. The following rating field stores an integer and displays it as stars:

```php
class Rating extends Field
{
    public $edit = 'fields.rating';

    public $view = 'fields.rating-view';

    public $tableColumnType = 'integer';

    public $optionGroup = 'Custom Fields';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Max Stars',
                'slug' => 'max_stars',
                'type' => 'Aura\\Base\\Fields\\Number',
                'default' => 5,
            ],
        ]);
    }

    public function set($post, $field, $value)
    {
        return (int) $value;
    }

    public function display($field, $value, $model)
    {
        $max = $field['max_stars'] ?? 5;

        return str_repeat('★', (int) $value) . str_repeat('☆', $max - (int) $value);
    }
}
```

If you register fields from a package or service provider rather than the `app/Aura/Fields` directory, add their class names with `registerFields()`:

```php
use Aura\Base\Facades\Aura;

Aura::registerFields([
    \App\Aura\Fields\Rating::class,
]);
```

For a full walkthrough of the generated Blade files and editor wiring, see [Creating Fields](/docs/creating-fields).

## Related

- [Resources](/docs/resources), declaring fields on a resource
- [Meta Fields](/docs/meta-fields), how meta-backed values are stored
- [Custom Tables](/docs/custom-tables), dedicated columns and `tableColumnType`
- [Creating Fields](/docs/creating-fields), building a custom field end to end
- [Media Library](/docs/media-manager), how Image and File uploads are handled
