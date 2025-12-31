# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Aura CMS is a Laravel package built on the **TALL stack** (Tailwind CSS, Alpine.js, Laravel, Livewire). It provides a content management system with a dynamic resource/field system, team management, and role-based access control.

**Namespace**: `Aura\Base`
**PHP Version**: 8.2+
**Laravel**: 10.x, 11.x, 12.x

## Development Commands

### Testing (Pest)
```bash
composer test                                    # Run all tests (parallel)
vendor/bin/pest --filter "test name"             # Run single test by name
vendor/bin/pest tests/Feature/Fields/            # Run tests in directory
vendor/bin/pest --group=fields                   # Run test group (fields, flows, table, resource)
vendor/bin/pest -c phpunit-without-teams.xml     # Run without teams feature
XDEBUG_MODE=coverage vendor/bin/pest --coverage  # Run with coverage
```

### Code Quality
```bash
composer analyse    # PHPStan (level 3)
composer format     # Laravel Pint
```

### Frontend Assets
```bash
npm run dev         # Development build
npm run build       # Production build
```

### Aura Commands
```bash
# Resource creation
php artisan aura:resource {name}                    # Create resource (uses config default)
php artisan aura:resource {name} --custom           # Force dedicated table
php artisan aura:resource {name} --dynamic          # Force posts/meta (EAV)
php artisan aura:resource {name} --no-migration     # Skip migration generation

# Generate migration from existing resource fields
php artisan aura:create-resource-migration {class}  # e.g., App\Aura\Resources\Article
php artisan aura:create-resource-migration {class} --soft-deletes

# Other commands
php artisan aura:field {name}        # Create custom field
php artisan aura:plugin {name}       # Create plugin
php artisan aura:permission          # Generate permissions
```

## Architecture

### Directory Structure
```
src/
  Fields/           # Field types (Text, Select, BelongsTo, etc.)
  Livewire/         # Livewire components
  Resources/        # Built-in resources (User, Team, Role, Attachment)
  Traits/           # Reusable traits
  Policies/         # Authorization policies
tests/
  Feature/          # Uses RefreshDatabase
  FeatureWithDatabaseMigrations/  # Uses DatabaseMigrations
  Resources/        # Test resource classes
  Pest.php          # Test helpers
```

### Resource System

Resources extend `Aura\Base\Resource` and define fields via `getFields()`:

```php
class Post extends Resource
{
    public static string $type = 'Post';
    protected static ?string $slug = 'post';

    public static function getFields(): array
    {
        return [
            [
                'type' => 'Aura\\Base\\Fields\\Text',
                'name' => 'Title',
                'slug' => 'title',
                'validation' => 'required|max:255',
            ],
        ];
    }
}
```

### Database Storage Patterns

Aura CMS supports two storage patterns:

**1. Posts/Meta (EAV Pattern)** - Default
```php
// Uses shared posts table + meta table for field values
class Article extends Resource
{
    public static $customTable = false;    // default
    public static bool $usesMeta = true;   // default
}
```
- Flexible: Add fields without migrations
- Best for: Dynamic content, plugins, small datasets
- Trade-off: Slower queries at scale

**2. Dedicated Tables** - Recommended for production
```php
// Uses dedicated table with typed columns
class Article extends Resource
{
    public static $customTable = true;
    public static bool $usesMeta = false;
    protected $table = 'articles';
}
```
- Better performance: Direct column access
- Best for: Stable schemas, large datasets, production apps
- Trade-off: Requires migrations for field changes

Control default behavior via `config/aura.php`:
```php
'features' => [
    'custom_tables_for_resources' => env('AURA_CUSTOM_TABLES', false),
]
```

### Field Type to Column Mapping

When using `aura:create-resource-migration`, fields map to these column types:

| Field Type | Column Type |
|------------|-------------|
| Text, Email, Slug, Phone | string |
| Textarea, Wysiwyg, Code | text |
| Number | integer |
| Boolean | boolean |
| Date | date |
| Datetime | timestamp |
| Time | time |
| Image, File, Repeater, Json, Checkbox | json |
| BelongsTo | bigInteger (with index) |

### Key Scopes
- **TeamScope**: Scopes queries to current team (bypassed with `withoutGlobalScope(TeamScope::class)`)
- **TypeScope**: Filters by `type` column for single-table inheritance
- **ScopedScope**: User-scoped filtering

## Testing Patterns

### Test Helpers (from `tests/Pest.php`)
- `createSuperAdmin()` - Creates authenticated super admin with team
- `createSuperAdminWithoutTeam()` - Super admin without team context
- `createAdmin()` - Limited permissions admin
- `createPost()` - Test post factory

### Livewire Testing
```php
use function Pest\Livewire\livewire;

beforeEach(fn () => $this->actingAs($this->user = createSuperAdmin()));

test('creates resource', function () {
    livewire(CreateResource::class)
        ->set('form.fields.name', 'Test')
        ->call('save')
        ->assertHasNoErrors();
});
```

### Important Test Notes
- Aura facade is reset after each test to prevent pollution
- Use `withoutGlobalScope(TeamScope::class)` when querying across teams in tests

## Code Style

Uses Laravel Pint with `ordered_class_elements` rule - methods sorted alphabetically within groups:
1. Traits, constants, properties
2. Constructor/destructor
3. Magic methods
4. Public methods, protected methods, private methods

### Naming Conventions
| Type | Convention | Example |
|------|------------|---------|
| Classes | PascalCase | `TextFieldTest` |
| Methods | camelCase | `getFields()` |
| Database columns | snake_case | `current_team_id` |
| Config keys | kebab-case | `aura-settings.php` |
| Blade views | kebab-case | `view-value.blade.php` |

## Key Configuration

- `config/aura.php` - Main package config (teams, features, theme)
- Teams enabled by default via `AURA_TEAMS` env var
- Database storage: `AURA_CUSTOM_TABLES` env var (false = posts/meta, true = dedicated tables)
