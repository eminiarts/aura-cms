# Resource editor

The Resource Editor is a local development tool for changing an App resource from the Aura admin UI. It edits the PHP class on disk. Adding, editing, reordering, duplicating, or deleting a field rewrites the array returned by `getFields()`. The top Save action also writes the resource's editable navigation properties.

Review the working-tree diff after every change. The Delete action removes the resource class file. Keep the resource under version control before opening the editor.

![Resource editor overview](/images/docs/resource-editor/resource-editor-overview.png)

## Access requirements

The editor is available only when all of these conditions hold:

- The application environment is `local` or `testing`.
- `aura.features.resource_editor` is true. The default is true when `app.env` is `local` and false otherwise.
- The authenticated user is Aura's `User` model and `isSuperAdmin()` returns true.
- The resource class name starts with `App`.

The editor has no separate policy check. The route middleware and the Livewire component enforce the environment, feature, and super-admin checks. A disabled feature or a non-local environment returns `404`. A non-super-admin user returns `403`.

The resource check is based on the class name, not the file path. Built-in Aura resources and resources provided by packages are treated as vendor resources and cannot be edited. An application class under the `App` prefix is eligible even when its file is discovered through a customised path.

The route is:

```text
/{aura.path}/resources/{slug}/editor
```

`aura.path` defaults to `admin`, so the usual URL is `/admin/resources/{slug}/editor`. The route name is `aura.resource.editor`. The default `aura-admin` middleware group is `['web', 'auth']`; applications can customise that group in `config/aura-settings.php`.

Creating a new resource uses the Create Resource action. That Livewire component also requires a super admin and rejects the `production` environment. The resulting editor page still requires `local` or `testing`, so use the create flow in a local development environment.

## Prepare the resource

The editor can rewrite only a literal field array. Keep `getFields()` in this shape:

```php
<?php

namespace App\Aura\Resources;

use Aura\Base\Resource;

class Product extends Resource
{
    public static string $type = 'Product';

    public static ?string $slug = 'product';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|max:255',
            ],
        ];
    }
}
```

Do not build this array with a helper, a conditional branch, or a closure if you want to edit it in the UI. `SaveFields::saveFields()` looks for a `return [ ... ];` statement inside `getFields()`. If it cannot find the method or return statement, it shows a notification and does not rewrite the file. It still dispatches the `SaveFields` event, so inspect the file and any schema output after a failed save.

The editor also refuses to mount when any field definition contains a closure. Maintain those resources in PHP instead.

## Edit fields

Open the editor from the resource index or visit the route directly. The field controls work as follows:

| Action | Result |
| --- | --- |
| Add field | Opens the field slide-over after the selected field. New fields start with the type supplied by the add control, or `Aura\\Base\\Fields\\Text` when no type is supplied. |
| Edit field | Opens the same slide-over for the selected field. |
| Duplicate | Copies the field after the original, adds a random four-character suffix to its slug, and appends ` Copy` to its name. |
| Reorder | Drag the handle. The new order is written when the drop completes. |
| Delete | Removes the field from the PHP array immediately. |

Field actions save the field array as soon as the action runs. The top Save button saves the current field array and the editable resource properties. It does not defer field changes until the top button.

New fields receive these initial values before the field-specific editor adds its own options:

| Key | Initial value |
| --- | --- |
| `type` | `Aura\\Base\\Fields\\Text` when no type is supplied |
| `slug` | An empty string until the editor derives or validates one |
| `name` | An empty string |
| `validation` | An empty string |
| `on_index` | `true` |
| `on_forms` | `true` |
| `on_view` | `true` |
| `searchable` | `false` |
| `conditional_logic` | An empty string |

The field editor applies the selected field class's own validation rules as well. Slugs must start with a letter or number, contain only letters, numbers, `_`, or `-`, and cannot contain only numbers. Keep slugs unique within the resource. `id` and `type` are real resource columns and should not be used as field slugs. The current field editor does not reliably reject those reserved names.

Deleting a definition does not perform a data migration. In the default shared `posts` and `meta` storage, old values remain until the application removes them, but the field no longer reads them. A custom-table schema listener can generate or apply a column drop. Review the generated migration before applying it.

## Conditional logic

Conditional logic is a flat list. Aura evaluates every rule and hides the field when the first rule fails, so multiple rules are combined with AND. The evaluator does not implement OR groups even though the component contains methods for editing grouped data.

Use a field slug, one of the supported operators, and a comparison value:

```php
'conditional_logic' => [
    [
        'field' => 'employment_status',
        'operator' => '==',
        'value' => 'employed',
    ],
],
```

The supported operators are `==`, `!=`, `>`, `>=`, `<`, and `<=`. Set `field` to `role` to compare the current user's role slug. Role conditions support only `==` and `!=`, and super admins pass role conditions automatically:

```php
'conditional_logic' => [
    [
        'field' => 'role',
        'operator' => '==',
        'value' => 'editor',
    ],
],
```

## Templates and presets

An empty resource offers three templates:

- `Plain` adds a text field.
- `Tabs` adds global tabs and text fields.
- `TabsWithPanels` adds global tabs, panels, and text fields.

When a global tab has no fields, it offers `PanelWithSidebar` and `Plain` as presets. These presets are inserted after that tab. The preset insertion path adds a random suffix to each slug. The empty-resource template path keeps the template slugs unchanged, so review for collisions when using it on a resource that already has definitions.

`PanelWithTabs` exists in `src/Templates/` but the Resource Editor does not currently show it.

## Resource properties

The editor displays the resource type and slug as read-only values. The top Save action can write the following properties when the class uses the conventional declarations or `getIcon()` method that `saveProps()` expects:

| Property | Effect |
| --- | --- |
| `icon` | Stores the icon returned by `getIcon()`. Aura renders the value as HTML, commonly an inline SVG. There is no icon-class resolver. |
| `group` | Sets the navigation group. |
| `dropdown` | Sets the navigation dropdown value. |
| `sort` | Sets the integer navigation order. |

The type and slug remain class identity values. Change them in the resource class when the editor's read-only controls are not sufficient, then review every route and registration that depends on the slug.

## Schema listeners

Changing a field definition normally changes only the PHP class. The `custom_tables_for_resources` feature controls optional listeners for the `SaveFields` event emitted by the editor:

```php
// config/aura.php
'features' => [
    'custom_tables_for_resources' => false,
],
```

The listener returns without changing a schema when the resource has the base `$customTable = false` setting. Custom-table storage and the `$usesMeta` flag are separate decisions. Read [Custom tables](/docs/custom-tables) before enabling a listener.

| Value | Listener behavior |
| --- | --- |
| `false` | No schema listener runs. The editor rewrites the PHP file only. |
| `true` or `'single'` | `ModifyDatabaseMigration` rewrites the `create_{table}_table` migration and invokes `aura:schema-update` with column dropping enabled. It compares column names and does not change the type of an existing column. |
| `'multiple'` | `CreateDatabaseMigration` writes an `update_{table}_table_TIMESTAMP` migration for changed fields and attempts to run `migrate`. The generated migration can contain additions, renames, type changes, and drops. |

The single mode treats the regenerated create migration as the full desired schema. The multiple mode records each field change in a new update migration. Neither mode is a substitute for a reviewed application migration when a change needs data conversion, a production rollout, or a constraint that the field class does not describe.

The Generate Migration action is separate from these listeners. It changes the resource class to set `public static $customTable = true;` and a protected `$table` based on the resource's plural name, then calls `aura:create-resource-migration`. Review the generated file and run the migration yourself. The action does not copy existing posts or meta rows into the custom table.

### Single-mode parser limits

`aura:schema-update` accepts simple column declarations with single- or double-quoted names. Hyphenated field names are supported. It stops before changing the table when declarations contain unsupported arguments or cannot be parsed safely. It does not alter existing column types. The listener includes `team_id` only when teams are enabled.

A failed schema sync reports an error and restores the resource definition and previous migration file. If the listener created a new migration, it removes that file. The editor does not report a successful save after that failure.

The editor rewrites a `getFields(): array` method that returns an array literal. If it cannot rewrite the method, it stops before dispatching schema changes. Use code for dynamic field definitions.

Review the generated migration and command result. Use an application migration for constraints, conversions, or other changes outside this parser's supported declarations.

## Practical workflow

1. Commit or otherwise save the resource class before opening the editor.
2. Enable `aura.features.resource_editor` in a local environment if it is disabled.
3. Open `/admin/resources/{slug}/editor`, or use the configured `aura.path`.
4. Add or edit fields, then save each field. Use the top Save button for icon, group, dropdown, or sort changes.
5. Review the PHP diff immediately. Check slugs, field classes, nested panel or tab structure, and conditional rules.
6. If the resource uses a custom table and a schema listener is enabled, inspect the migration and the command output before keeping the database change.
7. Run the focused resource-editor and storage tests for the application. The editor does not convert existing data when you change a field definition.

## Related guides

- [Fields](/docs/fields) lists field classes and their options.
- [Creating resources](/docs/creating-resources) covers the `aura:resource` command and resource layout.
- [Custom tables](/docs/custom-tables) explains storage flags, migrations, and posts-to-custom transfer.
- [Meta fields](/docs/meta-fields) explains the shared key/value storage.
