# AGENTS.md - Aura CMS Agent Instructions

## Project Overview

Aura CMS is a Laravel package built on the **TALL stack** (Tailwind CSS, Alpine.js, Laravel, Livewire). It's a content management system with a dynamic resource/field system, team management, and role-based access control.

**Namespace**: `Aura\Base`  
**PHP Version**: 8.2+  
**Laravel**: 10.x, 11.x, 12.x

---

## Build, Test & Lint Commands

### Testing (Pest)

```bash
# Run all tests (parallel, no coverage)
composer test
# or
vendor/bin/pest --parallel

# Run a single test file
vendor/bin/pest tests/Feature/Fields/TextFieldTest.php

# Run a single test by name
vendor/bin/pest --filter "Text Field - Default Value set"

# Run tests in a specific group
vendor/bin/pest --group=fields
vendor/bin/pest --group=flows
vendor/bin/pest --group=table
vendor/bin/pest --group=resource

# Run tests without teams feature
vendor/bin/pest -c phpunit-without-teams.xml

# Run tests with coverage (requires Xdebug)
XDEBUG_MODE=coverage vendor/bin/pest --coverage --min=80
```

### Static Analysis (PHPStan)

```bash
composer analyse
# or
vendor/bin/phpstan analyse
```

PHPStan is configured at **level 3** with Octane compatibility and model property checking.

### Code Formatting (Pint)

```bash
composer format
# or
vendor/bin/pint
```

Uses Laravel preset with ordered class elements (alphabetically sorted).

### Frontend Assets (Vite)

```bash
npm install
npm run dev      # Development build
npm run build    # Production build
```

---

## Project Structure

```
src/                    # Package source code
  Commands/             # Artisan commands
  Fields/               # Field type classes (Text, Number, BelongsTo, etc.)
  Livewire/             # Livewire components
  Models/               # Eloquent models and scopes
  Resources/            # Built-in resources (User, Team, Role, Attachment)
  Traits/               # Reusable traits
  Policies/             # Authorization policies
tests/
  Feature/              # Feature tests (uses RefreshDatabase)
  FeatureWithDatabaseMigrations/  # Tests requiring full migrations
  Resources/            # Test resource classes
  Pest.php              # Pest configuration and helper functions
resources/views/        # Blade templates
config/                 # Package configuration files
database/
  factories/            # Model factories
  migrations/           # Migration stubs
```

---

## Code Style Guidelines

### PHP Standards

- **PSR-12** coding standards (enforced by Pint)
- **PHP 8.2+ features**: typed properties, enums, attributes, constructor promotion
- Follow Laravel conventions and SOLID principles

### Class Element Order (enforced by Pint)

1. `use` traits
2. Public constants, protected constants, private constants
3. Public properties, protected properties, private properties
4. Constructor, destructor
5. Magic methods
6. PHPUnit/Pest methods
7. Public methods, protected methods, private methods

Methods within each group are sorted **alphabetically**.

### Naming Conventions

| Type | Convention | Example |
|------|------------|---------|
| Classes | PascalCase | `TextFieldTest`, `CreateResource` |
| Methods | camelCase | `getFields()`, `createSuperAdmin()` |
| Properties | camelCase | `$fieldsAttributeCache` |
| Constants | SCREAMING_SNAKE | `TYPE_POST` |
| Database columns | snake_case | `current_team_id`, `created_at` |
| Config keys | kebab-case | `aura-settings.php` |
| Blade views | kebab-case | `view-value.blade.php` |

### Type Declarations

- Always use type hints for parameters and return types
- Use union types (`string|null`) over nullable (`?string`) for clarity
- Avoid `mixed` type; be specific
- **Never** use `@ts-ignore`, `as any`, or suppress type errors

### Imports

- Group imports: PHP built-ins, then vendor, then project
- Use fully qualified class names in arrays/strings: `'type' => 'Aura\\Base\\Fields\\Text'`
- Prefer importing classes over using FQCNs inline

---

## Testing Patterns

### Test File Structure

```php
<?php

use Aura\Base\Livewire\CreateResource;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('descriptive test name', function () {
    livewire(CreateResource::class)
        ->set('form.fields.name', 'Test')
        ->call('save')
        ->assertHasNoErrors();
});
```

### Helper Functions (defined in `tests/Pest.php`)

- `createSuperAdmin()` - Creates and authenticates a super admin user with team
- `createSuperAdminWithoutTeam()` - Super admin without team context
- `createAdmin()` - Creates admin user with limited permissions
- `createPost()` - Creates a test post

### Livewire Testing

```php
use function Pest\Livewire\livewire;

livewire(ComponentClass::class, ['param' => 'value'])
    ->set('property', 'value')
    ->call('method')
    ->assertSee('text')
    ->assertSet('property', 'expected');
```

### Database Testing

- Feature tests use `RefreshDatabase` trait automatically
- Tests in `FeatureWithDatabaseMigrations/` use `DatabaseMigrations`
- Use factories: `User::factory()->create()`

---

## Resource & Field System

### Creating a Resource

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

### Field Types

Located in `src/Fields/`. Common types:
- `Text`, `Textarea`, `Number`, `Email`, `Password`
- `Select`, `Radio`, `Checkbox`, `Toggle`
- `Date`, `DateTime`, `Time`
- `BelongsTo`, `HasMany`, `Tags`
- `Panel`, `Tab`, `Repeater`

---

## Error Handling

- Use Laravel's exception handler
- Create custom exceptions in `src/Exceptions/`
- Never use empty catch blocks
- Log errors using `Log` facade for debugging

---

## Security Checklist

- Use `@csrf` in all forms
- Validate input with Form Requests
- Use Policies for authorization (`can()`, `authorize()`)
- Never expose `.env` values to frontend
- Use `auth()` helper and middleware for access control

---

## Key Files to Know

| File | Purpose |
|------|---------|
| `src/Resource.php` | Base resource class with field handling |
| `src/Aura.php` | Main facade for the CMS |
| `tests/Pest.php` | Test configuration and global helpers |
| `config/aura.php` | Main package configuration |
| `pint.json` | PHP code style rules |
| `phpstan.neon.dist` | Static analysis configuration |

---

## Common Gotchas

1. **Team Scope**: Most models use `TeamScope`. Use `withoutGlobalScope()` to bypass in tests.
2. **Aura Facade Reset**: Tests reset the Aura facade after each test to prevent pollution.
3. **Meta Fields**: Resources can store fields in a `meta` table. Check `usesMeta()`.
4. **Type Column**: The `posts` table uses a `type` column for single-table inheritance.

---

## CI/CD

GitHub Actions run on push/PR to `main`:
- PHP 8.2 tests with Pest
- PHPStan static analysis
- Pint code style fixes (auto-commit)
