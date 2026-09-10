# Troubleshooting

This page covers failures caused by Aura's installer, resources, fields, teams, permissions, assets, and configuration. For Composer, PHP extensions, database drivers, and HTTP limits, use the Laravel documentation.

This guide describes the current main branch unless a section specifies beta4. The public Composer release, v1.0.0-beta.4, has different installer and cache behavior. Check your installed version with `composer show eminiarts/aura-cms`. If it reports beta4, follow the release-specific steps in [Installation](/docs/installation).

<a id="common-issues"></a>
<a id="common-issues-and-solutions"></a>

## Quick triage

Check the installed package and application runtime first:

```bash
composer show eminiarts/aura-cms
php -v
php artisan about
```

The current main branch requires PHP 8.4, Laravel 13, Livewire 4, and the GD extension used by Intervention Image3. The public beta4 release supports older versions of these dependencies. Check [Installation](/docs/installation) before applying an example from main to beta4.

Then check the route, configuration, and migration state that match the failure:

```bash
php artisan route:list --path=admin
php artisan migrate:status
php artisan config:clear
```

`config:clear` removes Laravel's compiled configuration file and does not alter database data.

<a id="installation-problems"></a>

## Installation problems

### The installer reports success after a child command failed

On the current main branch, the installer stops if it cannot update the user model, write configuration, run migrations, create the user, or link storage. These steps run through `aura:extend-user-model`, `aura:install-config`, `migrate`, `aura:user`, and `storage:link`. Find the first failed command in the output and fix that problem before rerunning the installer. Then check the admin route with `php artisan route:list --path=admin`.

Beta4 can print its final installer message after a child command failed. Check each command's output and confirm that `/login` loads before treating beta4 installation as complete. The beta4 teams-off sequence is documented in [Installation](/docs/installation#without-teams).

### Aura-owned tables already exist

The Aura migration stops when package-owned tables such as `posts`, `meta`, `roles`, or `permissions` already exist. Run `php artisan migrate:status` to inspect migration state and compare the existing schema with the published migration. Use a database with no conflicting Aura-owned tables for a fresh installation, or plan a data-preserving migration for an existing application. Do not drop tables or reset the database as an installation shortcut.

### The User model was not updated

`aura:extend-user-model` edits a standard `app/Models/User.php` that extends Laravel's `Authenticatable` class. If you declined the prompt, run:

```bash
php artisan aura:extend-user-model
```

Review a custom user model manually. It should extend `Aura\Base\Resources\User` or preserve the contracts and traits that Aura's authentication flow requires. The command does not rewrite an arbitrary custom model.

### Teams-off installation behaves like teams-on

Choose whether to enable teams before creating the schema. On the current main branch, pass that choice to the installer for a non-interactive installation:

```bash
php artisan aura:install \
    --no-interaction \
    --teams=false \
    --registration=false \
    --admin-name="Aura Admin" \
    --admin-email="admin@example.com" \
    --admin-password="replace-this-value"
```

For beta4, follow the separate [teams-off sequence](/docs/installation#without-teams), where `aura:install-config` runs before `migrate` in a new PHP process. Changing `AURA_TEAMS` after the schema exists needs a planned schema migration.

<a id="configuration-and-cache"></a>

## Configuration and cache

### Configuration changes have no effect

When `.env` or `config/aura.php` changes do not affect the request, clear the compiled configuration before testing again:

```bash
php artisan config:clear
php artisan cache:clear
```

On the current main branch, `aura:install-config` clears both the configuration and application caches after writing your settings. Beta4 can leave a cached configuration file and writes literal values into the published config. Review the beta4 notes in [Installation](/docs/installation).

The current layout component alias is `aura::layout.app`. If an older published config still contains `aura::layouts.app`, update the `views.layout` value and clear the config cache. The shipped component is `resources/views/components/layout/app.blade.php`.

### A new resource or permission is missing from the sidebar

On the current main branch, adding a registered resource refreshes the relevant sidebar entry because the navigation cache key includes the resource list. A permission change can still leave the sidebar showing an older result. If reloading the page does not show the newly granted access, run `php artisan cache:clear`. Beta4 has a stale navigation cache after adding a resource. Use the beta4 instructions in [Installation](/docs/installation).

<a id="assets-and-media"></a>
<a id="media--file-upload-problems"></a>

## Assets and media

### The admin page is unstyled or JavaScript does not run

If the admin panel has no styling or its JavaScript does not run, republish the package assets. Aura checks that the asset manifest at `public/vendor/aura/manifest.json` and all files it references are present:

```bash
php artisan aura:publish
```

`aura:publish` has no `--force` option. It stages a complete asset tree, verifies the manifest references, and replaces the previous tree only after verification. You do not need to run `npm install` or rebuild the host application's Vite bundle to publish Aura's compiled assets. Run it after every package update.

If the command fails, inspect its error and the package installation. Confirm that `public/vendor/aura/manifest.json` exists after a successful publish.

### Uploaded files or images return 404

Aura stores media on the disk configured by `aura.media.disk`, which defaults to the `public` disk under the `media` path. The public storage link must exist:

```bash
php artisan storage:link
```

The current main branch creates the link during installation. Beta4 does not, so run this command after a beta4 installation. See the beta4 notes in [Installation](/docs/installation).

Check GD before investigating the upload record:

```bash
php -m | grep -i '^gd$'
```

Aura uses Intervention Image3 with the GD driver. Imagick is not required by the core thumbnail path.

### A thumbnail request returns `Requested thumbnail dimensions are not allowed.`

When `aura.media.restrict_to_dimensions` is `true`, the requested width and height must match one of the entries in `aura.media.dimensions`. The default named sizes include `xs` at 200 pixels, `sm` at 600, `md` at 1200, `lg` at 2000, and a 600 by 600 `thumbnail` size.

If the source file is missing, the thumbnail generator reports `Original image not found: {path}`. Check the configured disk, the `media` path, and the stored attachment URL before changing thumbnail settings.

Thumbnails are intentionally skipped in the `testing` environment. Tests should not expect generated thumbnail files.

<a id="resource-and-field-errors"></a>
<a id="resource--field-errors"></a>

## Resources and field storage

### A resource does not appear in the sidebar

Aura looks for application resources in `app/Aura/Resources` by default, using the `App\Aura\Resources` namespace. You can change the discovery path through `aura-settings.paths.resources.path`. Each resource must extend `Aura\Base\Resource` to be registered.

A resource goes missing when:

- The file is not under `app/Aura/Resources`.
- The class does not extend the base resource class.
- The class name or namespace does not match the file path, causing PSR-4 autoloading to fail silently.

```php
namespace App\Aura\Resources;

use Aura\Base\Resource;

class Project extends Resource
{
    public static string $type = 'Project';

    public static ?string $slug = 'project';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => 'Aura\Base\Fields\Text',
                'validation' => 'required|max:255',
            ],
        ];
    }
}
```

Aura resources are Eloquent models themselves. There is no separate `$model` property pointing at an `App\Models` class.

After adding a resource, generate its permissions so roles can be granted access:

```bash
php artisan aura:create-resource-permissions
```

On the current main branch, adding a registered resource refreshes the relevant sidebar entry because the navigation cache key includes the resource list. A permission change can still leave the sidebar showing an older result. If reloading the page does not show the newly granted access, run `php artisan cache:clear`.

### Resource Editor returns 404 or 403

The Resource Editor at `/admin/resources/{slug}/editor` refuses to open when the feature is disabled or the resource cannot be rewritten safely. The response tells you which check failed:

| Status | Message | Cause |
|--------|---------|-------|
| 404 | None | The `aura.features.resource_editor` setting is `false`. |
| 403 | `Only App resources can be edited.` | The resource class is outside the `App\` namespace, so Aura treats it as a vendor resource. |
| 403 | `Your fields have closures. You can not use the Resource Builder with Closures.` | The field definitions contain closures, such as dynamic options or validation callbacks. |

The feature flag defaults to on only in the local environment:

```php
// config/aura.php
'features' => [
    'resource_editor' => config('app.env') == 'local' ? true : false,
],
```

Set this option directly in the configuration file. There is no `AURA_RESOURCE_EDITOR` environment variable. The middleware also permits the testing environment.

The closure guard is by design. The editor rewrites your `getFields()` array to a file and cannot serialize closures. If you need dynamic field behaviour, edit the resource by hand.

<a id="common-gotchas"></a>
<a id="database-issues"></a>

## Teams and missing records

### Records exist in the database but queries return empty

A resource query may exclude records based on their type, team, or owner:

- The type scope limits records in the shared posts table to the requested resource type.
- The team scope limits team-owned records to the signed-in user's current team when teams are enabled.
- The user scope limits records to the current user when their role has the resource's `scope` permission and they are not a super admin.

These scopes are implemented by `TypeScope`, `TeamScope`, and `ScopedScope`, respectively.

Ordinary signed-in users get no team-owned records when they have no current team. Guests skip the team scope so login, registration, and password reset can find the models they need. The team resource itself is not team-scoped.

Within a team, role queries include that team's roles and shared global roles. When both have the same slug, the team role takes precedence.

First inspect the runtime context:

```php
$user = auth()->user();

config('aura.teams');
$user?->current_team_id;
$user?->belongsToTeam($team);
```

Use `withoutGlobalScope(TeamScope::class)` or `withoutGlobalScopes()` only in controlled administrative or diagnostic code where the wider result set is intended. Do not remove a scope from a normal web query to work around a missing permission:

```php
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Models\Scopes\TypeScope;

$all = Project::withoutGlobalScope(TeamScope::class)
    ->withoutGlobalScope(TypeScope::class)
    ->get();
```

The base resource already registers these scopes, so do not add them again in your resource's `booted()` method. The team scope does nothing when `aura.teams` is `false`.

### Switching teams does not change the result set

A user can switch only to a team they belong to, unless they are a global admin visiting that team. The `User::switchTeam()` method enforces this restriction. On the current main branch, saving a change to `current_team_id` through the user model clears the cached team ID at `user_{id}_current_team_id`.

If an integration writes the column through `DB::table()` instead of the User model, clear the same key through the public helper:

```php
\Aura\Base\Resources\User::clearCurrentTeamCache($userId);
```

Changing the current team does not create a membership. A global admin visiting a team remains a visitor unless you add a membership separately.

### A team role appears twice or the wrong role is applied

With teams enabled, a global role has no team ID. A team role with the same slug takes precedence within its team. Use `Role::resolveForTeam($slug, $teamId)` to get the team role when one exists, or the global role otherwise. Role lists and pickers follow the same rule. Saving a role assignment also rejects a global role ID if a team role with the same slug takes precedence in the target team.

When investigating a role mismatch, compare the role slug and the target team's ID. Memberships identify roles by slug. Creating or deleting a team role with that slug changes which role Aura resolves, so do not rewrite membership pivot rows to point at the overriding role.

### Teams-off mode has missing tables or resources

When you set `aura.teams` to `false` before migration, Aura omits the teams table and does not register the team or team invitation resources. It also uses the schema for roles and memberships without teams and disables the team scope. Follow the version-specific [installation steps](/docs/installation), especially the [beta4 teams-off sequence](/docs/installation#without-teams), instead of changing the setting on an existing schema without a migration plan.

## Field storage

### A field value saves as `null`

First check where the resource stores its fields. Choosing a custom table does not automatically disable meta storage. The `$customTable` and `$usesMeta` flags control these choices independently:

```php
$resource = app(\App\Aura\Resources\Project::class);

$resource->getTable();
$resource->usesCustomTable();
$resource->usesMeta();
$resource->isMetaField('title');
$resource->isTableField('title');
```

The four storage combinations are:

| `$customTable` | `$usesMeta` | Field storage |
| --- | --- | --- |
| `false` | `true` | Base fillable fields use `posts`; other input fields use the shared `meta` table. |
| `false` | `false` | Only base fillable fields have table storage. Other input fields are not written by Aura. |
| `true` | `true` | Base fillable fields use the custom table; other input fields use the shared `meta` table. |
| `true` | `false` | Input field slugs must be columns on the custom table. |

The default generated resource uses the shared `posts` and `meta` tables. `php artisan aura:resource Project --custom` generates a custom table resource with `$usesMeta = false`; create and run the matching migration before saving fields.

If a field is meta-backed, it is not a column on the resource table. Read it through the resource or its `meta` relation. If a field is column-backed, confirm that the migration created the column and that the model's fillable configuration permits the write.

For a custom-table resource, the declaration looks like this:

```php
namespace App\Aura\Resources;

use Aura\Base\Resource;

class Project extends Resource
{
    public static string $type = 'Project';

    public static ?string $slug = 'project';

    public static $customTable = true;

    protected $table = 'projects';

    public static bool $usesMeta = false;

    public static function getFields(): array
    {
        return [/* ... */];
    }
}
```

When converting existing posts to a custom table, review the generated migration first. The transfer command is explicit and does not preserve IDs or make the operation idempotent:

```bash
php artisan aura:transfer-from-posts-to-custom-table "App\Aura\Resources\Project"
```

Run the transfer in a new PHP process after the target migration exists. Do not accept a bulk transfer without checking the target schema and a backup or recovery plan.

<a id="authentication--permissions"></a>
<a id="permissions-and-403s"></a>

## Authentication, permissions, and 403 responses

### A valid password leads to the two-factor challenge

This is expected for a user with a confirmed Fortify two-factor secret. Aura stores the pending user ID in the session, leaves the request unauthenticated, and redirects to `/two-factor-challenge`. Submit the authenticator code or a recovery code on the challenge page. A successful challenge redirects to the intended URL or `config('aura.auth.redirect')`.

By default, users can submit a two-factor code five times per minute. Your application can replace Aura's limiter through `fortify.limiters.two-factor`. A 429 response after repeated invalid codes means the limiter is working. Start a new challenge after the limiter window instead of disabling it.

Setting `aura.auth.2fa` to `false` disables Aura's management routes for enabling and viewing two-factor data. An already confirmed user still receives the pre-authentication challenge.

To inspect the routes without signing in:

```bash
php artisan route:list --path=two-factor-challenge
```

### A resource action returns 403

```
403 | This action is unauthorized.
```

Aura stores permissions as a JSON map on each role. It does not use Spatie Laravel Permission. The `hasPermissionTo($ability, $resource)` method grants access when any of the user's roles has the matching `"{$ability}-{$slug}"` key set to `true`. A role with the `super_admin` flag also passes this check.

A resource can still disable an action for super admins. The create, update, view, and view-any policies check the resource's `$createEnabled`, `$editEnabled`, `$viewEnabled`, and `$indexViewEnabled` flags before granting super admin access.

Use these methods on Aura's user model to inspect access:

| Method | Purpose |
|--------|---------|
| `isSuperAdmin()` | `true` if any role has `super_admin` |
| `hasRole('admin')` | Role membership by slug (compares against each role's `slug`, not its name) |
| `hasPermissionTo('view', $resource)` | Ability check against a resource |
| `roles()` | The roles relation |

There is no `getAllPermissions()` or `assignRole()`. Inspect a user's access with the methods above:

```php
$user->isSuperAdmin();
$user->hasPermissionTo('viewAny', \App\Aura\Resources\Project::class);
```

When a new resource returns 403 for everyone, its permissions probably do not exist yet. Generate the missing ones:

```bash
php artisan aura:create-resource-permissions
```

Then grant the relevant abilities to the appropriate role. The command is named `aura:create-resource-permissions`; there is no `aura:permissions` command.

In teams-on mode, pass a numeric target team when the rows belong to a team other than the authenticated user's current team:

```bash
php artisan aura:create-resource-permissions --team=12
```

The command does not generate permissions for the team resource. A super admin's access comes from a role within one team. A global admin has instance-wide access, determined by the `AuraGlobalAdmin` gate.

With teams enabled, a global role has `team_id = null`. A team role with the same slug takes precedence within its team. Role lists and pickers show the resolved role, and saving an assignment rejects the hidden global role ID.

### A role grant works in one team but not another

Built-in team policies evaluate role abilities against the target team. If a custom policy calls `$user->isSuperAdmin()` or `$user->hasPermissionTo()` directly, it uses the User instance's current-team context. Set and validate the target-team context inside that policy before making the decision. Keep the actor's persisted `current_team_id` unchanged.

## Validation rules are ignored

Put each field's validation rules in its `validation` option as a Laravel rule string or array. Aura reads only this option when collecting field validation rules.

```php
[
    'name' => 'Email',
    'slug' => 'email',
    'type' => 'Aura\Base\Fields\Text',
    'validation' => 'required|email|unique:users,email',
],
```

Two patterns from older docs do not work as you might expect:

- Aura ignores `validation_messages` because it is not a supported field option. Customize messages through Laravel or the resource-level hook below.
- A closure in `validation` is passed to the validator as-is. Use a plain Laravel rule string or array, and adjust rules dynamically with `modifyValidationRules()` below.

To adjust rules dynamically, add a `modifyValidationRules()` method to the resource. Both the create and edit components call it if it exists:

```php
public function modifyValidationRules($rules, $form, $component)
{
    if (($form['fields']['type'] ?? null) === 'business') {
        $rules['fields.email'] = 'required|email|unique:businesses,email';
    }

    return $rules;
}
```

## Conditional fields never show (or always show)

Define visibility conditions in the field's `conditional_logic` option. Aura evaluates these conditions to decide whether to show the field. There is no `displayIf` or `hideIf` API.

The operator must be one of the following exact strings. Any other value, including a bare `=`, falls through to `false`, so the field is hidden:

| Operator | Meaning |
|----------|---------|
| `==` | equals |
| `!=` | not equals |
| `<=` | less than or equal |
| `>=` | greater than or equal |
| `<` | less than |
| `>` | greater than |

```php
[
    'name' => 'Weight',
    'slug' => 'weight',
    'type' => 'Aura\Base\Fields\Number',
    'conditional_logic' => [
        [
            'field' => 'product_type', // must match the other field's slug exactly
            'operator' => '==',        // NOT '='
            'value' => 'physical',
        ],
    ],
],
```

The `field` value must exactly match the slug of the field you want to check. To check the current user's role instead, use the special `field => 'role'` condition with `==` or `!=`. Super admins pass all role conditions.

## Filtered tables and bulk actions

### A bulk action says a selected row is unavailable

On the current main branch, a bulk action checks that each selected row still belongs to the table's filtered search results, then authorizes each record. The results come from `rowsQuery()`. If you change a filter after selecting rows, some may no longer be available to the action. Clear the selection and select rows from the current results.

Selecting all rows is limited to 500 records. A larger selection returns `Selection exceeds the maximum of 500 rows.` Split the operation or define a reviewed collection action that handles a bounded set.

### A Date or Datetime filter returns no rows

Date and datetime filters use these operator keys:

```text
date_is
date_is_not
date_before
date_after
date_on_or_before
date_on_or_after
date_is_empty
date_is_not_empty
```

Use the field's generated filter UI or store those exact keys in a saved filter. The query layer still accepts the older bare range aliases `before`, `after`, `on_or_before`, and `on_or_after`, but new filter payloads should use the `date_*` names.

## Media upload details

Creating an image attachment queues the `GenerateImageThumbnail` job. It skips thumbnail generation in either of these cases:

- The application is running in the testing environment.
- The `aura.media.generate_thumbnails` setting is `false`.

Aura serves thumbnails through the named route `aura.image`. By default, its URL is `/admin/img/{path}` with a `width` query parameter. The prefix follows the `aura.path` setting.

Thumbnail dimensions are restricted by default. When `aura.media.restrict_to_dimensions` is `true`, the requested size must be listed in `aura.media.dimensions`:

```php
// config/aura.php
'media' => [
    'disk' => 'public',
    'restrict_to_dimensions' => true,
    'dimensions' => [
        ['name' => 'xs', 'width' => 200],
        ['name' => 'sm', 'width' => 600],
        ['name' => 'md', 'width' => 1200],
        ['name' => 'lg', 'width' => 2000],
        ['name' => 'thumbnail', 'width' => 600, 'height' => 600],
    ],
],
```

Requesting a width that is not in that list aborts with 404:

```
Requested thumbnail dimensions are not allowed.
```

Request a configured size or add the required width to `dimensions`. For example, `$attachment->thumbnail('sm')` looks up the width for the named size. If the source file is missing, the error is:

```
Original image not found: {path}
```

Media uses the public disk by default. If you use that disk, run `php artisan storage:link` to make the files accessible.

<a id="testing-gotchas"></a>
<a id="testing-issues"></a>

## Testing problems

Aura resets its facade registrations and process-level scope state between the package's feature test groups. If you use a custom test bootstrap, reset the Aura facade, resource registrations, conditional-logic cache, and the state of `TeamScope` and `ScopedScope` between tests.

Changing `current_team_id` through the user model clears the current-team cache. If a test writes the column directly, clear the cache explicitly:

  ```php
  \Aura\Base\Resources\User::clearCurrentTeamCache($user->id);
  ```

`find()` and `first()` still apply global scopes in tests. If a controlled diagnostic needs an unscoped record, bypass only the named scopes needed for that check:

  ```php
  $record = Project::withoutGlobalScope(TeamScope::class)
      ->withoutGlobalScope(TypeScope::class)
      ->find($id);
  ```

Thumbnail generation returns early in the `testing` environment. Do not assert on generated thumbnail files.

For teams-off package tests, run the dedicated configuration:

  ```bash
  vendor/bin/pest -c phpunit-without-teams.xml
  ```

<a id="migration-troubleshooting"></a>

## Migration and schema problems

### `aura:schema-update` aborts without changing the table

The schema synchronizer leaves the table unchanged if it cannot safely parse the migration or finds no columns. This prevents it from dropping columns based on an incomplete reading of the migration. Inspect the migration named in the error. It must contain a `Schema::create()` block with simple quoted column names. The parser accepts the standard `id`, `softDeletes`, and `timestamps` methods and chained modifiers such as `->nullable()`.

```bash
php artisan aura:schema-update database/migrations/2026_01_01_000000_create_projects_table.php
```

The command adds missing columns and only drops unlisted columns when `--drop` is supplied and the drop is confirmed or `--force` is supplied. Review the migration before using either option. It does not change the type of an existing column. Use a normal Laravel migration for a type change.

To move a resource from the shared posts table to a custom table, use:

```bash
php artisan aura:transfer-from-posts-to-custom-table "App\Aura\Resources\Project"
```

Review the generated custom table migration and run the transfer in a new PHP process after the migration has completed. The transfer reads the old `posts` and `meta` rows, inserts new records, does not preserve IDs, and is not idempotent or transactional. If the target table does not contain every column included by the transfer payload, the insert can fail.

### Migrating legacy per-type meta tables

If an older application has `post_meta`, `team_meta`, or `user_meta`, run:

```bash
php artisan aura:migrate-post-meta-to-meta
```

The command creates the current polymorphic `meta` table when needed and skips orphaned rows. The table is shared by posts, teams, users, and custom resources. Do not delete rows from `meta` manually before the migration.

<a id="performance-issues"></a>

## Slow resource tables

By default, serializing a resource also computes and includes every input field. Aura keeps this behavior for compatibility, but it can slow down large tables or Livewire responses. Disable it in `config/aura.php`, then append the `fields` accessor only to responses that need it:

```php
'features' => [
    'legacy_fields_append' => false,
],
```

See [Performance](/docs/performance) for query and table rendering guidance.

<a id="upgrade-procedures"></a>

## Upgrade checks

After updating the Aura package:

```bash
php artisan migrate
php artisan aura:publish
php artisan config:clear
```

Review [Installation](/docs/installation) when moving between beta4 and the current main branch. Review changed keys such as `views.layout`, `features.legacy_fields_append`, and the teams settings before copying an old published config over a new one.

<a id="debugging-guide"></a>

## Safe diagnostics

Start with the smallest check that answers your question. The commands below inspect the installation or clear Laravel's compiled configuration without changing database data:

```bash
composer show eminiarts/aura-cms
php -v
php artisan about
php artisan route:list --path=admin
php artisan route:list --path=two-factor-challenge
php artisan migrate:status
php artisan config:clear
```

If a resource is missing, check its namespace and slug, then confirm its storage settings and table with `$customTable`, `$usesMeta`, and `getTable()`.

For a permission failure, check the user's current team and resolved role. Then check whether the resource enables the action and whether the role grants its exact permission key.

For an asset failure, check `public/vendor/aura/manifest.json`, the public storage link, the configured media disk, and GD.

<a id="error-messages-reference"></a>

## Common messages

| Message | Check |
| --- | --- |
| `Aura CMS assets are not published. Please run: php artisan aura:publish` | Publish the current package assets and confirm `public/vendor/aura/manifest.json` exists. |
| `Requested thumbnail dimensions are not allowed.` | Match the request to `aura.media.dimensions`. |
| `Original image not found: {path}` | Check the configured media disk and the original attachment path. |
| `This action is unauthorized.` | Check the resource capability flag, current team, role slug, and exact permission key. |
| `Selection exceeds the maximum of 500 rows.` | Reduce the current filtered selection. |
| `Unable to safely parse columns from ...` | Review the migration's `Schema::create` block and simple column declarations. The command aborts without changing the table. |

<a id="frequently-asked-questions"></a>

## Frequently asked questions

### Does Aura provide a generated REST API?

Aura Base does not generate REST routes. A host application or a separate plugin must define its routes, controllers, authorization, and serialization.

### Does Aura require Spatie Laravel Permission?

No. Aura provides its own role and permission resources. It checks access through JSON permission maps, the `AuraGlobalAdmin` gate, and built-in resource and team policies.

### Can I enable the Resource Editor in production?

The Resource Editor is intended for local development. Use it locally, review the generated PHP and migration changes, and deploy those source changes. Do not use it as a production schema editor.

### Should I regenerate the application key or reset the database when Aura fails?

No. Check the installed version, command output, config cache, migration state, published assets, and current team first. Preserve application data while diagnosing the failure.

## Related

- [Resources](/docs/resources) and [Custom Tables](/docs/custom-tables)
- [Meta Fields](/docs/meta-fields)
- [Roles and Permissions](/docs/roles-permissions)
- [Teams](/docs/teams)
- [Media Library](/docs/media-manager)
- [Testing](/docs/testing)
