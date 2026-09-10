# Fields

Fields are PHP classes under `Aura\Base\Fields`. Each class controls how a value is entered, validated, stored, and displayed. You declare fields as plain arrays in a resource's `getFields()` method. The package registers 44 concrete field classes from `src/Fields`.

This page is the field type reference. For how fields fit into a resource, see [Resources](/docs/resources); for the meta vs. custom-table storage split, see [Meta Fields](/docs/meta-fields) and [Custom Tables](/docs/custom-tables).

## Defining fields

A field definition is a plain array. Every definition requires `type` (the field class) and `slug`. Layout fields also need a slug even though they do not store a value:

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

The `type` value is resolved through Laravel's container with `app($field['type'])`, so it must be a resolvable class name or container binding. Always use the fully-qualified class name, such as `Aura\Base\Fields\Text`. Short aliases like `Text` do not resolve.

## Shared options

Every field class extends `Aura\Base\Fields\Field`. The base field's editor exposes these keys, so they are valid on any field:

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

Two more keys are honored at runtime even though the base editor does not list them:

- `default`. The create form seeds the field with this value (see [Defaults](#defaults)).
- `live`. On supported inputs such as `Text`, this switches the input to `wire:model.live` so the value updates on every keystroke instead of on blur.

`placeholder` and other keys are field-specific. They only take effect on fields whose class or Blade view reads them, as documented below. The `disabled` key is also read by the shared `Field::isDisabled()` helper and the input views.

## Visibility

Form visibility is resolved by pipeline filters, not by the field class:

- `on_forms => false` removes the field from both the create and edit forms.
- `on_create => false` removes it from the create form only.
- `on_edit => false` removes it from the edit form only.
- `on_view => false` removes it from the view page.
- `on_index => false` removes it from the table.

`on_create` and `on_edit` are optional; if you omit them, only `on_forms` applies. Group, Panel, and Tab wrappers propagate their display attributes to the fields nested inside them.

<a id="defaults"></a>

## Defaults

When the create form initializes, it seeds each declared input field:

- If the field array has a `default`, that value is used. For a `Checkbox` with array `options`, a scalar default is wrapped into a single-element array.
- A `Boolean` with no `default` initializes to `false`.
- A `Tags` field with no `default` initializes to an empty array.
- Every other field with no `default` initializes to `null`.

For a handwritten resource definition, set `default` explicitly. Defaults declared by a field class are used by the Resource Editor when it writes a definition; they are not merged into an arbitrary field array at render time.

The `Aura\Base\Traits\DefaultFields` trait also exposes three reusable definitions through `Aura::fields($key)`: `created_at` and `updated_at` use `Aura\\Base\\Fields\\Date` with `enable_time => true`, and `user_id` uses `Aura\\Base\\Fields\\BelongsTo`. The trait does not add these fields to every resource automatically.

<a id="conditional-logic"></a>

## Conditional logic

`conditional_logic` hides a field until its conditions pass. Each condition is an array of `field`, `operator`, and `value`. All conditions must pass (AND).

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

Supported operators are `==`, `!=`, `>`, `>=`, `<`, and `<=`. Any other operator (including a single `=`) evaluates to false and the field stays hidden.

Set `field` to `role` to key the condition off the current user's role instead of another field's value. Role conditions support `==` and `!=`, and super admins always pass:

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

Single-line text input.

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

Options: `default`, `placeholder`, `autocomplete`, `prefix`, `suffix`, `max_length`. `prefix`/`suffix` render as input adornments. `max_length` is stored on the field config but is not enforced by the input element; enforce length with a `max:` validation rule.

Column type: `string`.

### Textarea

Multi-line text input.

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

Options: `default`, `placeholder`, `autocomplete`, `rows` (the Resource Editor default is 3; the input falls back to 4 rows when the key is absent), and `max_length`. `max_length` is stored on the field config but is not enforced by the element. Use a validation rule.

Column type: `text`.

### Number

Numeric input.

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

Options: `default`, `placeholder`, `autocomplete`, `prefix`, and `suffix`. The value is cast to an integer when read back. The class also reads `number_type`, `precision`, and `scale` for exact-query configuration, but those keys are not exposed by its configuration fields and do not change the integer cast. Numeric filter operators are `equals`, `not_equals`, `greater_than`, `less_than`, `greater_than_or_equal`, `less_than_or_equal`, `is_empty`, and `is_not_empty`.

Column type: `integer`.

### Email

Email input.

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

Telephone input (`type="tel"`). No formatting or international handling is applied.

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

Password input. The value is hashed with `Hash::make()` on save unless it is already hashed. An empty value is skipped, so submitting a blank password leaves the stored value unchanged.

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

Options: `based_on` (required, and it must be the slug of the source field), `custom` (render the manual-edit toggle), `disabled` (start locked and derive from `based_on`; the initial editable state is `! disabled`), `default`, and `placeholder`. The Blade view throws an exception when `based_on` is missing. Uniqueness is not added automatically, so add a `unique:` rule to `validation` when needed.

Column type: `string`.

### Date

Date picker.

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

Options: `format` (default `d.m.Y`), `display_format` (default `d.m.Y`), `enable_input` (default true), `maxDate` (the number of days from today to the latest selectable date, validated from 0 to 365 by the Resource Editor), `minDate` (read by the Blade view when supplied), `weekStartsOn` (0 Sunday to 6 Saturday, default 1), and `options.native` (use the browser's native date input). Formats use PHP `date()` tokens. Filter operators are `date_is`, `date_is_not`, `date_before`, `date_after`, `date_on_or_before`, `date_on_or_after`, `date_is_empty`, and `date_is_not_empty`.

Column type: `date`.

### Datetime

Combined date and time picker.

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

Options: `format` (default `d.m.Y H:i`), `display_format` (default `d.m.Y H:i`), `enable_input` (default true), `maxDate`, `minDate`, `minTime`, `maxTime`, `weekStartsOn` (default 1), and `options.native`. Filter operators are `date_is`, `date_is_not`, `date_before`, `date_after`, `date_on_or_before`, `date_on_or_after`, `date_is_empty`, and `date_is_not_empty`. Saved filters using the older bare range names remain supported.

Column type: `timestamp`.

### Time

Time picker.

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

Options: `format` (default `H:i`), `display_format` (default `H:i`), `enable_input` (default true), `enable_seconds` (the Resource Editor default is false, but the current Time template does not pass this key to the date-time picker), `minTime`, `maxTime`, `weekStartsOn` (default 1), and `options.native`.

Column type: `string`.

## Choice fields

### Boolean

Toggle switch. The value is cast to a boolean on read and write. On the table and view page it renders as a check or cross icon.

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

Dropdown. Options are defined as a repeater of key/value pairs.

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

Options: `options` (key/value repeater), `default`, and `allow_multiple`. The current select template renders a single `<select>` and does not add a `multiple` attribute, so `allow_multiple` has no effect at runtime. To compute options at runtime, define a `get{Slug}Options()` method on the resource returning a key => label array:

```php
public function getCategoryOptions()
{
    return ['news' => 'News', 'blog' => 'Blog Post'];
}
```

Filter operators: `is`, `is_not`, `is_empty`, `is_not_empty`. Column type: `string`.

### Radio

Single choice rendered as radio buttons.

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

Multiple choice rendered as checkboxes. Selected values are stored as a JSON array and read back as an array.

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

Options: `options` (key/value repeater) and `default`. The class defines an `options()` helper for callers, but the current Checkbox form template reads the field's `options` array directly, so a `get{Slug}Options()` method does not replace options in that form. Column type: `string` (holds JSON).

### Status

Select whose options carry a color, rendered as a colored badge on the table and view page.

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

Options: `options` (key/value/color repeater), `default`, and `allow_multiple`. Each option's `color` is a set of Tailwind classes; the field's editor offers presets for Blue, Green, Red, Yellow, Indigo, Purple, Pink, Gray, Orange, and Teal, each with dark-mode variants. The class defines an `options()` helper for callers, but the current Status form template reads the field's `options` array directly, so a `get{Slug}Options()` method does not replace options in that form. The current template renders one selected key, so `allow_multiple` is also ignored.

Column type: `string`.

## Media fields

Media fields store attachment IDs and integrate with the [Media Library](/docs/media-manager).

### Image

Image upload with thumbnail preview. Stored as a JSON array of attachment IDs; the table cell shows the first image plus a `+N` badge for the rest.

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

Options: `use_media_manager`, `min_files`, `max_files`, and `allowed_file_types` (a comma-separated extension list). The current Image view always mounts the media uploader, regardless of `use_media_manager`. `min_files` and `allowed_file_types` are stored on the field but are not enforced. `max_files` limits selections in the media picker. The uploader itself accepts at most 20 files per batch, uses `aura.media.max_file_size` with a default of 102400 KiB, and applies its own MIME and blocked-extension rules.

Column type: `string` (holds a JSON array of attachment IDs).

### File

General file upload. Stored as JSON for multiple files.

```php
[
    'name' => 'Attachments',
    'slug' => 'attachments',
    'type' => 'Aura\\Base\\Fields\\File',
    'validation' => 'nullable',
]
```

Options: none beyond the shared options. `File` does not define the media configuration keys that `Image` does, and it uses the same uploader policy described above.

Column type: `string`.

## JavaScript fields

### AdvancedSelect

Relationship selector. Its class default is `api = true`, so it loads selected records first and fetches search results over AJAX in pages of 10. Set `api => false` to preload every record and filter the list in the browser. The related resource class is required.

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

Options: `resource` (required related resource class), `multiple` (the editor default is true), `create` (show the inline create action, default false), `return_type` (`id` or `object` in the field configuration), `polymorphic_relation` (defaults to true in relationship resolution), `api` (defaults to true on the field class), `reverse`, `thumbnail` (field slug used by custom views), and the custom view slugs `view_select`, `view_selected`, `view_view`, and `view_index`. `api` and `reverse` are runtime options and are not exposed by `AdvancedSelect::getFields()`.

Storage: when `polymorphic_relation` is truthy, the field syncs records through the `post_relations` pivot table. Set `polymorphic_relation => false` to store selected IDs as JSON in the resource's meta. The API endpoint returns ten records per page and keeps selected records visible even when they are outside the first page.

Option group: JavaScript fields.

### Color

Color picker.

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

Options: `format` (`hex`, `rgb`, `hsl`, `hsv`, `cmyk`) and `options.native`. Set `'options' => ['native' => true]` to use the browser's native color input. The runtime view reads the nested key. The Resource Editor currently writes the `native` setting as a top-level field key, so that editor toggle has no effect until this source mismatch is fixed.

Column type: `string`.

### Code

Code editor with syntax highlighting. JSON values are pretty-printed when read back.

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

Options: `language` (required. Accepted values are `html`, `css`, `javascript`, `php`, `json`, `yaml`, and `markdown`), `line_numbers`, and `min_height` (pixels, minimum 100).

Column type: `string`.

### Wysiwyg

Rich text editor producing HTML. On display, the field sanitizes string values with Symfony's HTML sanitizer before returning them.

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

Many-to-one selector. Stores the related record's ID and links to it on the table. Its `resource` key is required. By default it preloads every record and filters them client-side. Set `api => true` to search over AJAX instead.

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

One-to-many list of related records, rendered as an embedded table. It is a `relation` type and stores nothing on the resource itself. The `resource` key is required unless you provide a custom `relation` closure.

```php
[
    'name' => 'Comments',
    'slug' => 'comments',
    'type' => 'Aura\\Base\\Fields\\HasMany',
    'resource' => 'App\\Aura\\Resources\\Comment',
    'foreign_key' => 'post_id',
]
```

Options: `resource`, `foreign_key` (used to prefill the create link), `column` (resolve a direct `hasMany` on that column), `reverse` and `reverse_slug` (resolve the inverse through `post_relations`), and `relation` (a closure that receives the query and parent model). Without `column`, `reverse`, or a custom closure, the relation resolves through the `post_relations` pivot using the field slug.

Type: `relation`.

### HasOne

Extends `AdvancedSelect` with class properties `multiple = false`, `api = true`, and `searchable = true`. Its editor view is currently a placeholder that renders only the literal text "Has one" and does not render a working selector on the form. To pick a single related record on a form today, use `AdvancedSelect` with `multiple => false` instead.

```php
[
    'name' => 'Profile',
    'slug' => 'profile',
    'type' => 'Aura\\Base\\Fields\\HasOne',
    'resource' => 'App\\Aura\\Resources\\Profile',
    'create' => true,
]
```

Options: those of `AdvancedSelect`, including the required `resource`, `create`, `return_type`, `polymorphic_relation`, and view slugs. `multiple` is fixed on the field class, although manually supplied field arrays should still set it to `false` for value normalization.

Type: `relation`.

### BelongsToMany

Embedded many-to-many table field. It sets `type = relation` and `group = true` and scopes the target table to a relation on the parent record. The `resource` key is required by the embedded table view as the target model, but it does not choose or sync the parent's relation. Set the field slug to the parent's relation method, or set a string `relation` option with that method name. With a parent but no matching relation method it returns no rows. With no parent it leaves the target query unchanged.

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

Tagging field backed by a polymorphic relationship. The `resource` key is required. Selected tags are stored in the `post_relations` pivot, ordered by its `order` column, and rendered as badges.

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

Options: `resource` (required tag resource class), `create` (show the UI for adding tags and allow label creation when true; the Resource Editor writes false by default), and `max_tags` (a client-side Tagify limit). Existing IDs are checked through the related resource's scoped query. Text labels are created only when creation is allowed and the current user can create the target resource. Set `create => false` to reject new labels. Filter operators are `contains` and `does_not_contain`. This is a taxonomy field.

Type: `input` (relation-backed via `post_relations`).

## Structure fields

Structure fields group other fields. They store no value of their own. Their child fields are what get stored.

> Structure fields do not take a `fields` key in the resource definition. Declare children as following siblings in the same flat `getFields()` array. The field pipeline reads the array in order and builds the tree from declaration order. A nested `fields` array is skipped by the pipeline, so its children do not receive runtime field instances.

Use these rules to close or change the current nesting:

- `'exclude_level' => N` moves the field up `N` levels. Use it to put a field after a Repeater or Group.
- A global Tab with `'global' => true` resets the parent stack and groups global tabs under a `Tabs` wrapper.
- `same_level_grouping => false` disables the same-level behavior of a `Panel` or `Tab` for that definition.

If a Repeater or Group is the last field in its Tab or Panel, the end of the array closes it.

### Group

Groups child fields under one visual block on the same form. Declare the child fields as the following siblings.

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

Repeatable set of child fields. Stored as JSON. The child fields are the following siblings, and their runtime slugs are prefixed with the repeater slug and row index.

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

Groups child fields into a panel. Panels use same-level grouping by default. The panel's fields are the following siblings.

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

A single tab. Its fields are the following siblings. Mark the tab `'global' => true` (the common case) so consecutive tabs sit side by side; the pipeline wraps them in a `Tabs` container automatically.

```php
['name' => 'Content', 'slug' => 'tab-content', 'type' => 'Aura\\Base\\Fields\\Tab', 'global' => true],
['name' => 'Body', 'slug' => 'body', 'type' => 'Aura\\Base\\Fields\\Wysiwyg'],

['name' => 'Settings', 'slug' => 'tab-settings', 'type' => 'Aura\\Base\\Fields\\Tab', 'global' => true],
['name' => 'Published', 'slug' => 'published', 'type' => 'Aura\\Base\\Fields\\Boolean'],
```

Type: `tab`.

### Tabs

Container that groups `Tab` fields into a tabbed interface. You rarely declare `Tabs` yourself. Declaring consecutive `Tab` fields is enough because the pipeline wraps them.

Type: `tabs`.

## Layout fields

Layout fields render presentation only and store no value.

### Heading

Section heading. It still needs a slug because all field definitions pass through the same definition validator.

```php
[
    'name' => 'User Settings',
    'slug' => 'user-settings-heading',
    'type' => 'Aura\\Base\\Fields\\Heading',
]
```

### HorizontalLine

Horizontal rule. It also needs a slug.

```php
[
    'slug' => 'content-divider',
    'type' => 'Aura\\Base\\Fields\\HorizontalLine',
]
```

### View

Renders a Blade view you name.

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

Embeds a Livewire component.

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

The primary key. Not shown on forms (`on_forms = false`).

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

Renders an HTML `<embed>` for the current model's own `url` and `mime_type` attributes. It ignores the field's own slug and stored value entirely, so it only produces output on resources that expose those attributes (such as the built-in Attachment resource). It adds no field-specific options (no provider or URL configuration).

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

Permission matrix used by the [Roles & Permissions](/docs/roles-permissions) system. Stored as JSON.

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

Role assignment field, team-aware when [teams](/docs/teams) are enabled. Extends `AdvancedSelect` and syncs the user's roles on save. Primarily used on the built-in User resource.

```php
[
    'name' => 'Roles',
    'slug' => 'roles',
    'type' => 'Aura\\Base\\Fields\\Roles',
    'resource' => 'Aura\\Base\\Resources\\Role',
    'multiple' => false,
]
```

Type: `input`. The class inherits the base `input` type, reports itself as a relation via `isRelation()`, and syncs the user's `roles` relationship on save. The built-in User resource supplies the related Role resource and uses `multiple => false`.

### GlobalAdmin

Boolean field used by the built-in User resource for the instance-level `global_admin` flag. It inherits the Boolean field's input and display behavior. Its `saved()` hook only changes the real users-table column when the acting user is already allowed by the `AuraGlobalAdmin` gate. Other actors are ignored without changing the stored flag.

```php
[
    'name' => 'Global Admin',
    'slug' => 'global_admin',
    'type' => 'Aura\\Base\\Fields\\GlobalAdmin',
    'default' => false,
]
```

Option group: Choice Fields. Column type: `string` in the inherited field metadata. The built-in User resource writes this value to its `global_admin` column through the guarded hook.

### UserTeams

User-to-team field used by the built-in User resource when teams are enabled. It extends `BelongsToMany` for parent-aware table scoping and uses a dedicated Livewire membership editor on the User view page.

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

Type: `relation`. The built-in definition hides this field when teams are disabled and only shows it on the User view page.

## Field reference

Column type applies only to [custom tables](/docs/custom-tables); on the shared meta store every scalar value lives in the meta `value` column. "Option group" is the heading a field appears under in the resource editor's field picker. A relation or layout field has no generated value column.

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

When a resource is saved, Aura walks each field value and calls the field class's hooks. Only `display`, `get`, and `value` are defined on the base `Field` class; `set`, `saving`, `shouldSkip`, and `saved` are optional hooks that the save pipeline invokes only when your field class defines them (they are discovered with `method_exists`, so you implement just the ones you need):

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

This creates `app/Aura/Fields/Rating.php` extending `Aura\Base\Fields\Field`, plus two Blade files under `resources/views/components/fields/`: the editor (`rating.blade.php`) and the display view (`rating-view.blade.php`). Fields in `app/Aura/Fields` are discovered and registered automatically. No manual registration is needed for that path.

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

Add configuration options by returning field arrays from `getFields()` (they show up in the resource editor), and control storage and rendering with the lifecycle hooks above. For example, a rating field that casts to an integer and renders stars:

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
