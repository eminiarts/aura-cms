# Installation

Aura CMS is a Laravel-based Content Management System that leverages the power of the TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire). This guide will walk you through the installation process to get Aura CMS up and running in your Laravel application.

<a name="requirements"></a>
## Requirements

Before you begin, ensure your environment meets the following requirements:

- **Laravel**: `>= 11.x`
- **PHP**: `>= 8.2`
- **Composer**: Installed globally
- **Database**: MySQL, PostgreSQL, SQLite, or SQL Server
- **Node.js and NPM**: For asset compilation (optional but recommended)

<a name="installation-steps"></a>
## Installation Steps

<a name="install-laravel"></a>
### 1. Install Laravel (If Not Already Installed)

If you don't have a Laravel application set up, create a new one:

```bash
laravel new aura-app # or composer create-project laravel/laravel aura-app
cd aura-app
```

<a name="require-aura-cms-via-composer"></a>
### 2. Require Aura CMS via Composer

Add Aura CMS to your Laravel project using Composer:

```bash
composer require eminiarts/aura-cms
```

<a name="run-aura-install-command"></a>
### 3. Run the Aura Install Command

Execute the installation command to set up Aura CMS:

```bash
php artisan aura:install
```

This command will:

- Publish configuration files.
- Publish assets.
- Publish and run migrations.
- Extend the User model.
- Allow you to modify the Aura configuration.
- Allow you to create an admin user.

<a name="installation-prompts"></a>
#### Installation Prompts

During the installation, you will be prompted with several questions:

1. **Modify Aura Configuration?**
   - **Yes**: You'll be able to customize settings like enabling teams, adjusting features, allowing user registration, and theme customization.
   - **No**: Default settings will be used.

2. **Run Migrations?**

   - **Yes**: The necessary database tables will be created immediately.
   - **No**: You can run migrations later with `php artisan migrate`.

3. **Create a User?**

   - **Yes**: You'll create an admin user for logging into the CMS.
   - **No**: You can create a user later with `php artisan aura:user`.

<a name="configure-your-database"></a>
### 4. Configure Your Database

Ensure your `.env` file has the correct database credentials:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=your_database
DB_USERNAME=your_username
DB_PASSWORD=your_password
```

<a name="run-database-migrations"></a>
### 5. Run Database Migrations (If You Didn't During Installation)

If you chose not to run migrations during installation, run them now:

```bash
php artisan migrate
```

<a name="extend-the-user-model"></a>
### 6. Extend the User Model

Update your `User` model to extend Aura's base user model:

```php
<?php
// app/Models/User.php

namespace App\Models;

use Aura\Base\Models\User as AuraUser;

class User extends AuraUser
{
    // You can add custom methods or properties here
}
```

<a name="create-an-admin-user"></a>
### 7. Create an Admin User (If You Didn't During Installation)

If you didn't create a user during the installation, you can create one now:

```bash
php artisan aura:user
```

Follow the prompts to set up the admin user's name, email, and password.

<a name="access-the-aura-cms-admin-panel"></a>
### 8. Access the Aura CMS Admin Panel

Open your web browser and navigate to:

```
http://localhost:8000/admin
```

Log in using the admin credentials you created earlier.

<a name="publishing-configuration-files"></a>
## Additional Configuration

<a name="publishing-configuration-files"></a>
### Publishing Configuration Files (Optional)

If you need to publish the configuration files manually, run:

```bash
php artisan vendor:publish --provider="Aura\Base\AuraServiceProvider"
```

This will publish the `aura.php` configuration file to your `config` directory.

<a name="modifying-the-aura-configuration"></a>
### Modifying the Aura Configuration (Optional)

You can modify the Aura configuration at any time by running:

```bash
php artisan aura:install-config
```

This command allows you to:

- Enable or disable teams (multi-tenancy).
- Modify default features.
- Allow or disallow user registration.
- Customize the default theme.

<a name="configuration-options"></a>
#### Configuration Options

- **Teams**: Enable or disable multi-tenancy support.
- **Features**: Toggle features like global search, bookmarks, notifications, and more.
- **Registration**: Allow or disallow user registration (`AURA_REGISTRATION` in `.env`).
- **Theme Customization**: Choose color palettes, sidebar styles, and dark mode settings.

<a name="environment-variables"></a>
### Environment Variables

Some settings may require updates to your `.env` file, such as enabling user registration:

```dotenv
AURA_REGISTRATION=true
```

<a name="notes"></a>
## Notes

- **Livewire Components**: To use the Aura layout in your Livewire components, extend `aura::components.layout.app`:

  ```php
  public function render()
  {
      return view('livewire.my-component')->layout('aura::components.layout.app');
  }
  ```

- **Multi-Tenancy**: Teams are enabled by default. To disable, set `'teams' => false` in `config/aura.php` or choose "No" when prompted during configuration.

<a name="next-steps"></a>
## Next Steps

Now that Aura CMS is installed, you can start building your application by:

- [Configuring Aura](configuration.md)
- [Understanding Resources](resources.md)
- [Customizing Themes and Views](theme-customization.md)

---
