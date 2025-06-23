# CLAUDE.md

This file provides guidance to Claude Code (claude.ai/code) when working with code in this repository.

## Project Overview

Aura CMS is a modern, flexible content management system built on the TALL stack (Tailwind CSS, Alpine.js, Laravel, and Livewire). It provides a powerful admin interface and resource management system for building custom applications quickly without sacrificing flexibility.

### Core Features
- **Resource System**: Enhanced Eloquent models with built-in CMS features
- **Field System**: 40+ customizable field types with conditional logic and validation
- **Visual Resource Editor**: UI-based resource and field configuration
- **Team Management**: Optional multi-tenancy with team-based permissions
- **Media Management**: Built-in media library with optimization
- **Plugin Architecture**: Extensible system for custom functionality

## Development Commands

### Frontend Development
```bash
npm run dev         # Development server with hot reload
npm run build       # Production build
npm run build:lib   # Build library mode
npm run watch       # Watch for changes and rebuild
```

### Testing
```bash
composer test                                      # Run all tests
vendor/bin/pest                                   # Run tests directly
vendor/bin/pest -c phpunit-without-teams.xml      # Run tests without teams
XDEBUG_MODE=coverage vendor/bin/pest --coverage --min=80  # Run with coverage
vendor/bin/pest tests/Feature/Aura/               # Test specific feature
```

### Code Quality
```bash
composer analyse    # Run static analysis with PHPStan
composer format     # Format code with Laravel Pint
```

### Aura CMS Commands
```bash
php artisan aura:install                    # Install Aura CMS with interactive setup
php artisan aura:resource {name}            # Create a new resource
php artisan aura:field {name}               # Create a new custom field type
php artisan aura:plugin {name}              # Create a new plugin
php artisan aura:user                       # Create a new admin user
php artisan aura:permission                 # Generate permissions for resources
php artisan aura:publish                    # Publish Aura assets and views
php artisan aura:customize {component}      # Customize a component
php artisan aura:database-to-resources      # Generate resources from database tables
```

## High-Level Architecture

### Directory Structure

**Package Core** (`src/`):
- `Fields/` - 40+ field type implementations (Text, Select, Media, Relationship fields, etc.)
- `Resources/` - Built-in resources (User, Team, Role, Option, Permission)
- `Livewire/` - Core Livewire components (Table, Forms, Modals, Global Search)
- `Traits/` - Reusable traits for resources and fields
- `Pipeline/` - Field processing pipelines for data transformation
- `Services/` - Core services (ResourceManager, FieldManager, MediaOptimizer)

**Application Code** (`app/`):
- `Aura/Resources/` - Custom application resources
- Custom resources extend `Aura\Base\Resource`
- Uses flexible posts/meta system or custom tables

**Frontend** (`resources/`):
- Blade components with Aura component library
- Alpine.js for lightweight interactivity
- Tailwind CSS with custom theme system
- Field-specific view templates

### Key Architectural Patterns

1. **Resource System**
   - Resources are enhanced Eloquent models with CMS features
   - Can use shared posts table with meta fields or custom tables
   - Automatic CRUD generation with permission handling
   - Built-in team scoping for multi-tenancy

2. **Field System**
   - Fields define data structure and UI representation
   - Support for validation, conditional logic, and custom storage
   - Meta fields for flexible data without schema changes
   - Pipeline system for field data processing

3. **Component Architecture**
   - Extends Laravel Livewire for reactive components
   - Base Table component for consistent data listings
   - Modal and slide-over system for forms
   - Global search integration with keyboard shortcuts

4. **Permission System**
   - Resource-based permissions (view, create, edit, delete)
   - Team-scoped permissions when teams enabled
   - Role-based access control with Spatie permissions

## Development Guidelines

### Creating Resources

Resources must extend `Aura\Base\Resource` and define their model and fields:

```php
class Article extends Resource
{
    public static string $model = Post::class;
    
    public function fields()
    {
        return [
            ID::make('ID'),
            Text::make('Title')->rules('required'),
            Wysiwyg::make('Content'),
            Select::make('Category')->options([
                'news' => 'News',
                'blog' => 'Blog',
            ])->meta(),
            Date::make('Published At')->meta(),
        ];
    }
}
```

For custom database tables:
```php
class Product extends Resource
{
    public static string $model = Product::class;
    public static bool $customTable = true;
    
    public function fields()
    {
        return [
            ID::make('ID'),
            Text::make('Name')->rules('required'),
            Number::make('Price')->rules('required|numeric|min:0'),
            Boolean::make('Active')->default(true),
            BelongsTo::make('Category'),
            HasMany::make('Reviews'),
        ];
    }
}
```

### Key Development Rules

**Resource Development:**
- All resources must extend `Aura\Base\Resource`
- Use `$model` property to specify the Eloquent model
- Define fields in `fields()` method returning array of Field instances
- Use traits: `HasFields`, `InteractsWithFields`, `SaveFields`
- Implement context-specific field methods: `indexFields()`, `createFields()`, `editFields()`, `viewFields()`
- Apply `TeamScope` trait when multi-tenancy is enabled
- Follow naming convention: singular (e.g., `Post`, not `Posts`)

**Field Development:**
- Custom fields must extend `Aura\Base\Fields\Field`
- Define view templates: `$edit`, `$view`, `$index` properties
- Use conditional logic with `displayIf()` and `hideIf()`
- Support meta storage with `$meta = true` for flexible data
- Implement `get()` and `set()` methods for custom data handling

**Livewire Components:**
- Extend Aura base components (e.g., `Table\Table` for listings)
- Use Aura traits: `WithLivewireHelpers`, `InteractsWithTable`, `InteractsWithFields`
- Place in `src/Livewire/` for core, `app/Http/Livewire/` for custom
- Use Aura's modal and slide-over system for forms
- Emit Aura events: 'saved', 'deleted', 'updated'
- Use `$this->notify()` for user feedback
- Use `$this->authorize()` with resource policies

**Blade Components:**
- Use Aura's component library: `<x-aura::button>`, `<x-aura::input>`, `<x-aura::card>`
- Extend layout: `<x-aura::layout.app>` for authenticated pages
- Use field components: `<x-aura::fields.text>`, `<x-aura::fields.select>`
- Follow view naming: `aura.resource-name.action`

### Development Workflow

1. Create resource: `php artisan aura:resource Post`
2. Define fields in the resource class
3. Run migrations: `php artisan migrate`
4. Set up permissions: `php artisan aura:permission`
5. Customize views if needed
6. Write tests for the resource
7. Use Resource Editor for visual configuration

### Testing Approach
- Use PestPHP with Aura test helpers
- Test with team context when multi-tenancy enabled
- Mock media uploads using Aura utilities
- Aim for 80% minimum coverage
- Use descriptive test names: `it ensures users can register`
- Test validation with valid and invalid data
- Use model factories for test data

### Best Practices
- Start with posts table, migrate to custom tables when needed
- Use meta fields for flexible, non-indexed data
- Leverage conditional logic for dynamic forms
- Group related fields using Panel and Tab fields
- Implement proper team scoping from the beginning
- Cache field definitions in production
- Use Aura's built-in components before creating custom ones
- Test resources with different permission levels
- Use eager loading to avoid N+1 queries
- Follow PSR-12 coding standards
- Use strict types in all PHP files