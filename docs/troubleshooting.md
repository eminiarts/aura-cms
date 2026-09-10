# Troubleshooting

This page covers failures caused by Aura's installer, resources, fields, teams, permissions, assets, and configuration. For Composer, PHP extensions, database drivers, and HTTP limits, use the Laravel documentation.

The page describes the current `main` branch unless it says `beta4`. The public Composer release is `v1.0.0-beta.4`, and that release has separate installer and cache behavior. Use the release-specific steps in [Installation](/docs/installation) when `composer show eminiarts/aura-cms` reports beta4.

<a id="common-issues"></a>
<a id="common-issues-and-solutions"></a>

## Quick triage

Check the installed package and application runtime first:

```bash
composer show eminiarts/aura-cms
php -v
php artisan about
```

Current `main` requires PHP 8.4, Laravel 13, Livewire 4, and the GD extension used by Intervention Image3. The public beta4 release supports an older runtime matrix. Check [Installation](/docs/installation) before applying a `main` example to beta4.

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

Current `main` stops `aura:install` when `aura:extend-user-model`, `aura:install-config`, `migrate`, `aura:user`, or `storage:link` fails. Read the first failed child command and fix that problem before rerunning the installer. Check the final route with `php artisan route:list --path=admin`.

Beta4 can print its final installer message after a child command failed. Check each command's output and confirm that `/login` loads before treating beta4 installation as complete. The beta4 teams-off sequence is documented in [Installation](/docs/installation#without-teams).

### Aura-owned tables already exist

The Aura migration stops when package-owned tables such as `posts`, `meta`, `roles`, or `permissions` already exist. Run `php artisan migrate:status` to inspect migration state and compare the existing schema with the published migration. Use a database with no conflicting Aura-owned tables for a fresh installation, or plan a data-preserving migration for an existing application. Do not drop tables or reset the database as an installation shortcut.

### The User model was not updated

`aura:extend-user-model` edits a standard `app/Models/User.php` that extends Laravel's `Authenticatable` class. If you declined the prompt, run:

```bash
php artisan aura:extend-user-model
```

Review a custom User model manually. It should extend `Aura\Base\Resources\User` or preserve the contracts and traits that Aura's authentication flow requires. The command does not rewrite an arbitrary custom model.

### Teams-off installation behaves like teams-on

Choose the teams setting before the schema is created. On current `main`, a non-interactive installation passes the option to the installer:

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

Current `main`'s `aura:install-config` clears both configuration and application cache after it writes the selected values. Beta4 can leave a cached configuration file and writes literal values into the published config. Review the beta4 notes in [Installation](/docs/installation).

The current layout component alias is `aura::layout.app`. If an older published config still contains `aura::layouts.app`, update the `views.layout` value and clear the config cache. The shipped component is `resources/views/components/layout/app.blade.php`.

### A new resource or permission is missing from the sidebar

Current `main` includes the registered resource list in the navigation cache key, so adding a resource invalidates the relevant sidebar entry. After changing a role's permissions, an already cached sidebar can still show the old result. Run `php artisan cache:clear` if a fresh request does not reflect the grant. Beta4 has a stale navigation cache after adding a resource. Use the beta4 instructions in [Installation](/docs/installation).

<a id="assets-and-media"></a>
<a id="media--file-upload-problems"></a>

## Assets and media

### The admin page is unstyled or JavaScript does not run

Aura checks `public/vendor/aura/manifest.json` and the files referenced by that manifest. If the admin panel renders unstyled or the JavaScript is dead, republish the package assets:

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

Current `main` creates the link during `aura:install`. Beta4 does not, so the command is required after a beta4 installation. See the beta4 notes in [Installation](/docs/installation).

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

Aura discovers application resources under `aura-settings.paths.resources.path`, which defaults to `app/Aura/Resources` with the `App\Aura\Resources` namespace. Only classes that extend `Aura\Base\Resource` are registered.

A resource goes missing when:

- The file is not under `app/Aura/Resources`.
- The class does not `extend Aura\Base\Resource`.
- The class name / namespace do not match the file path (PSR-4 autoloading fails silently).

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

Current `main` includes the registered resource list in the navigation cache key, so adding a resource invalidates the relevant sidebar entry. After changing a role's permissions, an already cached sidebar can still show the old result. Run `php artisan cache:clear` if a fresh request does not reflect the grant.

### Resource Editor returns 404 or 403

The resource editor (`/admin/resources/{slug}/editor`) guards itself in three ways in `mount()`. Each aborts with a specific status:

| Status | Message | Cause |
|--------|---------|-------|
| 404 | (none) | `config('aura.features.resource_editor')` is `false` |
| 403 | `Only App resources can be edited.` | `isVendorResource()` is true. The resource class is outside the `App\` namespace. |
| 403 | `Your fields have closures. You can not use the Resource Builder with Closures.` | `getFields()` contains closures (dynamic options, closure validation, etc.) |

The feature flag defaults to on only in the local environment:

```php
// config/aura.php
'features' => [
    'resource_editor' => config('app.env') == 'local' ? true : false,
],
```

This is read directly from config. There is no `AURA_RESOURCE_EDITOR` env var. The middleware also permits the `testing` environment.

The closure guard is by design. The editor rewrites your `getFields()` array to a file and cannot serialize closures. If you need dynamic field behaviour, edit the resource by hand.

<a id="common-gotchas"></a>
<a id="database-issues"></a>

## Teams and missing records

### Records exist in the database but queries return empty

Resources can apply three global scopes:

- `TypeScope` limits shared `posts` records to the resource's `type`.
- `TeamScope` limits team-owned records to the authenticated user's current team when teams are enabled.
- `ScopedScope` limits records to the current user when the role has the resource's `scope` permission and the user is not a Super Admin.

A missing current team fails closed for ordinary authenticated users. Guests skip `TeamScope` so login, registration, and password reset can resolve their models. The `Team` resource itself is not team-scoped. `Role` queries in a team context include that team's Team Roles and the shared Global Roles, with shadow resolution applied by the role catalog.

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

Do not re-add these scopes manually in your resource's `booted()`. `Aura\Base\Resource` already registers them, and `TeamScope` no-ops when `config('aura.teams') === false`.

### Switching teams does not change the result set

`User::switchTeam()` refuses a non-member unless the user is a Global Admin visiting that team. A normal model save that changes `current_team_id` clears the `user_{id}_current_team_id` cache key on current `main`.

If an integration writes the column through `DB::table()` instead of the User model, clear the same key through the public helper:

```php
\Aura\Base\Resources\User::clearCurrentTeamCache($userId);
```

The current-team pointer does not create a Membership. A Global Admin visiting a team remains a visitor unless a Membership is added separately.

### A team role appears twice or the wrong role is applied

In teams-on mode, a Global Role has `team_id = null`. A Team Role with the same slug shadows it inside that team. `Role::resolveForTeam($slug, $teamId)` returns the Team Role when one exists and otherwise returns the Global Role. Current role lists and pickers apply the same shadow resolution, and server-side role saving rejects a hidden Global Role ID when its slug is shadowed by the target team.

When investigating a role mismatch, compare the role slug and the target team's ID. Do not change Membership pivot rows to point at the Shadow. Membership identity is the role slug, and creating or deleting a Shadow changes the resolved role.

### Teams-off mode has missing tables or resources

With `aura.teams` set to `false` before migration, Aura does not create the `teams` table or register the Team and TeamInvitation resources. The role and membership tables use their teams-off shape, and `TeamScope` is disabled. Follow the version-specific [installation steps](/docs/installation), especially the [beta4 teams-off sequence](/docs/installation#without-teams), instead of changing the setting on an existing schema without a migration plan.

## Field storage

### A field value saves as `null`

`$customTable` and `$usesMeta` are independent flags. Check both on the resource and check the table that the model uses:

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

The default OTP limiter permits five attempts per minute. A host value at `fortify.limiters.two-factor` replaces Aura's `two-factor` limiter. A 429 response after repeated invalid codes means the limiter is working. Start a new challenge after the limiter window instead of disabling it.

Setting `aura.auth.2fa` to `false` disables Aura's management routes for enabling and viewing two-factor data. An already confirmed user still receives the pre-authentication challenge.

To inspect the routes without signing in:

```bash
php artisan route:list --path=two-factor-challenge
```

### A resource action returns 403

```
403 | This action is unauthorized.
```

Aura does not use Spatie Laravel Permission. Permissions are stored as a JSON map on each role, and access is checked with `hasPermissionTo($ability, $resource)`, which looks for an `"{$ability}-{$slug}"` key set to `true` on any of the user's roles. A role flagged `super_admin` short-circuits `hasPermissionTo()`. The create, update, view, and view-any policies check `$createEnabled`, `$editEnabled`, `$viewEnabled`, and `$indexViewEnabled` before the Super Admin grant.

Useful methods on the user (from `Aura\Base\Resources\User`):

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

(The command is `aura:create-resource-permissions`, not `aura:permissions`.) Then grant the relevant abilities to the appropriate role.

In teams-on mode, pass a numeric target team when the rows belong to a team other than the authenticated user's current team:

```bash
php artisan aura:create-resource-permissions --team=12
```

The command excludes the Team resource from the generated resource set. A Super Admin is a role-level grant inside one team. A Global Admin is instance-level and is decided by the `AuraGlobalAdmin` gate.

In teams-on mode, a Global Role has `team_id = null`. A Team Role with the same slug shadows it inside that team. Current role lists and pickers apply that shadow resolution, and server-side role saving rejects a hidden Global Role ID when its slug is shadowed by the target team.

### A role grant works in one team but not another

Built-in team policies evaluate role abilities against the target team. If a custom policy calls `$user->isSuperAdmin()` or `$user->hasPermissionTo()` directly, it uses the User instance's current-team context. Set and validate the target-team context inside that policy before making the decision. Keep the actor's persisted `current_team_id` unchanged.

## Validation rules are ignored

Field validation is a single `validation` key per field. Use a Laravel rule string or array. That is the only validation input `InputFieldsValidation` reads.

```php
[
    'name' => 'Email',
    'slug' => 'email',
    'type' => 'Aura\Base\Fields\Text',
    'validation' => 'required|email|unique:users,email',
],
```

Two patterns from older docs do not work as you might expect:

- **`validation_messages`** is not a field option and is ignored. Customize messages through Laravel or the resource-level hook below.
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

Field visibility uses the `conditional_logic` key, evaluated by `Aura\Base\ConditionalLogic`. There is no `displayIf` or `hideIf` API.

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

`field` must match the referenced field's `slug` exactly. A special `field => 'role'` condition is supported with `==` / `!=` and checks the current user's role; super admins pass all role conditions.

## Filtered tables and bulk actions

### A bulk action says a selected row is unavailable

Current `main` resolves selected rows against the table's filtered and searched `rowsQuery()`, then authorizes each record. If you select a row and change the filter, the row may no longer belong to the current table scope. Clear the selection and select rows from the current result set.

`select all` is limited to 500 rows. A larger selection returns `Selection exceeds the maximum of 500 rows.` Split the operation or define a reviewed collection action that handles a bounded set.

### A Date or Datetime filter returns no rows

The `Date` and `Datetime` fields expose these operator keys:

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

Images get thumbnails from a queued job: creating an `Attachment` dispatches `GenerateImageThumbnail`. The job returns early when:

- The app is in the `testing` environment (thumbnails are intentionally skipped in tests).
- `config('aura.media.generate_thumbnails')` is `false`.

Thumbnails are served through the named route `aura.image`. The route is registered under the `config('aura.path')` prefix (default `admin`), so with the default config it resolves to `/admin/img/{path}` with a `width` query parameter. When `config('aura.media.restrict_to_dimensions')` is `true` (the default), only widths listed in `config('aura.media.dimensions')` are allowed:

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

Fix it by either requesting one of the configured sizes (use `$attachment->thumbnail('sm')`, which maps a size name to its configured width) or adding the width to `dimensions`. If the source file is missing you get:

```
Original image not found: {path}
```

Media lives on the `public` disk, so a missing `php artisan storage:link` also breaks image display.

<a id="testing-gotchas"></a>
<a id="testing-issues"></a>

## Testing problems

- Aura resets its facade registrations and process-level scope state between the package Feature test groups. A custom test bootstrap must also reset the Aura facade, resource registrations, conditional-logic cache, TeamScope state, and ScopedScope state between tests.

- Changing `current_team_id` through the User model clears the current-team cache. If the test writes the column directly, call:

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

The schema synchronizer fails closed when it cannot safely parse the migration or when it finds no columns. This protects an existing table from a partial parse followed by a drop operation. Inspect the migration named in the error. It must contain a `Schema::create()` block with simple quoted column names. The parser accepts the standard `id`, `softDeletes`, and `timestamps` methods and chained modifiers such as `->nullable()`.

```bash
php artisan aura:schema-update database/migrations/2026_01_01_000000_create_projects_table.php
```

The command adds missing columns and only drops unlisted columns when `--drop` is supplied and the drop is confirmed or `--force` is supplied. Review the migration before using either option. It does not change the type of an existing column. Use a normal Laravel migration for a type change.

Moving a resource off the shared `posts` table onto a custom table:

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

Aura appends the computed `fields` accessor to resource serialization by default for compatibility. Resolving it computes every input field. For a large table or Livewire payload, set this feature off in `config/aura.php` and append `fields` only where the response needs it:

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

Review [Installation](/docs/installation) when moving between beta4 and current `main`. Review changed keys such as `views.layout`, `features.legacy_fields_append`, and the teams settings before copying an old published config over a new one.

<a id="debugging-guide"></a>

## Safe diagnostics

Use the smallest read-only check that answers the question:

```bash
composer show eminiarts/aura-cms
php -v
php artisan about
php artisan route:list --path=admin
php artisan route:list --path=two-factor-challenge
php artisan migrate:status
php artisan config:clear
```

For a resource, inspect its class namespace, `$slug`, `$customTable`, `$usesMeta`, and `getTable()`. For a permission failure, inspect the user's current team, the resolved role slug, the resource capability flags, and the exact permission key. For an asset failure, inspect `public/vendor/aura/manifest.json`, the public storage link, the configured media disk, and GD.

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

No. Aura uses its Role and Permission resources, JSON permission maps, the `AuraGlobalAdmin` gate, and the built-in Resource and Team policies.

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
