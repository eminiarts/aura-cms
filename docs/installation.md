# Installation

> 📹 **Video Placeholder**: Complete walkthrough of Aura CMS installation from creating a new Laravel project to accessing the admin panel for the first time

Aura CMS transforms your Laravel application into a powerful content management system with just a few commands. This guide covers multiple installation methods, troubleshooting common issues, and deployment best practices.

## Table of Contents

- [Requirements](#requirements)
- [Quick Installation](#quick-installation)
- [Detailed Installation Steps](#detailed-installation-steps)
- [Docker Installation](#docker-installation)
- [Configuration During Installation](#configuration-during-installation)
- [Post-Installation Setup](#post-installation-setup)
- [Troubleshooting](#troubleshooting)
- [Deployment](#deployment)
- [Next Steps](#next-steps)

<a name="requirements"></a>
## Requirements

### System Requirements

| Component | Version | Notes |
|-----------|---------|-------|
| **PHP** | >= 8.2 | With extensions: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML |
| **Laravel** | >= 11.x | Fresh or existing installation |
| **Composer** | >= 2.0 | Latest version recommended |
| **Database** | MySQL 8.0+, PostgreSQL 12+, SQLite 3.8.8+, SQL Server 2017+ | MySQL/PostgreSQL recommended for production |
| **Node.js** | >= 18.x | For asset compilation (optional but recommended) |
| **NPM/Yarn** | Latest | For frontend dependencies |

### PHP Extensions

```bash
# Check required PHP extensions
php -m | grep -E 'bcmath|ctype|json|mbstring|openssl|pdo|tokenizer|xml|gd|imagick'
```

> **Pro Tip**: For image processing, install either GD or ImageMagick PHP extension. ImageMagick provides better quality for image manipulation.

<a name="quick-installation"></a>
## Quick Installation

For experienced Laravel developers, here's the fastest way to get started:

```bash
# Create new Laravel project
laravel new my-aura-project
cd my-aura-project

# Configure database in .env
# DB_CONNECTION=mysql
# DB_DATABASE=my_aura_db
# DB_USERNAME=root
# DB_PASSWORD=

# Install Aura CMS
composer require eminiarts/aura-cms

# Run interactive installer
php artisan aura:install

# Start development server
php artisan serve

# Visit http://localhost:8000/admin
```

> 📹 **Video Placeholder**: 60-second speed run of Aura CMS installation

<a name="detailed-installation-steps"></a>
## Detailed Installation Steps

### Step 1: Prepare Your Laravel Application

#### Option A: Fresh Laravel Installation

```bash
# Using Laravel installer
laravel new my-aura-project
cd my-aura-project

# Or using Composer
composer create-project laravel/laravel my-aura-project
cd my-aura-project
```

#### Option B: Existing Laravel Application

Ensure your existing application meets the requirements:

```bash
# Check Laravel version
php artisan --version  # Should be >= 11.x

# Update dependencies
composer update

# Clear caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
```

> **Common Pitfall**: If you have existing authentication scaffolding (like Laravel Breeze or Jetstream), remove it first as Aura CMS provides its own authentication system.

### Step 2: Configure Database

Create your database and update `.env`:

```dotenv
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=aura_cms
DB_USERNAME=root
DB_PASSWORD=your_password

# For better performance with Aura CMS
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
```

Test database connection:

```bash
php artisan db:show
```

### Step 3: Install Aura CMS Package

```bash
# Install via Composer
composer require eminiarts/aura-cms

# If you encounter memory issues
COMPOSER_MEMORY_LIMIT=-1 composer require eminiarts/aura-cms
```

### Step 4: Run the Interactive Installer

The installer guides you through the entire setup:

```bash
php artisan aura:install
```

You'll see output like this:

```
 ┌─────────────────────────────────────────────────────────────┐
 │                  Welcome to Aura CMS Setup                   │
 └─────────────────────────────────────────────────────────────┘

 Publishing configuration files...
 Publishing assets...
 Publishing migrations...

 ┌ Aura Configuration ─────────────────────────────────────────┐
 │ Do you want to modify the Aura configuration? (yes/no)     │
 └─────────────────────────────────────────────────────────────┘
 > 
```

#### Installation Options Explained

1. **Extend User Model** (Automatic)
   - The installer automatically updates your User model
   - Adds necessary traits and relationships

2. **Modify Configuration** (Recommended: Yes)
   - **Teams**: Enable multi-tenancy support
   - **Features**: Toggle individual features
   - **Registration**: Allow public user registration
   - **Theme**: Customize colors and appearance

3. **Run Migrations** (Recommended: Yes)
   - Creates all necessary database tables
   - Includes users, teams, posts, meta, media tables

4. **Create Admin User** (Recommended: Yes)
   - Sets up your first super admin account
   - You'll need this to access the admin panel

### Step 5: Verify Installation

After installation, verify everything is working:

```bash
# Check installed routes
php artisan route:list --name=aura

# Verify configuration
php artisan config:show aura

# Test the application
php artisan serve
```

Visit `http://localhost:8000/admin` and log in with your admin credentials.

<a name="docker-installation"></a>
## Docker Installation

Aura CMS works perfectly with Docker. Here's a complete Docker setup:

### Docker Compose Configuration

Create `docker-compose.yml`:

```yaml
version: '3.8'

services:
  app:
    build:
      context: .
      dockerfile: Dockerfile
    image: aura-cms
    container_name: aura-cms-app
    restart: unless-stopped
    working_dir: /var/www
    volumes:
      - ./:/var/www
      - ./docker/php/local.ini:/usr/local/etc/php/conf.d/local.ini
    networks:
      - aura-network

  webserver:
    image: nginx:alpine
    container_name: aura-cms-webserver
    restart: unless-stopped
    ports:
      - "8080:80"
    volumes:
      - ./:/var/www
      - ./docker/nginx/conf.d:/etc/nginx/conf.d
    networks:
      - aura-network

  db:
    image: mysql:8.0
    container_name: aura-cms-db
    restart: unless-stopped
    environment:
      MYSQL_DATABASE: ${DB_DATABASE}
      MYSQL_ROOT_PASSWORD: ${DB_PASSWORD}
      MYSQL_PASSWORD: ${DB_PASSWORD}
      MYSQL_USER: ${DB_USERNAME}
    ports:
      - "3307:3306"
    volumes:
      - dbdata:/var/lib/mysql
      - ./docker/mysql/my.cnf:/etc/mysql/my.cnf
    networks:
      - aura-network

  redis:
    image: redis:alpine
    container_name: aura-cms-redis
    restart: unless-stopped
    ports:
      - "6380:6379"
    networks:
      - aura-network

networks:
  aura-network:
    driver: bridge

volumes:
  dbdata:
    driver: local
```

### Dockerfile

Create `Dockerfile`:

```dockerfile
FROM php:8.2-fpm

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    libwebp-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    vim \
    unzip \
    git \
    curl \
    libzip-dev \
    libonig-dev \
    libxml2-dev

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install pdo_mysql mbstring zip exif pcntl bcmath
RUN docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install gd

# Install Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Set working directory
WORKDIR /var/www

# Copy application
COPY . /var/www

# Install dependencies
RUN composer install --optimize-autoloader --no-dev

# Set permissions
RUN chown -R www-data:www-data /var/www/storage /var/www/bootstrap/cache

USER www-data

EXPOSE 9000
CMD ["php-fpm"]
```

### Docker Installation Steps

```bash
# Clone or create your project
git clone your-project.git aura-cms-docker
cd aura-cms-docker

# Copy environment file
cp .env.example .env

# Update .env for Docker
# DB_HOST=db
# REDIS_HOST=redis

# Build and start containers
docker-compose up -d --build

# Install Aura CMS in container
docker-compose exec app composer require eminiarts/aura-cms
docker-compose exec app php artisan aura:install

# Access at http://localhost:8080/admin
```

> **Pro Tip**: Use Docker volumes for persistent storage of uploads and ensure proper permissions for the `storage` and `bootstrap/cache` directories.

<a name="configuration-during-installation"></a>
## Configuration During Installation

The interactive installer allows you to configure Aura CMS during installation:

### Teams Configuration

```
┌─────────────────────────────────────────────────────────────┐
│ Do you want to use teams? (yes/no) [yes]:                  │
└─────────────────────────────────────────────────────────────┘
```

- **Yes**: Enables multi-tenant functionality
- **No**: Single-tenant application

> **Important**: Changing teams setting later requires fresh migration

### Features Configuration

```
┌─────────────────────────────────────────────────────────────┐
│ Do you want to modify the default features? (yes/no) [no]: │
└─────────────────────────────────────────────────────────────┘
```

If yes, you can toggle:
- Global Search (⇧⌘K)
- Bookmarks
- Recent Pages
- Notifications
- Settings Page
- Resource Editor
- And more...

### Theme Configuration

```
┌─────────────────────────────────────────────────────────────┐
│ Select value for 'color-palette':                           │
│ > aura                                                      │
│   blue                                                      │
│   green                                                     │
│   red                                                       │
│   purple                                                    │
│   ...                                                       │
└─────────────────────────────────────────────────────────────┘
```

Choose from 20+ color palettes and configure:
- Primary color scheme
- Gray palette
- Dark mode behavior
- Sidebar style
- Login page background

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
