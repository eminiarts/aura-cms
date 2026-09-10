# Installation

Install Aura in a fresh Laravel application, then sign in to the admin panel. The public Composer package is `v1.0.0-beta.4`. The `main` branch is newer and may contain installer options and fixes that are not in that release.

## Requirements

- PHP 8.4 or newer
- Laravel 13 for `main`. Beta4 also supports Laravel 12.
- Livewire 4, installed by Composer
- Composer 2
- PHP GD. Aura uses Intervention Image3 with the GD driver for thumbnails.
- A configured database connection

Configure the database in `.env` before running the installer. A fresh Laravel application can use its default SQLite database. If you use Laravel Herd, open the application's `.test` URL after installation instead of running `php artisan serve`.

## Install the public beta

Create a fresh application without authentication scaffolding when the Laravel installer asks:

```bash
laravel new aura-new
cd aura-new
```

When using Herd, set the local application URL in `.env`:

```dotenv
APP_NAME="Aura New"
APP_URL=http://aura-new.test
```

Install the public beta and start the guided installer:

```bash
composer require 'eminiarts/aura-cms:^1.0@beta'
php artisan aura:install
```

Accept the prompt to extend the standard User model, configure Aura, run the migrations, and create the first administrator. For this beta4 path, answer **Yes** when asked whether to use teams. To disable teams, follow the separate sequence below. The `aura:user` prompt grants Global Admin status by default. Disable public registration if only administrators should create accounts.

Beta4 does not create the public storage link. Run this after the installer:

```bash
php artisan storage:link
```

For a beta4 installation without teams, use the [separate teams-off sequence](#without-teams). Passing `--teams=false` to the beta4 unified installer does not update the configuration used by the migration in the same process.

<a id="local-checkout"></a>

## Use current main from a local checkout

Use a path repository when you need the current package checkout during development. The example assumes the Aura checkout is next to the Laravel application at `../aura-cms`.

```bash
composer config repositories.aura-cms path ../aura-cms
composer require 'eminiarts/aura-cms:dev-main'
php artisan aura:install
```

The `main` installer creates `public/storage` when it is missing. It also applies the selected teams and registration settings before it runs the migration and stops if a child command fails.

## Scripted installation on current main

The current `main` branch validates the options before publishing files or changing the database. Supply all administrator options, or pass `--no-admin` and create the administrator with `aura:user` in a separate process:

```bash
php artisan aura:install \
    --no-interaction \
    --teams=false \
    --registration=false \
    --admin-name="Aura Admin" \
    --admin-email="admin@example.com" \
    --admin-password="use-a-secret-value"
```

The administrator password must contain at least eight characters, and the email address must be valid. Replace the example password before running the command.

For a teams-enabled scripted install, change `--teams=false` to `--teams=true`. The current `main` command also accepts `--team-name` and `--no-global-admin` when those options are needed.

## What `aura:install` does

The installer publishes the two Aura configuration files, compiled assets under `public/vendor/aura`, and the package migrations. It then runs `aura:extend-user-model`, `aura:install-config`, the application migration, and `aura:user` when you choose those prompts. The migration creates Aura-owned tables and adds the required columns to Laravel's existing `users` table.

`aura:extend-user-model` updates a standard `app/Models/User.php` that extends Laravel's `Authenticatable` class. If the application uses a custom User model, adapt that model to extend `Aura\Base\Resources\User` while preserving its existing contracts and behavior.

`aura:publish` republishes Aura's compiled assets. You do not need to build Aura with npm or change the application's Vite configuration:

```bash
php artisan aura:publish
```

Aura's media library uses the `public` disk by default. The public storage link must exist before uploaded media can be served. Current `main` creates it as part of `aura:install`; beta4 requires the explicit `storage:link` command shown above.

<a id="without-teams"></a>

## Install beta4 without teams

After `composer require`, run these commands instead of `aura:install` when using `v1.0.0-beta.4` without teams. Each step runs in a new PHP process, so the migration reads the teams setting written by `aura:install-config`:

```bash
php artisan vendor:publish --tag=aura-config
php artisan aura:install-config --no-interaction --teams=false --registration=false
php artisan aura:extend-user-model
php artisan vendor:publish --tag=aura-migrations
php artisan migrate
php artisan aura:publish
php artisan aura:user
php artisan storage:link
```

Keep `teams` set to `false` in `config/aura.php`. Changing the teams setting after the schema exists requires a planned schema migration. Do not use a destructive reset command on an application that contains data.

For beta4 with teams enabled, the unified non-interactive installer accepts the administrator options directly:

```bash
php artisan aura:install \
    --no-interaction \
    --teams=true \
    --registration=false \
    --admin-name="Aura Admin" \
    --admin-email="admin@example.com" \
    --admin-password="use-a-secret-value"
php artisan storage:link
```

Beta4 can print its final installer message after a child command fails. Check each command's output and verify that the login page works before treating the installation as complete.

## Sign in

The default admin path is `/admin`, and the login path is `/login`. Open the application's URL followed by `/login` and use the administrator credentials entered during installation.

For the `aura-new` Herd application, the usual URL is:

```text
http://aura-new.test/login
```

After signing in, open `/admin`. See [Configuration](/docs/configuration) for the path and domain settings.

![Login page](/images/docs/installation/login-page.png)

![Dashboard](/images/docs/installation/dashboard-overview.png)

## Create a resource

After the admin panel works, create the resource from the [Introduction](/docs/introduction) example:

```bash
php artisan aura:resource Article
```

Edit the generated class, then open `/admin/article`. With the default `posts` and `meta` storage, a field outside the core columns does not require a migration. `title` and `content` are core `posts` columns.

If teams are enabled, Aura saves the new record in the current team. See [Teams](/docs/teams) before changing the teams setting.

## Troubleshooting

### Aura-owned tables already exist

The migration stops when tables such as `posts`, `meta`, `roles`, or `permissions` already exist. Use a database with no conflicting Aura-owned tables, or resolve the schema conflict deliberately. Do not drop an existing application's tables as an installation shortcut.

### The User model was not updated

If you declined the `aura:extend-user-model` prompt, run the command again and accept it:

```bash
php artisan aura:extend-user-model
```

The command only edits a standard User model that contains `extends Authenticatable`. Review custom User models manually.

### Configuration changes have no effect in beta4

Beta4 rewrites `config/aura.php` with literal values when `aura:install-config` runs. Review the file after installation and run `php artisan config:clear` if the application has cached configuration. The current `main` command preserves `env()` expressions and writes their selected values to `.env`.

Beta4 can also write `var(--primary-600]` into `theme.colors.light.primary` and `theme.colors.dark.primary`. Correct those values to `var(--primary-600)` if present.

### Assets or media are missing

Republish assets, create the storage link, and verify that GD is enabled:

```bash
php artisan aura:publish
php artisan storage:link
php -m | grep -i '^gd$'
```

### A new resource is missing from the sidebar

Beta4 caches the navigation list. Clear the application cache after adding a resource:

```bash
php artisan cache:clear
```

<a id="deployment"></a>

## Deployment

Commit the published Aura configuration and migrations with the application. During deployment, run `php artisan migrate --force`. After updating the Aura package, republish its assets with `php artisan aura:publish`.

## Next steps

- [Quick Start](/docs/quick-start)
- [Resources](/docs/resources)
- [Fields](/docs/fields)
- [Configuration](/docs/configuration)
