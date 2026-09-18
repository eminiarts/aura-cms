# Resource editor

The resource editor lets you change an application resource through the Aura admin interface during local development. Each field change rewrites the field definitions in the resource's PHP class. The Save button at the top also writes its editable navigation properties.

Keep the resource under version control before opening the editor, and review the diff after every change. Deleting a resource removes its PHP class file.

![Resource editor overview](/images/docs/resource-editor/resource-editor-overview.png)

## Access requirements

The editor is available only when all of these conditions hold:

- The application environment is `local` or `testing`.
- The resource editor feature is enabled through `aura.features.resource_editor`. It is enabled by default in the local environment and disabled elsewhere.
- The signed-in user uses Aura's user model and is a super admin, as determined by `isSuperAdmin()`.
- The resource class name starts with `App`.

The route middleware and Livewire component enforce these access requirements. There is no separate policy check. Disabling the feature or using an environment other than local or testing returns a 404 response. Users who are not super admins receive a 403 response.

Aura checks the resource's class name to decide whether it can be edited. Built-in resources and those provided by packages cannot be edited. Application classes with the `App` prefix are eligible even when Aura discovers their files through a customised path.

The route is:

```text
/{aura.path}/resources/{slug}/editor
```

The default admin path is `admin`, so the usual URL is `/admin/resources/{slug}/editor`. You can change the path through `aura.path` or link to the named route, `aura.resource.editor`.

The route uses the `aura-admin` middleware group, which includes the web and authentication middleware by default. Customise this group in `config/aura-settings.php`.

Use the Create Resource action to add a resource. This action also requires a super admin and rejects the production environment. The resulting editor page is only available in local or testing environments, so create resources during local development.

<a id="prepare-the-resource"></a>

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

Return the field array directly from `getFields()` if you want to use the editor. Arrays built with helpers, conditional branches, or closures cannot be rewritten. If the editor cannot find the method or a literal array return statement, it raises an error before writing the file or dispatching schema changes.

The editor also refuses to open if any field definition contains a closure. Maintain those resources in PHP.

## Edit fields

Open the editor from the resource index or visit the route directly. The field controls work as follows:

| Action | Result |
| --- | --- |
| Add field | Opens the field slide-over to insert a field after the selected one. It uses the type supplied by the add control, or a text field if none is supplied. |
| Edit field | Opens the same slide-over for the selected field. |
| Duplicate | Copies the field after the original, adds a random four-character suffix to its slug, and appends ` Copy` to its name. |
| Reorder | Drag the handle. The new order is written when the drop completes. |
| Delete | Removes the field from the PHP array immediately. |

Field actions write their changes immediately. You do not need to click the Save button at the top to keep them. That button saves both the current field definitions and the editable resource properties.

New fields start with the following values. Each field type may add its own options:

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

Each field type applies its own validation rules. A slug must start with a letter or number and may contain only letters, numbers, underscores, or hyphens. It cannot consist entirely of numbers and must be unique within the resource.

Do not use `id` or `type` as field slugs. They are existing resource columns, and the editor does not reliably reject these reserved names.

Deleting a field definition does not migrate its data. With the default shared storage, old values remain in the posts and meta tables until the application removes them. The deleted field no longer reads those values.

If a custom-table schema listener is enabled, deleting a field can generate or apply a column drop. Review the generated migration before applying it.

## Conditional logic

A field is visible only when all its conditional rules pass. Aura checks the rules in order and hides the field as soon as one fails. Rules therefore use AND logic. OR groups are not supported, even though the component has methods for editing grouped data.

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

- Plain adds a text field.
- Tabs adds global tabs and text fields.
- TabsWithPanels adds global tabs, panels, and text fields.

An empty global tab offers the PanelWithSidebar and Plain presets. Selecting a preset inserts its fields after the tab and adds a random suffix to each slug.

Templates for an empty resource keep their original slugs. Check for duplicate slugs if you use one on a resource that already has field definitions.

A PanelWithTabs template exists in `src/Templates/`, but the resource editor does not currently offer it.

## Resource properties

The resource type and slug are read-only in the editor. The Save button at the top can update the properties below, provided the class uses the conventional property declarations and `getIcon()` method expected by the editor:

| Property | Effect |
| --- | --- |
| `icon` | Stores the icon returned by `getIcon()`. Aura renders the value as HTML, commonly an inline SVG. There is no icon-class resolver. |
| `group` | Sets the navigation group. |
| `dropdown` | Sets the navigation dropdown value. |
| `sort` | Sets the integer navigation order. |

To change the resource's type or slug, edit its PHP class directly. Review every route and registration that depends on the slug.

## Schema listeners

Field changes normally affect only the PHP class. You can also have the editor update a custom table's schema when it saves fields. The `custom_tables_for_resources` feature enables listeners for these changes:

```php
// config/aura.php
'features' => [
    'custom_tables_for_resources' => false,
],
```

These listeners leave the schema unchanged unless the resource uses a custom table. The default is `$customTable = false`. Choosing a custom table is separate from deciding whether to store field values as meta data through `$usesMeta`. Read [Custom tables](/docs/custom-tables) before enabling a listener.

| Value | Listener behavior |
| --- | --- |
| `false` | No schema listener runs. The editor rewrites the PHP file only. |
| `true` or `'single'` | `ModifyDatabaseMigration` rewrites the `create_{table}_table` migration and invokes `aura:schema-update` with column dropping enabled. It compares column names and does not change the type of an existing column. |
| `'multiple'` | `CreateDatabaseMigration` writes an `update_{table}_table_TIMESTAMP` migration for changed fields and attempts to run `migrate`. The generated migration can contain additions, renames, type changes, and drops. |

The single mode treats the regenerated create migration as the full desired schema. The multiple mode records each field change in a new update migration. Neither mode is a substitute for a reviewed application migration when a change needs data conversion, a production rollout, or a constraint that the field class does not describe.

The Generate Migration action works separately from these listeners. It enables custom-table storage in the resource class and sets a protected `$table` property based on the resource's plural name. It then runs `aura:create-resource-migration`.

Review the generated file and run the migration yourself. This action does not copy existing posts or meta rows into the custom table.

### Single-mode parser limits

`aura:schema-update` accepts simple column declarations with single- or double-quoted names. Hyphenated field names are supported. It stops before changing the table when declarations contain unsupported arguments or cannot be parsed safely. It does not alter existing column types. The listener includes `team_id` only when teams are enabled.

A failed schema sync reports an error and restores the resource definition and previous migration file. If the listener created a new migration, it removes that file. The editor does not report a successful save after that failure.

As described in [Prepare the resource](#prepare-the-resource), the editor requires a literal field array. If it cannot rewrite that array, it stops before dispatching schema changes. Maintain dynamic field definitions in PHP.

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
