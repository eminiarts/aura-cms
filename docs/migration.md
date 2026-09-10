# Migration

Aura has separate commands for upgrading the package schema, moving data between storage models, and generating or synchronizing custom-table migrations.

This page follows the current main branch's 1.0 baseline. The public `v1.0.0-beta.4` package predates some of the current command safeguards. Check the version installed in your application before applying the examples.

Make a complete database backup and back up resource files before using a command that edits PHP or drops columns. Review the generated migration and the target schema on a staging copy before changing an application with data.

## Upgrade from 0.x

Aura 1.0 is a fresh baseline. It has no automated upgrade path for 0.x schemas or data. There is no command that converts a 0.2 installation into a 1.0 installation.

For an existing 0.x application:

1. Keep the application on `eminiarts/aura-cms:^0.2` while preparing the migration.
2. Create a fresh 1.0 installation and compare its published configuration and database schema with the application.
3. Write and test application-specific migrations for the data that must be retained.
4. Update custom Livewire components for Livewire 4 and test the teams mode used by the application.
5. Run the upgrade on a staging copy, verify rollback from your own backup, and deploy only after that review.

The current 1.0 support baseline requires PHP 8.4, Laravel 13, and Livewire 4. Read `UPGRADING.md` in the package for the complete list of breaking changes.

## Apply package migrations

After updating the package, run the application's pending migrations and republish Aura's compiled assets:

```bash
php artisan migrate
php artisan aura:publish
php artisan view:clear
php artisan config:clear
```

The publish command replaces `public/vendor/aura` with the package's verified compiled assets, libraries, and public files. It does not publish configuration or views, and it does not run migrations.

The published Aura install migration records which tables it created and which Aura columns it added to an existing `users` table. On rollback it removes only those recorded tables and columns. It preserves host-owned tables such as an existing `users` or `sessions` table and their data. If an Aura-owned table already exists, the install migration stops before changing the host schema. Resolve that conflict deliberately instead of resetting the database.

Set `aura.teams` before the first migration. Teams-enabled and teams-disabled installations have different tables and columns. With teams disabled, the install schema omits team-specific tables and columns, and custom-table migration generation omits `team_id`. Changing this setting after the schema exists requires a planned schema and data migration.

## Storage models

By default, resources store their base columns in the shared posts table and other input fields in the shared meta table. A resource with a custom table stores fields in the table named by its `$table` property, according to its storage settings. Using a custom table and using meta storage are separate choices. See [Custom tables](/docs/custom-tables) for how `$customTable` and `$usesMeta` work together.

## Consolidate legacy meta tables

To import rows from the legacy post, team, and user meta tables into the shared meta table, run:

```bash
php artisan aura:migrate-post-meta-to-meta
```

For each row in `post_meta`, the command finds the referenced post and looks up its registered resource from the post's type using `Aura::findResourceBySlug()`. Rows from `team_meta` and `user_meta` use the built-in team and user resource classes. The command skips rows whose referenced record no longer exists, or whose post type does not resolve to a registered resource. If none of the three legacy tables exists, it exits successfully without creating the shared meta table.

The command creates the shared meta table when needed and inserts each migrated row individually. It does not remove the legacy tables or rows, deduplicate existing records, or provide an all-or-nothing transaction. Run it once after a backup, then compare row counts and sample values before planning cleanup.

## Generate a custom-table migration

To create or rewrite a custom-table migration from a resource's current input fields, run:

```bash
php artisan aura:create-resource-migration "App\Aura\Resources\Product"
php artisan aura:create-resource-migration "App\Aura\Resources\Product" --table=inventory_items
```

Pass the fully qualified resource class as the required argument. Use `--table` to override its loaded table name. The command generates columns from the resource's input fields, so panels, tabs, and other layout fields do not produce columns. Each field class supplies its column type and nullability.

The migration also adds an `id` column, a `user_id` column, and nullable `created_at` and `updated_at` columns. It includes `team_id` when teams are enabled.

The generated migration does not add indexes, defaults, foreign-key constraints, cascade rules, soft deletes, or the `$table->timestamps()` helper. It does not run `migrate`. Review the file, add the schema details the application needs, and then run:

```bash
php artisan migrate
```

If the migration has already run, write a new versioned migration instead of rewriting the applied create migration. The command reuses a file whose name contains `create_{table}_table`, so inspect the file after every run.

## Convert a posts-backed resource

To change a resource that uses the shared posts table to use a custom table, run:

```bash
php artisan aura:migrate-from-posts-to-custom-table "App\Aura\Resources\Product"
```

The resource class is optional. If you omit it, the command prompts for a registered resource. If you provide it, the command checks that the class exists. It then:

1. Sets `$customTable = true` in the resource file.
2. Sets `$table` to the snake-case plural of the resource class basename.
3. Adds `$usesMeta = false` when the file has no `$customTable` declaration. If the file already declares `$customTable`, the command changes that declaration and leaves `$usesMeta` as it is.
4. Calls `aura:create-resource-migration` with the chosen table name.
5. Prompts to run the migration and then prompts to start the transfer command.

Both confirmation prompts default to yes. Answer no to both so you can review the edited resource file and generated migration before proceeding.

The command edits the resource file during the current Artisan process. It passes the table name directly to the migration generator, but the optional transfer runs before PHP reloads the edited class. Run the migration and transfer as separate commands so the transfer loads the updated class:

```bash
php artisan migrate
php artisan aura:transfer-from-posts-to-custom-table "App\Aura\Resources\Product"
```

Before starting the transfer, review source and target row counts, field values, casts, and relationship data.

## Transfer data to a custom table

The transfer command accepts a resource class or prompts for one:

```bash
php artisan aura:transfer-from-posts-to-custom-table "App\Aura\Resources\Product"
```

The command selects posts whose type matches the resource and loads their meta values. It matches meta rows by the resource class in `metable_type` and the post ID in `metable_id`, then creates each target record through the resource's `create()` method.

It prepares the timestamps, user ID, team ID when present, and meta values. For standard Aura resources, it then merges the full post row because they inherit Eloquent's `fillable()` method. The resource's fillable rules and save hooks determine which attributes reach the insert.

Review those attributes against the target schema before transferring data. A generated custom table may lack shared post columns such as `title`, `content`, `type`, or `status`. It may also need changes to account for application-specific casts and relationship data. The target must contain every attribute that reaches the insert.

The command has no strategy for preserving IDs or updating existing records. It inserts rows one at a time without a transaction. Repeated runs can duplicate data or fail on target constraints.

Relationship and taxonomy links in `post_relations` need a separate migration plan because the command does not read that table. It leaves the original posts and meta rows in place.

## Synchronize a table from a migration file

To compare a table with a simple create-table migration and add missing columns, run:

```bash
php artisan aura:schema-update database/migrations/2026_01_01_000000_create_products_table.php
php artisan aura:schema-update database/migrations/2026_01_01_000000_create_products_table.php --drop
php artisan aura:schema-update database/migrations/2026_01_01_000000_create_products_table.php --drop --force
```

If you omit the path, the command prompts for a file from `database/migrations`. It reads the table name from `Schema::create()` and accepts simple column declarations with one quoted column name. Single and double quotes both work, as do hyphenated field names generated by the Resource Editor:

```php
$table->string('title')->nullable();
$table->foreignId("user_id")->nullable();
```

The command adds missing columns as nullable. It keeps extra live columns unless `--drop` is supplied. With `--drop`, it asks before dropping columns unless `--force` is also supplied. It always keeps `id`, `created_at`, `updated_at`, and `deleted_at` out of the drop list.

The command does not alter the type of an existing column. It does not parse `Schema::table()` migrations or general PHP. A declaration with extra arguments such as `string('title', 255)`, or any other `$table` call the parser cannot classify, makes the command exit before schema mutation. An empty or partial parse also exits before mutation. Use a normal, hand-written Laravel migration for type changes, renames, data conversion, indexes, constraints, or other complex schema work.

If the target table does not exist, the command runs `php artisan migrate`. That runs the application's pending migrations, so review them before invoking the command.

When `aura.features.custom_tables_for_resources` is `true` or `'single'`, the Resource Editor rewrites the create migration and runs the schema update with `--drop --force`. This can drop columns without asking for confirmation. The generated schema includes a team ID column only when teams are enabled.

The updater accepts the editor's single- and double-quoted column declarations. It stops before changing the table if a declaration is ambiguous. If synchronization fails, Aura raises an error and restores the resource and migration files. If the migration file was newly created, Aura removes it.

## Generate resources from existing tables

To generate a resource from an existing table, run:

```bash
php artisan aura:transform-table-to-resource articles
```

This creates an Article resource in `app/Aura/Resources`. The command refuses to overwrite an existing file. It generates a field for every column, including identifiers and timestamps, using these mappings:

| Database column type | Generated field |
| --- | --- |
| `text`, `longtext` | Textarea |
| `integer`, `float`, `double` | Number |
| `date` | Date |
| Other types | Text |

Review the class, storage settings, field definitions, and validation before registering it.

To generate resources for every database table, excluding the built-in list of Laravel and Aura system tables, run:

```bash
php artisan aura:database-to-resources
```

Treat the generated files as starting points. Check each table name, field mapping, storage mode, and relationship before using the resource.

## Related

- [Custom tables](/docs/custom-tables) explains storage flags, generated schemas, and the Resource Editor listeners.
- [Meta fields](/docs/meta-fields) explains the shared key/value table and field queries.
- [Resources](/docs/resources) covers resource classes and storage configuration.
- [Teams](/docs/teams) documents the teams-enabled and teams-disabled schema.
