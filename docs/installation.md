# Installation

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
- [Available Artisan Commands](#available-commands)
- [Next Steps](#next-steps)

<a name="requirements"></a>
## Requirements

### System Requirements

| Component | Version | Notes |
|-----------|---------|-------|
| **PHP** | >= 8.4 | With extensions: BCMath, Ctype, JSON, Mbstring, OpenSSL, PDO, Tokenizer, XML |
| **Laravel** | 12.x or 13.x | Fresh or existing installation |
| **Livewire** | 4.x | Livewire 3 is not supported |
| **Composer** | >= 2.0 | Latest version recommended |
| **Database** | MySQL 8.0+, PostgreSQL 12+, SQLite 3.8.8+, SQL Server 2017+ | MySQL/PostgreSQL recommended for production |
| **Node.js** | >= 20.19 or >= 22.12 | For asset compilation (optional) |
| **NPM/Yarn** | Latest | For frontend dependencies |

### PHP Extensions

```bash
# Check required PHP extensions
php -m | grep -E 'bcmath|ctype|json|mbstring|openssl|pdo|tokenizer|xml|gd|imagick'
```

> **Pro Tip**: Aura CMS uses the `intervention/image` package (v2.7 or v3.0) for image processing. Install either the GD or ImageMagick PHP extension. ImageMagick provides better quality for image manipulation.

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
composer require eminiarts/aura-cms:1.0.0-beta.3

# Publish, configure, migrate, and create the first administrator
php artisan aura:install

# Start development server
php artisan serve

# Visit http://localhost:8000/admin
```

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
php artisan --version  # Should be 12.x or 13.x

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
composer require eminiarts/aura-cms:1.0.0-beta.3

# If you encounter memory issues
COMPOSER_MEMORY_LIMIT=-1 composer require eminiarts/aura-cms:1.0.0-beta.3
```

### Step 4: Publish and Configure Aura

For the guided path, run the interactive installer and skip to [Step 7](#step-7-verify-installation):

```bash
php artisan aura:install
```

The installer publishes the configuration, migrations, and assets; connects the User model; runs the migrations; and creates the first administrator as a Global Admin by default.

For CI or scripted installations, pass the configuration and administrator values explicitly:

```bash
php artisan aura:install --no-interaction \
    --teams=true \
    --registration=false \
    --admin-name="Aura Admin" \
    --admin-email="admin@example.com" \
    --admin-password="use-a-secret-value"
```

Use `--no-admin` to skip creating the administrator or `--no-global-admin` to create it without instance-level Global Admin access. For a customized manual installation, run the underlying commands below instead.

Publish the package configuration, then run Aura's interactive configuration command:

```bash
php artisan vendor:publish --tag=aura-config
php artisan aura:install-config
```

The command asks about teams, feature flags, registration, and theme settings. Review the resulting `config/aura.php` before continuing.

### Step 5: Connect the User Model and Database

```bash
php artisan aura:extend-user-model
php artisan vendor:publish --tag=aura-migrations
php artisan migrate
```

The first command updates `App\Models\User` to extend Aura's user resource. Publishing the migrations copies Aura's timestamped migration into your application's `database/migrations` directory; `migrate` then creates Aura-owned tables and augments compatible host tables without taking ownership of those host tables.

### Step 6: Publish Assets and Create an Administrator

```bash
php artisan aura:publish
php artisan aura:user
```

The user command creates the first super administrator and, when teams are enabled, its initial team.

### Step 7: Verify Installation

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
docker-compose exec app composer require eminiarts/aura-cms:1.0.0-beta.3
docker-compose exec app php artisan vendor:publish --tag=aura-config
docker-compose exec app php artisan aura:install-config
docker-compose exec app php artisan aura:extend-user-model
docker-compose exec app php artisan vendor:publish --tag=aura-migrations
docker-compose exec app php artisan migrate
docker-compose exec app php artisan aura:publish
docker-compose exec app php artisan aura:user

# Access at http://localhost:8080/admin
```

> **Pro Tip**: Use Docker volumes for persistent storage of uploads and ensure proper permissions for the `storage` and `bootstrap/cache` directories.

<a name="configuration-during-installation"></a>
## Configuration During Installation

The `aura:install-config` command allows you to configure Aura CMS during installation:

### Published Configuration Files

The installer publishes two configuration files:

1. **`config/aura.php`** - Main configuration
   - Admin path and domain
   - Teams setting
   - Theme settings
   - Feature toggles
   - Authentication options
   - Media settings

2. **`config/aura-settings.php`** - Application settings
   - Resource paths and namespaces (`App\Aura\Resources`)
   - Field paths and namespaces (`App\Aura\Fields`)
   - Widget registration
   - Middleware configuration

### Teams Configuration

```
Do you want to use teams? (yes/no) [yes]:
```

- **Yes**: Enables multi-tenant functionality with team-based data isolation
- **No**: Single-tenant application without team scoping

> **Important**: Changing the teams setting later requires running `php artisan migrate:fresh` to rebuild the database schema

### Features Configuration

```
Do you want to modify the default features? (yes/no) [no]:
```

If yes, you can toggle:
- **global_search**: Quick navigation with keyboard shortcut (⇧⌘K)
- **bookmarks**: Save frequently accessed pages
- **last_visited_pages**: Track recent pages
- **notifications**: Enable notification system
- **plugins**: Enable plugin system
- **settings**: Enable settings page
- **profile**: Enable user profile page
- **create_resource**: Allow creating new resources
- **resource_view**: Enable resource view pages
- **resource_edit**: Enable resource editing
- **resource_editor**: Visual resource editor (enabled by default only in local environment)
- **custom_tables_for_resources**: Use custom tables instead of posts/meta tables (advanced)

### Theme Configuration

```
Do you want to modify the default theme? (yes/no) [no]:
```

If yes, you can customize:

**Color Palette** (`color-palette`):
- Options: `aura`, `red`, `orange`, `amber`, `yellow`, `lime`, `green`, `emerald`, `teal`, `cyan`, `sky`, `blue`, `indigo`, `violet`, `purple`, `fuchsia`, `pink`, `rose`, `mountain-meadow`, `sandal`, `slate`, `gray`, `zinc`, `neutral`, `stone`

**Gray Color Palette** (`gray-color-palette`):
- Options: `slate`, `purple-slate`, `gray`, `zinc`, `neutral`, `stone`, `blue`, `smaragd`, `dark-slate`, `blackout`

**Dark Mode** (`darkmode-type`):
- Options: `auto`, `light`, `dark`

**Sidebar Size** (`sidebar-size`):
- Options: `standard`, `compact`

**Sidebar Type** (`sidebar-type`):
- Options: `primary`, `light`, `dark`

<a name="post-installation-setup"></a>
## Post-Installation Setup

### Essential Configuration

After installation, optimize your setup for production:

#### 1. Storage Permissions

```bash
# Set proper permissions
chmod -R 775 storage bootstrap/cache
chown -R www-data:www-data storage bootstrap/cache

# Create storage link for public access
php artisan storage:link
```

#### 2. Queue Configuration

Aura CMS uses queues for:
- Image processing and thumbnail generation
- Email notifications
- Bulk operations

Configure your queue driver in `.env`:

```dotenv
QUEUE_CONNECTION=redis  # or database, sqs, etc.

# For Redis
REDIS_HOST=127.0.0.1
REDIS_PASSWORD=null
REDIS_PORT=6379

# Start queue worker
php artisan queue:work --queue=default,thumbnails
```

#### 3. Media Storage

Configure media storage for production:

```dotenv
# For S3/Cloud Storage
FILESYSTEM_DISK=s3
AWS_ACCESS_KEY_ID=your-key
AWS_SECRET_ACCESS_KEY=your-secret
AWS_DEFAULT_REGION=us-east-1
AWS_BUCKET=your-bucket

# Update config/aura.php
'media' => [
    'disk' => env('FILESYSTEM_DISK', 'public'),
    'path' => 'media',
]
```

#### 4. Cache Configuration

```bash
# Cache configuration for production
php artisan config:cache
php artisan route:cache
php artisan view:cache
php artisan icons:cache  # If using Blade Icons

# Clear all caches when needed
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

#### 5. Email Configuration

```dotenv
MAIL_MAILER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_ENCRYPTION=null
MAIL_FROM_ADDRESS="hello@example.com"
MAIL_FROM_NAME="${APP_NAME}"
```

### Security Hardening

#### 1. Environment File

```bash
# Ensure .env is not accessible
chmod 600 .env

# Generate application key if not set
php artisan key:generate
```

#### 2. HTTPS Configuration

```nginx
# Nginx configuration for HTTPS
server {
    listen 443 ssl http2;
    server_name yourdomain.com;
    
    ssl_certificate /path/to/certificate.crt;
    ssl_certificate_key /path/to/private.key;
    
    # Force HTTPS in Laravel
    # Add to AppServiceProvider boot method
    if (config('app.env') === 'production') {
        URL::forceScheme('https');
    }
}
```

#### 3. Security Headers

Add to your web server configuration:

```nginx
add_header X-Frame-Options "SAMEORIGIN";
add_header X-Content-Type-Options "nosniff";
add_header X-XSS-Protection "1; mode=block";
add_header Referrer-Policy "strict-origin-when-cross-origin";
```

<a name="troubleshooting"></a>
## Troubleshooting

### Common Installation Issues

#### 1. Composer Memory Limit

```bash
# Error: Allowed memory size exhausted
COMPOSER_MEMORY_LIMIT=-1 composer require eminiarts/aura-cms:1.0.0-beta.3
```

#### 2. Permission Denied Errors

```bash
# Fix storage permissions
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache
```

#### 3. Migration Errors

```bash
# Error: Table already exists
php artisan migrate:fresh --seed

# Error: Foreign key constraint fails
# Ensure you're using InnoDB engine for MySQL
DB_ENGINE=InnoDB
```

#### 4. Missing PHP Extensions

```bash
# Check missing extensions
php -m | grep -E 'bcmath|gd|imagick'

# Install missing extensions (Ubuntu/Debian)
sudo apt-get install php8.2-bcmath php8.2-gd php8.2-imagick

# Install missing extensions (macOS with Homebrew)
brew install php@8.2-gd php@8.2-imagick
```

#### 5. Assets Not Loading

```bash
# Republish assets using Aura's publish command
php artisan aura:publish

# Or manually republish
php artisan vendor:publish --tag=aura-assets --force

# Clear view cache
php artisan view:clear

# Check symbolic link
php artisan storage:link
```

#### 6. Login Issues

```bash
# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear

# Regenerate key
php artisan key:generate

# Check session configuration
# Ensure SESSION_DOMAIN matches your domain
SESSION_DOMAIN=.yourdomain.com
```

### Database-Specific Issues

#### MySQL 8.0 Authentication

```sql
-- If you get authentication errors with MySQL 8
ALTER USER 'username'@'localhost' IDENTIFIED WITH mysql_native_password BY 'password';
FLUSH PRIVILEGES;
```

#### PostgreSQL Configuration

```dotenv
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=aura_cms
DB_USERNAME=postgres
DB_PASSWORD=password
DB_SCHEMA=public
```

### Performance Optimization

#### 1. Enable OPcache

```ini
; php.ini
opcache.enable=1
opcache.memory_consumption=256
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0
```

#### 2. Redis Configuration

```bash
# Install Redis PHP extension
pecl install redis

# Configure Laravel for Redis sessions and cache
SESSION_DRIVER=redis
CACHE_DRIVER=redis
```

#### 3. Database Indexing

Aura CMS migrations automatically create optimized indexes for:
- Meta table lookups (composite index on `metable_id`, `key`, `value`)
- Resource type queries (index on `type`, `status`, `created_at`)
- Team scoping (composite index on `team_id`, `type` when teams enabled)
- Role and permission slug lookups

No additional commands are needed - indexes are created during migration.

<a name="deployment"></a>
## Deployment

### Production Deployment Checklist

#### Pre-deployment

- [ ] Set `APP_ENV=production` and `APP_DEBUG=false`
- [ ] Configure production database
- [ ] Set up Redis/cache backend
- [ ] Configure queue workers
- [ ] Set up SSL certificates
- [ ] Configure backups

#### Deployment Steps

```bash
# 1. Upload code to server
git pull origin main

# 2. Install dependencies (no dev)
composer install --optimize-autoloader --no-dev

# 3. Run migrations
php artisan migrate --force

# 4. Cache everything
php artisan config:cache
php artisan route:cache
php artisan view:cache

# 5. Restart services
php artisan queue:restart
sudo service php8.2-fpm restart
sudo service nginx restart
```

#### Popular Hosting Platforms

**Laravel Forge**
- Automated deployments
- Queue worker management
- SSL certificates
- Database backups

**Digital Ocean App Platform**
```yaml
# app.yaml
name: aura-cms
services:
- name: web
  environment_slug: php
  github:
    branch: main
    deploy_on_push: true
  build_command: composer install --optimize-autoloader --no-dev
  run_command: php artisan serve --host=0.0.0.0 --port=8080
```

**Heroku**
```json
// composer.json
"scripts": {
    "post-install-cmd": [
        "php artisan aura:publish"
    ]
}
```

### Monitoring

Set up monitoring for:
- Application errors (Sentry, Bugsnag)
- Performance metrics (New Relic, Datadog)
- Uptime monitoring (Pingdom, UptimeRobot)
- Log aggregation (Papertrail, Loggly)

<a name="available-commands"></a>
## Available Artisan Commands

Aura CMS provides several artisan commands for development and maintenance:

### Installation & Setup
| Command | Description |
|---------|-------------|
| `php artisan aura:install-config` | Configure Aura settings interactively |
| `php artisan aura:extend-user-model` | Extend User model with AuraUser |
| `php artisan aura:user` | Create a new super admin user |
| `php artisan aura:publish` | Republish all Aura assets |

### Resource Development
| Command | Description |
|---------|-------------|
| `php artisan aura:resource` | Create a new Aura resource |
| `php artisan aura:field` | Create a new custom field type |
| `php artisan aura:plugin` | Create a new Aura plugin |
| `php artisan aura:create-resource-migration` | Create migration for a resource |
| `php artisan aura:create-resource-permissions` | Generate permissions for a resource |
| `php artisan aura:create-resource-factory` | Create a factory for a resource |

### Data Migration
| Command | Description |
|---------|-------------|
| `php artisan aura:migrate-from-posts-to-custom-table` | Migrate data from posts table to custom table |
| `php artisan aura:transfer-from-posts-to-custom-table` | Transfer resource data to custom table |
| `php artisan aura:migrate-post-meta-to-meta` | Migrate post meta to meta table |

### Utilities
| Command | Description |
|---------|-------------|
| `php artisan aura:customize` | Customize a resource page (copy its Blade view and/or generate a custom Livewire component) |
| `php artisan aura:database-to-resources` | Generate resources from database tables |
| `php artisan aura:transform-table-to-resource` | Transform a database table into a resource |
| `php artisan aura:layout` | Generate Aura layout files |

<a name="next-steps"></a>
## Next Steps

Congratulations! Aura CMS is now installed. Here's what to do next:

1. 📖 **[Configuration Guide](configuration.md)** - Deep dive into all configuration options
2. 🚀 **[Quick Start Tutorial](quick-start.md)** - Build your first Aura application
3. 📚 **[Understanding Resources](resources.md)** - Learn the core concepts
4. 🎨 **[Customizing Appearance](themes.md)** - Make it yours

### Additional Resources

- **Community Forum**: Get help and share experiences
- **GitHub Issues**: Report bugs or request features
- **YouTube Channel**: Video tutorials and tips
- **Discord Server**: Real-time community support

> **Need Help?** Check our [Troubleshooting Guide](#troubleshooting) or visit the community forum for assistance.

---

**Happy building with Aura CMS!** 🚀
