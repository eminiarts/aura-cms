# Troubleshooting & FAQ

This guide helps you resolve common issues with Aura CMS, provides debugging techniques, and answers frequently asked questions.

## Table of Contents

1. [Common Issues](#common-issues)
2. [Common Gotchas](#common-gotchas)
3. [Installation Problems](#installation-problems)
4. [Database Issues](#database-issues)
5. [Authentication & Permissions](#authentication--permissions)
6. [Resource & Field Errors](#resource--field-errors)
7. [Livewire Component Issues](#livewire-component-issues)
8. [Media & File Upload Problems](#media--file-upload-problems)
9. [Performance Issues](#performance-issues)
10. [Testing Issues](#testing-issues)
11. [Debugging Guide](#debugging-guide)
12. [Error Messages Reference](#error-messages-reference)
13. [Upgrade Procedures](#upgrade-procedures)
14. [Migration Troubleshooting](#migration-troubleshooting)
15. [Frequently Asked Questions](#frequently-asked-questions)

## Common Issues

### Issue: "Aura CMS assets are not published"

**Error Message:**
```
RuntimeException: Aura CMS assets are not published. Please run: php artisan aura:publish
```

**Solution:**
```bash
# Publish assets
php artisan aura:publish

# Or force republish
php artisan vendor:publish --tag=aura-assets --force

# Clear views
php artisan view:clear
```

### Issue: Blank Page After Installation

**Possible Causes:**
- PHP version mismatch
- Missing extensions
- Permission issues
- Cache problems

**Solutions:**
```bash
# Check PHP version (8.2+ required)
php -v

# Check required extensions
php -m | grep -E 'bcmath|ctype|curl|dom|fileinfo|json|mbstring|openssl|pcre|pdo|tokenizer|xml|gd|imagick'

# Fix permissions
sudo chown -R $USER:www-data storage bootstrap/cache
sudo chmod -R 775 storage bootstrap/cache

# Clear all caches
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
```

### Issue: Styles Not Loading Correctly

**Solutions:**
```bash
# Rebuild assets
npm install
npm run build

# Create storage link
php artisan storage:link

# Check Vite configuration
npm run dev # For development
```

## Common Gotchas

These are important architectural patterns in Aura CMS that can cause unexpected behavior if not understood:

### 1. Team Scope Filtering

Most models in Aura CMS use `TeamScope` which automatically filters records by the user's current team. This can cause records to appear "missing" when they exist in the database.

**Problem:** Records exist in database but queries return empty results

**Solution:**
```php
use Aura\Base\Models\Scopes\TeamScope;

// Bypass TeamScope when you need all records
$allRecords = YourResource::withoutGlobalScope(TeamScope::class)->get();

// Or for specific queries
$record = YourResource::withoutGlobalScope(TeamScope::class)
    ->where('id', $id)
    ->first();
```

**Important:** When changing a user's team, you must clear the cached team ID:
```php
use Illuminate\Support\Facades\Cache;

// After updating user's team
$user->update(['current_team_id' => $newTeamId]);

// Clear the TeamScope cache
Cache::forget("user_{$user->id}_current_team_id");
```

### 2. Type Column for Single-Table Inheritance

The `posts` table uses a `type` column for single-table inheritance. Resources sharing the posts table are differentiated by this column.

**Problem:** Queries return wrong resource types or unexpected records

**Solution:**
```php
// The TypeScope automatically filters by type
// If you need to query across types:
use Aura\Base\Models\Scopes\TypeScope;

$allPosts = Post::withoutGlobalScope(TypeScope::class)->get();
```

### 3. Meta Fields Storage

Resources can store field data in a separate `meta` table instead of directly in columns. Use the `usesMeta()` method to check.

**Problem:** Field values not saving or returning null

**Solution:**
```php
// Check if resource uses meta storage
if ($resource->usesMeta()) {
    // Values are stored in meta table, not the main table
    // Access via the model's meta relationship
}

// For custom meta table, define it in your model:
class Product extends Model
{
    protected $metaTable = 'product_meta';
}
```

### 4. Resource Editor Environment Restriction

The Resource Editor is automatically disabled outside of local environments for security.

**Configuration in `config/aura.php`:**
```php
'features' => [
    'resource_editor' => config('app.env') == 'local' ? true : false,
],
```

**Problem:** Resource Editor not visible in staging/production

**Solution:** This is intentional. To enable in other environments (not recommended for production):
```php
// In config/aura.php
'resource_editor' => env('AURA_RESOURCE_EDITOR', false),
```

## Installation Problems

### Composer Memory Limit

**Error:**
```
Fatal error: Allowed memory size of X bytes exhausted
```

**Solution:**
```bash
# Increase memory limit for composer
COMPOSER_MEMORY_LIMIT=-1 composer require eminiarts/aura-cms
```

### Package Discovery Failed

**Error:**
```
Script @php artisan package:discover --ansi handling the post-autoload-dump event returned with error code 1
```

**Solutions:**
```bash
# Clear composer cache
composer clear-cache

# Update composer
composer self-update

# Remove vendor and reinstall
rm -rf vendor composer.lock
composer install
```

### Missing PHP Extensions

**Error:**
```
Your requirements could not be resolved to an installable set of packages.
```

**Check and Install Extensions:**

**Ubuntu/Debian:**
```bash
sudo apt-get update
sudo apt-get install php8.2-bcmath php8.2-gd php8.2-mbstring php8.2-xml php8.2-curl php8.2-zip
```

**macOS (Homebrew):**
```bash
brew install php@8.2
pecl install imagick
```

**Windows:**
Enable extensions in `php.ini`:
```ini
extension=bcmath
extension=gd
extension=mbstring
extension=openssl
extension=pdo_mysql
```

## Database Issues

### Migration Errors

**Error: "Table already exists"**
```bash
# Option 1: Fresh migration (WARNING: Deletes all data)
php artisan migrate:fresh --seed

# Option 2: Rollback and remigrate
php artisan migrate:rollback
php artisan migrate
```

**Error: "Foreign key constraint fails"**
```php
// In your migration
Schema::disableForeignKeyConstraints();
// ... your migrations
Schema::enableForeignKeyConstraints();
```

### MySQL 8.0 Authentication

**Error:**
```
SQLSTATE[HY000] [2054] The server requested authentication method unknown to the client
```

**Solution:**
```sql
ALTER USER 'your_user'@'localhost' IDENTIFIED WITH mysql_native_password BY 'your_password';
FLUSH PRIVILEGES;
```

### PostgreSQL Connection

**Error:**
```
SQLSTATE[08006] [7] FATAL: password authentication failed for user
```

**Solution in `.env`:**
```env
DB_CONNECTION=pgsql
DB_HOST=127.0.0.1
DB_PORT=5432
DB_DATABASE=aura_cms
DB_USERNAME=postgres
DB_PASSWORD=your_password
DB_SCHEMA=public
```

### SQLite Issues

**Error:**
```
SQLSTATE[HY000]: General error: 1 no such table: posts
```

**Solution:**
```bash
# Create database file
touch database/database.sqlite

# Run migrations
php artisan migrate
```

## Authentication & Permissions

### Cannot Login

**Symptoms:**
- Login form submits but redirects back
- No error messages
- Session not persisting

**Solutions:**
```bash
# Regenerate application key
php artisan key:generate

# Clear all caches
php artisan cache:clear
php artisan config:cache
php artisan route:cache

# Check session configuration
# In .env
SESSION_DRIVER=file # or database, redis
SESSION_DOMAIN=yourdomain.com
SESSION_SECURE_COOKIE=true # Only if using HTTPS
```

### 403 Forbidden Errors

**Error:**
```
403 | This action is unauthorized.
```

**Common Causes:**
1. User lacks required permissions
2. Resource policy denying access
3. Team scope restrictions

**Solutions:**
```php
// Check user permissions
$user = auth()->user();
dd($user->getAllPermissions());

// Regenerate permissions
php artisan aura:permissions

// Check if user is super admin
if (!$user->isSuperAdmin()) {
    // Assign super admin role
    $user->assignRole('Super Admin');
}
```

### Team Scope Issues

**Problem:** User can't see resources from their team

**Solution:**
```php
// Ensure user has current team set
$user = auth()->user();
if (!$user->current_team_id && $user->teams->isNotEmpty()) {
    $user->switchTeam($user->teams->first());
}

// Check team scope in resource
class YourResource extends Resource
{
    protected static function booted()
    {
        parent::booted();
        
        // Only apply team scope if teams enabled
        if (config('aura.teams')) {
            static::addGlobalScope(new TeamScope);
        }
    }
}
```

## Resource & Field Errors

### InvalidMetaTableException

**Error:**
```
InvalidMetaTableException: You need to define a custom meta table for this model.
```

**Solution:**
```php
// In your model
class Product extends Model
{
    protected $metaTable = 'product_meta';
    
    // Or use a custom table instead
    protected $table = 'products';
    protected $customTable = true;
}
```

### "Function getFields() not found"

**Error when using Resource Editor:**
```
Function getFields() not found
```

**Solution:**
Ensure your resource has the proper structure:
```php
namespace App\Aura\Resources;

use Aura\Base\Resource;

class Product extends Resource
{
    public static string $model = \App\Models\Product::class;
    
    public static function getFields()
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required|max:255',
            ],
            // More fields...
        ];
    }
}
```

### Field Validation Not Working

**Problem:** Validation rules are ignored

**Solution:**
```php
// Ensure field has validation key
[
    'name' => 'Email',
    'type' => 'Aura\\Base\\Fields\\Email',
    'validation' => 'required|email|unique:users,email',
    'validation_messages' => [
        'required' => 'Email address is required',
        'email' => 'Please enter a valid email',
        'unique' => 'This email is already taken',
    ],
]

// For conditional validation
'validation' => function($form) {
    return $form['type'] === 'business' 
        ? 'required|email|unique:businesses,email'
        : 'required|email';
},
```

### Conditional Fields Not Showing

**Problem:** Fields with displayIf/hideIf not working

**Solution:**
```php
// Check field slugs match exactly
[
    'name' => 'Product Type',
    'type' => 'Aura\\Base\\Fields\\Select',
    'slug' => 'product_type', // Note the slug
    'options' => [
        'physical' => 'Physical Product',
        'digital' => 'Digital Product',
    ],
],
[
    'name' => 'Weight',
    'type' => 'Aura\\Base\\Fields\\Number',
    'slug' => 'weight',
    'conditional_logic' => [
        [
            'field' => 'product_type', // Must match slug above
            'operator' => '=',
            'value' => 'physical',
        ],
    ],
],
```

## Livewire Component Issues

### Component Not Found

**Error:**
```
Unable to find component: [component-name]
```

**Solutions:**
```bash
# Clear component cache
php artisan livewire:discover

# Check component registration
php artisan livewire:list

# Ensure proper namespace
namespace App\Http\Livewire; // Default
namespace App\Livewire; // Laravel 11+
```

### Wire:model Not Updating

**Problem:** Form inputs not binding to component properties

**Solutions:**
```blade
<!-- Use wire:model.live for real-time updates -->
<input type="text" wire:model.live="name">

<!-- Use wire:model.blur for updates on blur -->
<input type="text" wire:model.blur="email">

<!-- For nested properties -->
<input type="text" wire:model="form.fields.title">

<!-- With debouncing -->
<input type="text" wire:model.live.debounce.500ms="search">
```

### File Upload Errors

**Error:**
```
Livewire encountered corrupt data when trying to hydrate the [component] component
```

**Solutions:**
```php
// In component
use Livewire\WithFileUploads;

class MediaUploader extends Component
{
    use WithFileUploads;
    
    public $file;
    
    protected $rules = [
        'file' => 'required|file|max:10240', // 10MB max
    ];
    
    public function updatedFile()
    {
        $this->validateOnly('file');
    }
}
```

**In config/livewire.php:**
```php
'temporary_file_upload' => [
    'disk' => 'local',
    'rules' => 'file|mimes:png,jpg,pdf|max:10240',
    'directory' => 'livewire-tmp',
    'middleware' => 'throttle:60,1',
    'preview_mimes' => [
        'png', 'gif', 'bmp', 'svg', 'wav', 'mp4',
        'mov', 'avi', 'wmv', 'mp3', 'm4a', 'jpg', 'jpeg',
        'mpga', 'webp', 'wma',
    ],
    'max_upload_time' => 5, // Minutes
],
```

## Media & File Upload Problems

### Thumbnail Generation Failed

**Error in logs:**
```
Failed to generate thumbnail: Unable to read image from path
```

**Solutions:**
```bash
# Install image processing libraries
# Ubuntu/Debian
sudo apt-get install imagemagick php-imagick

# macOS
brew install imagemagick
pecl install imagick

# Check PHP memory limit
php -i | grep memory_limit
# Increase if needed in php.ini
memory_limit = 256M
```

### Large File Upload Timeout

**Error:**
```
413 Request Entity Too Large
```

**Solutions:**

**PHP Configuration (`php.ini`):**
```ini
upload_max_filesize = 50M
post_max_size = 55M
max_execution_time = 300
max_input_time = 300
```

**Nginx Configuration:**
```nginx
client_max_body_size 50M;
client_body_timeout 300s;
```

**Apache Configuration:**
```apache
LimitRequestBody 52428800
```

### Storage Permission Denied

**Error:**
```
Unable to write to storage/app/public
```

**Solution:**
```bash
# Fix storage permissions
sudo chown -R www-data:www-data storage/app/public
sudo chmod -R 755 storage/app/public

# Recreate symlink
php artisan storage:link
```

## Performance Issues

### Slow Page Load

**Diagnosis:**
```php
// Enable query log
DB::enableQueryLog();

// Your operation
$products = Product::with('category')->get();

// Check queries
dd(DB::getQueryLog());
```

**Common Solutions:**

1. **Enable Caching:**
```bash
# Use Redis
composer require predis/predis
# Set in .env
CACHE_DRIVER=redis
SESSION_DRIVER=redis
```

2. **Optimize Queries:**
```php
// Bad - N+1 problem
$posts = Post::all();
foreach ($posts as $post) {
    echo $post->author->name;
}

// Good - Eager loading
$posts = Post::with('author')->get();
```

3. **Add Indexes:**
```php
Schema::table('posts', function (Blueprint $table) {
    $table->index(['type', 'status', 'created_at']);
});
```

### High Memory Usage

**Solutions:**
```php
// Use chunking for large datasets
Product::chunk(100, function ($products) {
    foreach ($products as $product) {
        // Process product
    }
});

// Use cursor for minimal memory
foreach (Product::cursor() as $product) {
    // Process one at a time
}
```

## Testing Issues

### Aura Facade Pollution Between Tests

**Problem:** Tests pass individually but fail when run together

The Aura facade maintains state that can leak between tests, causing unexpected failures.

**Solution:**
```php
// In tests/Pest.php or your test setup
uses()->afterEach(function () {
    // Reset the Aura facade to its original state
    app()->forgetInstance(\Aura\Base\Aura::class);
    app()->singleton(\Aura\Base\Aura::class);
    \Aura\Base\Facades\Aura::clearResolvedInstances();
})->in('Feature');
```

### TeamScope Cache Issues in Tests

**Problem:** User's team changes don't reflect in queries during tests

**Solution:**
```php
// After updating user's team, clear the cache
$user->update(['current_team_id' => $team->id]);
\Illuminate\Support\Facades\Cache::forget("user_{$user->id}_current_team_id");
```

### Records Not Found Due to Global Scopes

**Problem:** Records exist but `find()` or `first()` returns null in tests

**Solution:**
```php
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Models\Scopes\TypeScope;

// Bypass scopes when needed
$record = YourResource::withoutGlobalScope(TeamScope::class)
    ->withoutGlobalScope(TypeScope::class)
    ->where('id', $id)
    ->first();
```

### Running Tests Without Teams Feature

If you need to test without the teams feature:

```bash
# Use the dedicated phpunit config
vendor/bin/pest -c phpunit-without-teams.xml
```

### Test Helper Functions

Aura CMS provides helper functions in `tests/Pest.php`:

```php
// Create a super admin user with team
$user = createSuperAdmin();

// Create a super admin without team context
$user = createSuperAdminWithoutTeam();

// Create an admin with limited permissions
$user = createAdmin();

// Create a test post
$post = createPost(['title' => 'Test']);
```

### Livewire Component Testing

```php
use function Pest\Livewire\livewire;

test('component works correctly', function () {
    $this->actingAs(createSuperAdmin());
    
    livewire(YourComponent::class)
        ->set('form.fields.name', 'Test')
        ->call('save')
        ->assertHasNoErrors();
});
```

## Debugging Guide

### Enable Debug Mode

**In `.env`:**
```env
APP_DEBUG=true
APP_ENV=local
LOG_LEVEL=debug
```

### Laravel Telescope

Install for detailed debugging:
```bash
composer require laravel/telescope --dev
php artisan telescope:install
php artisan migrate
```

### Ray Debugging

Aura CMS includes Ray support:
```php
// Debug variables
ray($variable);
ray()->showQueries();

// Measure performance
ray()->measure();
// ... code to measure
ray()->measure();

// Pause execution
ray()->pause();
```

### Logging

```php
// Log to specific channel
Log::channel('aura')->info('Resource created', [
    'resource' => $resource->toArray(),
    'user' => auth()->id(),
]);

// Custom log file
Log::build([
    'driver' => 'single',
    'path' => storage_path('logs/aura-debug.log'),
])->info('Debug message');
```

### Debug Livewire

```blade
<!-- Show component state -->
<div>
    @if(config('app.debug'))
        <pre>{{ json_encode($this->all(), JSON_PRETTY_PRINT) }}</pre>
    @endif
</div>
```

```php
// In component
public function dehydrate()
{
    if (config('app.debug')) {
        ray()->showQueries();
        ray($this->all());
    }
}
```

## Error Messages Reference

### Common Error Messages and Solutions

| Error Message | Cause | Solution |
|--------------|-------|----------|
| "Aura CMS assets are not published" | Assets not published after install | Run `php artisan aura:publish` |
| "You need to define a custom meta table for this model" | Using meta fields without meta table | Define `$metaTable` property in model |
| "Only App resources can be edited" | Trying to edit vendor resources | Copy resource to app/Aura/Resources |
| "The 'resource' key is not set or is empty" | Tags field missing resource config | Add `'resource' => TagResource::class` to field config |
| "Function getFields() not found" | Malformed resource class | Ensure `getFields()` method exists and returns array |
| "Call to undefined method" | Missing trait in model | Add required traits (HasFields, HasMeta, etc.) |
| "Target class does not exist" | Incorrect namespace | Check class namespace and autoloading |
| "Undefined array key" | Missing field configuration | Ensure all required field keys are present |
| "Unknown field type" | Invalid field type class | Verify field type class exists (e.g., `Aura\Base\Fields\Text`) |
| "Requested thumbnail dimensions are not allowed" | Invalid thumbnail size | Check `config/aura.php` media dimensions |
| "Original image not found" | Missing source image for thumbnail | Verify image exists at the specified path |
| "Unable to find migration file" | Migration file not found | Check migration name and ensure file exists |
| "Width is not defined for thumbnail size" | Thumbnail config missing width | Add `width` key to thumbnail dimension config |
| "Invalid filter name" | Non-existent filter in table | Check filter slug matches field slug |
| "Invalid filter type" | Unsupported filter type | Use supported filter types for the field |

## Upgrade Procedures

### Upgrading Aura CMS

1. **Backup Your Application:**
```bash
# Backup database
mysqldump -u root -p aura_cms > backup.sql

# Backup files
tar -czf aura-backup.tar.gz .
```

2. **Update Package:**
```bash
composer update eminiarts/aura-cms
```

3. **Publish Updated Assets:**
```bash
php artisan aura:publish --force
```

4. **Run Migrations:**
```bash
php artisan migrate
```

5. **Clear Caches:**
```bash
php artisan cache:clear
php artisan config:cache
php artisan route:cache
php artisan view:cache
```

6. **Rebuild Assets:**
```bash
npm install
npm run build
```

### Breaking Changes

Always check the [CHANGELOG.md](../CHANGELOG.md) for breaking changes.

Common breaking changes to watch for:
- Field API changes
- Resource method signatures
- Configuration structure changes
- Database schema updates

## Migration Troubleshooting

### Posts to Custom Table Migration

**Issue:** Migration fails with "Column not found"

**Solution:**
```php
// Ensure fillable attributes in model
protected $fillable = [
    'title',
    'slug',
    'content',
    // Add all columns from migration
];

// Run migration command
php artisan aura:migrate-from-posts-to-custom-table Product
```

### Meta Table Migration

**Issue:** Orphaned meta records

**Solution:**
```bash
# Clean orphaned records before migration
DELETE FROM meta WHERE metable_id NOT IN (SELECT id FROM posts);

# Run migration
php artisan aura:migrate-post-meta-to-meta
```

### Custom Table Creation

**Issue:** Foreign key constraints fail

**Solution:**
```php
// In migration
public function up()
{
    Schema::disableForeignKeyConstraints();
    
    Schema::create('products', function (Blueprint $table) {
        // ... columns
    });
    
    Schema::enableForeignKeyConstraints();
}
```

## Frequently Asked Questions

### General Questions

**Q: Can I use Aura CMS with an existing Laravel application?**
A: Yes! Aura CMS is designed to be integrated into existing Laravel applications. Follow the installation guide and ensure there are no routing conflicts.

**Q: Does Aura CMS support multi-tenancy?**
A: Yes, Aura CMS has built-in team support for multi-tenancy. Enable it during installation or set `AURA_TEAMS=true` in your `.env` file.

**Q: Can I use custom database tables instead of the posts table?**
A: Absolutely! Aura CMS supports custom tables. Use `php artisan aura:resource Product --custom-table` to create resources with custom tables.

**Q: Is Aura CMS compatible with Laravel Octane?**
A: Yes, but ensure you clear stateful services between requests. Add Aura facades to the `flush` array in `config/octane.php`.

### Development Questions

**Q: How do I create custom fields?**
A: Use `php artisan aura:field MyCustomField` to generate a field class, then implement the required methods. See the [Creating Fields](creating-fields.md) guide.

**Q: Can I use Vue.js or React instead of Livewire?**
A: While Aura CMS is built with Livewire, you can create custom fields and components using any frontend framework via the API.

**Q: How do I extend existing resources?**
A: Create a new resource that extends the base resource:
```php
class CustomUser extends \Aura\Base\Resources\User
{
    public static function getFields()
    {
        $fields = parent::getFields();
        // Add your custom fields
        return $fields;
    }
}
```

**Q: Can I disable the Resource Editor in production?**
A: The Resource Editor is automatically disabled in non-local environments (see [Common Gotchas](#4-resource-editor-environment-restriction)). You can control it with `AURA_RESOURCE_EDITOR=false`.

### Performance Questions

**Q: How many resources can Aura CMS handle?**
A: Aura CMS can handle millions of records when properly configured with caching, indexes, and custom tables.

**Q: Should I use posts table or custom tables?**
A: Start with posts table for flexibility. Migrate to custom tables when you need better performance or specific database features.

**Q: How can I improve search performance?**
A: Use Laravel Scout for full-text search, add database indexes, and consider Elasticsearch for large datasets.

### Troubleshooting Questions

**Q: Why are my changes not appearing?**
A: Clear all caches:
```bash
php artisan cache:clear
php artisan config:clear
php artisan route:clear
php artisan view:clear
npm run build
```

**Q: How do I debug Livewire components?**
A: Use `@dump($variable)` in Blade views, `ray()` for debugging, or Laravel Telescope for detailed inspection.

**Q: Why am I getting 419 errors?**
A: This is a CSRF token mismatch. Ensure:
- CSRF token is included in forms: `@csrf`
- Session configuration is correct
- APP_URL matches your actual URL

> 📹 **Video Placeholder**: [Comprehensive troubleshooting walkthrough showing common issues and their solutions]

## Getting Help

If you can't find a solution here:

1. **Check the Documentation**: Review relevant sections in the docs
2. **Search GitHub Issues**: Look for similar issues on [GitHub](https://github.com/eminiarts/aura-cms/issues)
3. **Community Support**: Join the Aura CMS community forum
4. **Professional Support**: Contact support@eminiarts.com for priority support

Remember to include:
- Aura CMS version
- Laravel version
- PHP version
- Error messages
- Steps to reproduce
- Relevant code snippets

## Pro Tips

1. **Always backup before upgrades**
2. **Use version control (Git)**
3. **Test in staging before production**
4. **Monitor error logs regularly**
5. **Keep dependencies updated**
6. **Use Laravel Telescope in development**
7. **Enable query logging when debugging**
8. **Clear caches after deployments**
9. **Document your customizations**
10. **Follow Laravel best practices**