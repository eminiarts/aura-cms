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

`aura:publish` replaces `public/vendor/aura` with the package's verified `dist`, `libs`, and `public` assets. It does not publish configuration or views, and it does not run migrations.

The published Aura install migration records which tables it created and which Aura columns it added to an existing `users` table. On rollback it removes only those recorded tables and columns. It preserves host-owned tables such as an existing `users` or `sessions` table and their data. If an Aura-owned table already exists, the install migration stops before changing the host schema. Resolve that conflict deliberately instead of resetting the database.

Set `aura.teams` before the first migration. Teams-enabled and teams-disabled installations have different tables and columns. With teams disabled, the install schema omits team-specific tables and columns, and custom-table migration generation omits `team_id`. Changing this setting after the schema exists requires a planned schema and data migration.

## Storage models

The default Resource storage uses the shared `posts` table for its base columns and the shared `meta` table for input fields that are outside those columns. A custom-table Resource uses its `$table` value for the fields selected by its storage flags. `$customTable` and `$usesMeta` are independent. See [Custom tables](/docs/custom-tables) for the complete storage matrix.

## Consolidate legacy meta tables

`aura:migrate-post-meta-to-meta` imports rows from any of the legacy tables that exist:

```bash
php artisan aura:migrate-post-meta-to-meta
```

For `post_meta`, the command finds the referenced post and resolves its `type` with `Aura::findResourceBySlug()`. For `team_meta` and `user_meta`, it writes the built-in Team and User resource classes. It skips rows whose referenced record no longer exists. It also skips a post row when its type does not resolve to a registered Resource. If none of the three legacy tables exists, the command exits successfully without creating `meta`.

The command creates `meta` when needed and inserts each migrated row individually. It does not remove the legacy tables or rows, deduplicate existing records, or provide an all-or-nothing transaction. Run it once after a backup, then compare row counts and sample values before planning cleanup.

## Generate a custom-table migration

`aura:create-resource-migration` creates or rewrites a migration from a Resource's current input fields:

```bash
php artisan aura:create-resource-migration "App\Aura\Resources\Product"
php artisan aura:create-resource-migration "App\Aura\Resources\Product" --table=inventory_items
```

The required argument is the fully qualified Resource class. The optional `--table` value overrides the Resource's loaded table name. The command uses `inputFields()`, so layout fields such as `Panel` and `Tab` do not produce columns. It writes an `id` column, one column per input field, `user_id`, `team_id` when teams are enabled, and nullable `created_at` and `updated_at` columns. Each field class supplies its column type and nullability.

The generated migration does not add indexes, defaults, foreign-key constraints, cascade rules, soft deletes, or the `$table->timestamps()` helper. It does not run `migrate`. Review the file, add the schema details the application needs, and then run:

```bash
php artisan migrate
```

If the migration has already run, write a new versioned migration instead of rewriting the applied create migration. The command reuses a file whose name contains `create_{table}_table`, so inspect the file after every run.

## Convert a posts-backed Resource

`aura:migrate-from-posts-to-custom-table` changes a Resource class and prepares its custom-table migration:

```bash
php artisan aura:migrate-from-posts-to-custom-table "App\Aura\Resources\Product"
```

The argument is optional. Without it, the command prompts for a registered Resource. With an argument, it validates that the class exists. It then:

1. Sets `$customTable = true` in the Resource file.
2. Sets `$table` to the snake-case plural of the Resource class basename.
3. Adds `$usesMeta = false` when the file has no `$customTable` declaration. If the file already declares `$customTable`, the command changes that declaration and leaves `$usesMeta` as it is.
4. Calls `aura:create-resource-migration` with the chosen table name.
5. Prompts to run the migration and then prompts to start the transfer command.

The command edits the Resource file during the current Artisan process. Both confirmation prompts default to yes. The migration generation receives the table name explicitly, but the optional transfer prompt runs before a new PHP process reloads the edited class. For a reviewable conversion, answer no to both prompts, review the Resource file and generated migration, then run the migration and start the transfer as separate commands:

```bash
php artisan migrate
php artisan aura:transfer-from-posts-to-custom-table "App\Aura\Resources\Product"
```

This separate process requirement also gives you a point to review source and target row counts, field values, casts, and relationship data before inserting records.

## Transfer data to a custom table

The transfer command accepts a Resource class or prompts for one:

```bash
php artisan aura:transfer-from-posts-to-custom-table "App\Aura\Resources\Product"
```

It selects rows from `posts` whose `type` matches the Resource, loads `meta` rows whose `metable_type` is the Resource class and whose `metable_id` matches the post, then calls the Resource's `create()` method. The prepared attributes start with timestamps, `user_id`, `team_id` when present, and meta values. A standard Aura Resource inherits Eloquent's `fillable()` method, so the command then merges the full post row. The Resource's fillable rules and save hooks determine which attributes reach the insert. A generated custom table may lack shared columns such as `title`, `content`, `type`, or `status`, so review the payload and target schema before using this command. The command has no ID-preserving or upsert strategy. It inserts rows one at a time and does not wrap the transfer in a transaction, so repeated runs can duplicate data or fail on target constraints.

The command does not read `post_relations`. Relationship and taxonomy links stored there need a separate migration plan. It leaves the original `posts` and `meta` rows in place. The target table must contain every attribute that reaches `create()`. Generated custom-table migrations commonly need review before a transfer because shared post columns, application-specific casts, and relation data may not be represented in the generated table.

## Synchronize a table from a migration file

`aura:schema-update` compares a table with a simple `Schema::create()` migration:

```bash
php artisan aura:schema-update database/migrations/2026_01_01_000000_create_products_table.php
php artisan aura:schema-update database/migrations/2026_01_01_000000_create_products_table.php --drop
php artisan aura:schema-update database/migrations/2026_01_01_000000_create_products_table.php --drop --force
```

Without a path, it prompts for a file from `database/migrations`. The parser reads the table name from `Schema::create()` and recognizes simple column declarations with one quoted column name. Both literal quote styles work, including hyphenated field names generated by the Resource Editor:

```php
$table->string('title')->nullable();
$table->foreignId("user_id")->nullable();
```

The command adds missing columns as nullable. It keeps extra live columns unless `--drop` is supplied. With `--drop`, it asks before dropping columns unless `--force` is also supplied. It always keeps `id`, `created_at`, `updated_at`, and `deleted_at` out of the drop list.

The command does not alter the type of an existing column. It does not parse `Schema::table()` migrations or general PHP. A declaration with extra arguments such as `string('title', 255)`, or any other `$table` call the parser cannot classify, makes the command exit before schema mutation. An empty or partial parse also exits before mutation. Use a normal, hand-written Laravel migration for type changes, renames, data conversion, indexes, constraints, or other complex schema work.

If the target table does not exist, the command runs `php artisan migrate`. That runs the application's pending migrations, so review them before invoking the command.

When `aura.features.custom_tables_for_resources` is `true` or `'single'`, the Resource Editor rewrites the create migration and invokes this command with `--drop --force`. Its generated schema includes `team_id` only when teams are enabled. The updater accepts the single- and double-quoted declarations produced by that listener and stops before changing the table when a declaration is ambiguous. A failed sync raises an error and restores the resource and migration files; a newly created migration is removed.

## Generate resources from existing tables

`aura:transform-table-to-resource` creates one Resource from an existing table:

```bash
php artisan aura:transform-table-to-resource articles
```

It writes `App\Aura\Resources\Article` under `app/Aura/Resources`, refuses to overwrite an existing file, and maps `text` and `longtext` to `Textarea`, `integer`, `float`, and `double` to `Number`, `date` to `Date`, and other column types to `Text`. It generates fields for every column, including identifiers and timestamps. Review the class, storage flags, field definitions, and validation before registering it.

`aura:database-to-resources` runs the same transformation for every database table except the built-in Laravel and Aura system-table list:

```bash
php artisan aura:database-to-resources
```

Treat the generated files as starting points. Check each table name, field mapping, storage mode, and relationship before using the Resource.

## Related

- [Custom tables](/docs/custom-tables) explains storage flags, generated schemas, and the Resource Editor listeners.
- [Meta fields](/docs/meta-fields) explains the shared key/value table and field queries.
- [Resources](/docs/resources) covers Resource classes and storage configuration.
- [Teams](/docs/teams) documents the teams-enabled and teams-disabled schema.
