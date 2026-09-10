# Installation

Install Aura in a fresh Laravel 13 application, then sign in to the admin panel. If you already have a blank Laravel app, start with its database and URL settings below.

> The public Composer package is currently `v1.0.0-beta.4`; install it with `^1.0@beta`. The repository `main` branch is newer, targets Laravel 13, and can contain installer options that are not in the public beta.

## Requirements

- PHP 8.4 or newer
- Laravel 13
- Livewire 4, installed automatically by Composer
- Composer 2
- PHP GD for Aura's image thumbnail generation
- A configured database connection

Configure the database in `.env` before running the installer. A fresh Laravel app can use its default SQLite database. With Laravel Herd, use the app's HTTP `.test` URL after installation. You do not need to run `php artisan serve`.

## Install Aura

Create a fresh app. Choose a blank application without authentication scaffolding when the Laravel installer asks:

```bash
laravel new aura-new
cd aura-new
```

Set the local URL in `.env` when using Herd:

```dotenv
APP_NAME="Aura New"
APP_URL=http://aura-new.test
```

From the root of the Laravel application, run:

```bash
composer require 'eminiarts/aura-cms:^1.0@beta'
php artisan aura:install
php artisan storage:link
```

Accept the prompts to extend the User model, configure Aura, run migrations, and create an administrator. Choose **Yes** for teams for this setup. Disable public registration if only administrators should create accounts. The first administrator is a Global Admin by default.

For an application without teams, use [the separate installation steps below](#without-teams). In the public beta, changing the teams setting inside `aura:install` does not take effect until the next PHP process.

Run `storage:link` after the installer so uploaded media can be served from the default `public` disk. The public beta does not create this link itself.

## What `aura:install` does

The installer:

1. Publishes `config/aura.php` and `config/aura-settings.php`.
2. Publishes Aura's compiled assets to `public/vendor/aura`. No npm build or changes to your Vite or Tailwind configuration are needed for the admin panel.
3. Publishes Aura's migrations to `database/migrations`.
4. Runs `aura:extend-user-model`, which asks before changing the standard `app/Models/User.php` to extend `Aura\Base\Resources\User`.
5. Runs `aura:install-config` when you accept the configuration prompt.
6. Runs the application migrations and seeds Aura's built-in role catalog when you accept the migration prompt.
7. Runs `aura:user` when you accept the administrator prompt.

The published migration creates Aura's tables and adds columns to Laravel's existing `users` table. Tables owned by Aura, such as `posts`, `meta`, and `roles`, must not already exist. For an application with existing authentication or data, review the User model and route changes before installing.

## Sign in

The admin path is `/admin` by default. Authentication is at `/login`.

Open the app URL followed by `/login` and use the administrator credentials entered during installation. For the `aura-new` Herd application, the usual URL is:

```text
http://aura-new.test/login
```

After sign-in, the dashboard is at `/admin`. See [Configuration](/docs/configuration) for other settings.

![Login page](/images/docs/installation/login-page.png)

![Dashboard](/images/docs/installation/dashboard-overview.png)

## Create a resource

After the admin panel works, create the resource from the [Introduction](/docs/introduction) example:

```bash
php artisan aura:resource Article
```

Edit the generated class, then open `/admin/article`. The default `posts` and `meta` storage means adding a field outside the core columns does not require a migration. `title` and `content` are core `posts` columns.

If the application uses teams, the new record is saved in the current team. See [Teams](/docs/teams) before changing the teams setting.

## Non-interactive installation

For a scripted install, skip administrator creation and create it in a separate command:

```bash
php artisan aura:install \
    --no-interaction \
    --teams=true \
    --registration=false \
    --no-admin

php artisan aura:user \
    --no-interaction \
    --name="Aura Admin" \
    --email="admin@example.com" \
    --password="use-a-secret-value"

php artisan storage:link
```

You can also create the administrator in the install command by passing `--admin-name`, `--admin-email`, and `--admin-password` together instead of `--no-admin`. Replace the example password with your own value.

## Troubleshooting

If a step reports an error, resolve it before continuing. The public beta can print its final success message even when a setup command failed.

### Aura-owned tables already exist

The migration stops when tables such as `posts`, `meta`, `roles`, or `permissions` already exist. Point the application at a fresh database or resolve the existing schema conflict deliberately. Do not drop an existing application's tables as an installation shortcut.

### The User model was not updated

The installer only updates a standard `app/Models/User.php` that extends Laravel's `Authenticatable` class. If you declined the prompt, run:

```bash
php artisan aura:extend-user-model
```

If the application has a custom user model, adapt that model to extend `Aura\Base\Resources\User` and preserve the application's other contracts and behavior.

### Configuration changes have no effect

The beta installer writes literal values to `config/aura.php`, replacing its `env()` calls. Edit that file directly after installation, then run `php artisan config:clear` if configuration was cached.

In `v1.0.0-beta.4`, also check `theme.colors.light.primary` and `theme.colors.dark.primary`: if either contains `var(--primary-600]`, correct it to `var(--primary-600)`.

### Assets are missing

Republish the package assets and reload the page:

```bash
php artisan aura:publish
```

### Media URLs are broken

Create the public storage link and verify that PHP GD is enabled:

```bash
php artisan storage:link
php -m | grep -i '^gd$'
```

### A new resource is missing from the sidebar

The public beta caches navigation. Clear the application cache after adding a resource, then reload the admin panel:

```bash
php artisan cache:clear
```

<a id="without-teams"></a>

## Install without teams

After installing the Composer package, run these commands instead of `aura:install`. Configuration and migrations run in separate PHP processes, so the beta uses the selected teams setting when it creates the schema.

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

Keep `teams` set to `false` in `config/aura.php`. Changing the teams setting after installation requires a schema migration, not just a configuration change.

<a id="deployment"></a>

## Deployment

Commit the published Aura configuration and migrations with the application. During deployment, run `php artisan migrate --force`. After updating the Aura package, republish its assets with `php artisan aura:publish`.

## Next steps

- [Quick Start](/docs/quick-start)
- [Resources](/docs/resources)
- [Fields](/docs/fields)
- [Configuration](/docs/configuration)
