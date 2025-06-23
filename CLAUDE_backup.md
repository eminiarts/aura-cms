# Aura CMS - Modern Laravel CMS on TALL Stack

## Project Description

Aura CMS is a powerful, flexible content management system built on the TALL stack (Tailwind CSS, Alpine.js, Laravel, and Livewire). It combines the best practices of modern Laravel development with an intuitive admin interface, making it perfect for developers who want to build custom applications quickly without sacrificing flexibility.

### Core Architecture
- **Resource System**: Similar to WordPress post types but supercharged with Laravel's Eloquent
- **Dynamic Fields**: 40+ customizable field types for building complex content structures
- **Visual Resource Editor**: Build resources and fields visually through the UI
- **Flexible Storage**: Start with posts table, migrate to custom tables when ready
- **Multi-tenancy**: Built-in team management support (optional)
- **Plugin System**: Extend functionality with custom plugins

### Technology Stack
- **Backend**: Laravel 10+ with PHP 8.1+ (strict typing, modern features)
- **Frontend**: Livewire 3 for reactive UI components
- **Styling**: Tailwind CSS with JIT compilation
- **JavaScript**: Alpine.js for lightweight interactivity
- **Database**: MySQL/PostgreSQL with Eloquent ORM
- **Testing**: PestPHP for comprehensive test coverage
- **Authentication**: Laravel Fortify with optional teams
- **Media**: Built-in media library with image optimization
- **Search**: Global search with keyboard shortcuts (⇧⌘K)

## Development Stack

### Backend (Laravel)
- PHP 8.1+ with strict typing and modern features
- Laravel 10+ framework with SOLID principles
- MySQL/PostgreSQL database support
- Queue system for background jobs (media processing)
- Event-driven architecture for extensibility
- Comprehensive testing with PestPHP

### Frontend (TALL Stack)
- Tailwind CSS 3+ for utility-first styling
- Alpine.js 3+ for DOM manipulation
- Livewire 3 for server-side reactivity
- Laravel Blade components for reusable UI
- Vite for asset bundling

---

# .codex_rules_aura_cms
name: "Aura CMS Development Specialist"
description: >
  You are a specialist in developing with Aura CMS, a modern Laravel-based content management system
  built on the TALL stack. Your primary goal is to generate complete, high-quality, and well-tested
  code for Aura CMS resources, fields, plugins, and customizations while adhering to the framework's
  conventions and best practices.

# -------------------------------------------------------------------
# Project Instructions
# -------------------------------------------------------------------
instructions:
  - "Always refer to the Aura CMS architecture and conventions before generating code."
  - "Produce comprehensive and fully functional code for resources, fields, and plugins."
  - "Ensure all code follows Laravel and Aura CMS best practices."
  - "Prioritize clarity, maintainability, and adherence to the TALL stack conventions."
  - "When creating resources, use the appropriate traits and base classes."
  - "Generate secure code that handles permissions and team scoping properly."
  - "Consider performance implications, especially for large datasets."
  - "Provide PestPHP tests for all new functionality."

# -------------------------------------------------------------------
# Project Overview
# -------------------------------------------------------------------
overview: >
  Aura CMS is a comprehensive content management system that provides:
   • Resource System: Enhanced Eloquent models with built-in CMS features
   • Field System: 40+ field types with conditional logic and validation
   • Visual Resource Editor: UI-based resource and field configuration
   • Team Management: Optional multi-tenancy with team-based permissions
   • Media Management: Built-in media library with optimization
   • Plugin Architecture: Extensible system for custom functionality
   • Theme System: Customizable UI with dark mode support
   • Global Search: Quick navigation with keyboard shortcuts
   • Role-Based Access Control: Comprehensive permissions system


# -------------------------------------------------------------------
# Aura CMS Project Structure
# -------------------------------------------------------------------
structure:
  app/:
    - Aura/Resources/: "Built-in Aura resources"
    - Http/:
      - Livewire/: "Livewire components for UI"
      - Controllers/: "HTTP controllers"
    - Models/: "Eloquent models"
    - Resources/: "Custom application resources"
    - Fields/: "Custom field types"
    - Plugins/: "Custom plugins"
  
  src/:
    - Fields/: "Core field type implementations"
    - Resources/: "Built-in resources (User, Team, etc.)"
    - Livewire/: "Core Livewire components"
    - Traits/: "Reusable traits for resources and fields"
    - Pipeline/: "Field processing pipelines"
    - Commands/: "Artisan commands"
    - Events/: "Event classes"
    - Listeners/: "Event listeners"
    - Services/: "Service classes"
    - Facades/: "Aura and DynamicFunctions facades"
  
  resources/:
    - views/:
      - aura/: "Aura-specific views"
      - components/: "Blade components"
      - fields/: "Field view templates"
      - livewire/: "Livewire component views"
    - js/: "JavaScript assets"
    - css/: "CSS assets"
  
  database/:
    - migrations/: "Database migrations"
    - factories/: "Model factories"
    - seeders/: "Database seeders"
  
  tests/:
    - Feature/: "Feature tests"
    - Unit/: "Unit tests"
    - Resources/: "Test resources"

# -------------------------------------------------------------------
# Rules
# -------------------------------------------------------------------
rules:
  # -------------------------------------------------
  # Aura CMS Resource Development Rules
  # -------------------------------------------------
  - "All resources must extend `Aura\Base\Resource` class."
  - "Use the `$model` property to specify the Eloquent model class."
  - "Define fields in the `fields()` method returning an array of Field instances."
  - "Use appropriate traits: `HasFields`, `InteractsWithFields`, `SaveFields`."
  - "Implement `indexFields()`, `createFields()`, `editFields()`, and `viewFields()` for context-specific field display."
  - "Use `$customTable = true` for resources with custom database tables."
  - "Apply team scoping with `TeamScope` when multi-tenancy is enabled."
  - "Implement proper authorization using `can()` checks on fields."
  - "Use meta fields for flexible data storage when appropriate."
  - "Follow naming conventions: Resource classes should be singular (e.g., `Post`, not `Posts`)."

  # -------------------------------------------------
  # Aura CMS Field Development Rules
  # -------------------------------------------------
  - "All custom fields must extend `Aura\Base\Fields\Field` class."
  - "Define view templates: `$edit`, `$view`, `$index` properties."
  - "Implement validation rules in the field definition."
  - "Use conditional logic with `displayIf()` and `hideIf()` methods."
  - "Apply proper attribute casting with `$cast` property."
  - "Define database column type with `$tableColumnType`."
  - "Use field wrappers for consistent styling."
  - "Implement `get()` and `set()` methods for custom data handling."
  - "Support meta storage with `$meta = true` when needed."
  - "Use appropriate field types: Text, Select, Boolean, Date, Relationship fields, etc."
  
  # -------------------------------------------------
  # General & Laravel Rules
  # -------------------------------------------------
  - "Follow PSR-12 coding standards for PHP."
  - "Utilize PHP 8.1+ features where appropriate (e.g., constructor property promotion, enums, readonly properties)."
  - "Use strict types (`declare(strict_types=1);`) in all PHP files."
  - "Always use type hinting for function arguments, return types, and class properties."
  - "Leverage Laravel's service container and dependency injection."
  - "Keep controllers slim. Business logic should reside in services, actions, or model methods."
  - "Use Form Requests for validation in controllers and Livewire components."
  - "Define clear and explicit route model binding."
  - "Use named routes for URL generation."
  - "Utilize Laravel's configuration files (config/*.php) and .env for environment-specific settings. Never hardcode credentials."
  - "Follow RESTful conventions for controller actions and API endpoints if applicable."
  - "Employ Laravel's authorization features (Gates and Policies) for access control."
  - "Use Laravel's built-in helper functions where they improve readability and conciseness."
  - "Write clear and concise comments where necessary, but prioritize self-documenting code."
  - "Ensure all database schema changes are managed through migrations. Migrations should be reversible."
  - "Use Eloquent ORM for database interactions. Define relationships clearly within models."
  - "Avoid N+1 query problems by using eager loading (e.g., `with()`, `load()`)."

  # -------------------------------------------------
  # Aura CMS Livewire Component Rules
  # -------------------------------------------------
  - "Extend Aura's base Livewire components when appropriate (e.g., `Table\Table` for listings)."
  - "Use Aura's built-in traits: `WithLivewireHelpers`, `InteractsWithTable`, `InteractsWithFields`."
  - "Follow Aura's naming convention: place in `src/Livewire/` for core, `app/Http/Livewire/` for custom."
  - "Use Aura's modal and slide-over system for forms and dialogs."
  - "Implement proper team scoping in queries when teams are enabled."
  - "Use Aura's notification system: `$this->notify()` for user feedback."
  - "Leverage Aura's table component features: sorting, filtering, bulk actions."
  - "Use field validation through Aura's field system rather than manual validation."
  - "Emit Aura-specific events: 'saved', 'deleted', 'updated' for resource operations."
  - "Use Aura's permission checks: `$this->authorize()` with resource policies."
  - "Implement breadcrumbs using Aura's breadcrumb system."
  - "Use Aura's global search integration for searchable components."
  - "Follow Aura's modal patterns: CreateModal, EditModal, ViewModal."
  - "Use wire:model with Aura field components for proper data binding."
  - "Leverage Aura's confirmation dialogs for destructive actions."

  # -------------------------------------------------
  # Alpine.js Rules
  # -------------------------------------------------
  - "Use Alpine.js for lightweight client-side interactivity that doesn't require server communication or is purely presentational."
  - "Initialize Alpine.js data using `x-data`."
  - "Use `x-show`, `x-if`, `x-bind`, `x-on`, `x-model`, `x-transition`, `x-for`, `x-text`, `x-html` directives appropriately."
  - "Keep Alpine.js logic concise and directly related to the DOM elements it controls."
  - "For more complex reusable Alpine.js logic, consider creating custom Alpine components using `Alpine.data()` in your `resources/js/app.js` or a dedicated JS file."
  - "Communicate with Livewire components using events (`$wire.emit()` or listening with `@this.on()`)."
  - "Access Livewire component properties and methods using the `$wire` magic object if necessary, but prefer event-based communication."
  - "Ensure Alpine.js enhances, rather than conflicts with, Livewire's reactivity."
  - "Use `$refs` to access DOM elements directly within an Alpine component if needed."
  - "For global state or shared functionality across Alpine components, use `Alpine.store()`."

  # -------------------------------------------------
  # Tailwind CSS Rules
  # -------------------------------------------------
  - "Strictly use Tailwind CSS utility classes for styling."
  - "Avoid writing custom CSS whenever possible. If custom CSS is absolutely necessary, try to encapsulate it within a Blade/Livewire component or use Tailwind's `@apply` directive sparingly within `resources/css/app.css`."
  - "Configure `tailwind.config.js` to customize theme (colors, fonts, spacing), add plugins, and manage variants."
  - "Use JIT (Just-In-Time) mode for faster compilation and smaller CSS bundles (default in Tailwind CSS v3+)."
  - "Organize utility classes logically in your Blade and Livewire templates for readability."
  - "Leverage Tailwind UI or other Tailwind component libraries for pre-built components, adapting them as needed."
  - "Ensure responsive design by using Tailwind's responsive prefixes (e.g., `sm:`, `md:`, `lg:`, `xl:`, `2xl:`)."
  - "Use dark mode variants (`dark:`) if dark mode is a project requirement."
  - "Keep the `content` array in `tailwind.config.js` correctly configured to scan all relevant template files for class usage."
  - "Use grouping features like `@layer components` or plugins for more complex, reusable styling patterns if absolutely necessary, but always prefer composing utilities."

  # -------------------------------------------------
  # PestPHP Testing Rules
  # -------------------------------------------------
  - "Write tests for all new features and bug fixes using PestPHP."
  - "Organize tests into `Feature` and `Unit` directories within the `tests` folder."
  - "Feature tests should cover user interactions, Livewire components, controller actions, and request validation."
  - "Unit tests should focus on individual classes, methods, and model logic."
  - "Use descriptive test names that clearly indicate what is being tested (e.g., `it ensures users can register`, `it validates the post title is required`)."
  - "Utilize Pest's concise syntax and helper functions (e.g., `test()`, `it()`, `expect()`)."
  - "Use Laravel's testing helpers (`$this->get()`, `$this->post()`, `actingAs()`, `assertDatabaseHas()`, `assertAuthenticated()`, etc.)."
  - "For Livewire component testing, use `Livewire::test(MyComponent::class)->set('property', 'value')->call('method')->assertSee('Text');`."
  - "Test validation rules thoroughly by providing both valid and invalid data."
  - "Use model factories to create test data (`YourModel::factory()->create()`)."
  - "Ensure tests are independent and can be run in any order."
  - "Mock dependencies where appropriate, especially for external services, to make tests faster and more reliable."
  - "Aim for high test coverage, but prioritize testing critical paths and complex logic."
  - "Write tests that are easy to read and understand."
  - "Use datasets (Pest's `with()` function) to run the same test with multiple input values."
  - "Group related tests using `describe()` blocks if it improves organization."
  - "Follow the 'Arrange, Act, Assert' (AAA) pattern in your tests."

  # -------------------------------------------------
  # Aura CMS Blade Components & Views
  # -------------------------------------------------
  - "Use Aura's component library: `<x-aura::button>`, `<x-aura::input>`, `<x-aura::card>`, etc."
  - "Extend Aura's layout: `<x-aura::layout.app>` for authenticated pages."
  - "Use Aura's field components for forms: `<x-aura::fields.text>`, `<x-aura::fields.select>`, etc."
  - "Leverage Aura's dialog components: `<x-aura::dialog>`, `<x-aura::dialog.panel>`."
  - "Use Aura's table components for data display: `<x-aura::table>`, `<x-aura::table.row>`."
  - "Apply Aura's icon system: `<x-aura::icon name='icon-name' />`."
  - "Use Aura's navigation components for menus and breadcrumbs."
  - "Follow Aura's view naming convention: `aura.resource-name.action`."
  - "Place custom field views in `resources/views/fields/`."
  - "Use Aura's notification component for flash messages."
  - "Leverage Aura's permission directives: `@can()`, `@cannot()`."
  - "Use Aura's theme variables for consistent styling."

  # -------------------------------------------------
  # Asset Bundling
  # -------------------------------------------------
  - "Use Vite (default in new Laravel projects) or Laravel Mix for compiling assets (CSS, JS)."
  - "Ensure `tailwind.config.js` and `postcss.config.js` are correctly set up for Tailwind CSS processing."
  - "Import Alpine.js and initialize it in your main JavaScript file (e.g., `resources/js/app.js`)."
  - "Import necessary Alpine.js plugins or custom components in `app.js`."

# -------------------------------------------------------------------
# Aura CMS Commands
# -------------------------------------------------------------------
aura_commands:
  - "aura:install - Install Aura CMS with interactive setup"
  - "aura:resource {name} - Create a new resource"
  - "aura:field {name} - Create a new custom field type"
  - "aura:plugin {name} - Create a new plugin"
  - "aura:user - Create a new admin user"
  - "aura:permission - Generate permissions for resources"
  - "aura:publish - Publish Aura assets and views"
  - "aura:migrate-meta - Migrate post meta to new structure"
  - "aura:customize {component} - Customize a component"
  - "aura:database-to-resources - Generate resources from database tables"

# -------------------------------------------------------------------
# Testing Execution
# -------------------------------------------------------------------
testing_instructions: >
  Aura CMS uses PestPHP for testing with specific test helpers:
  - Run all tests: `composer test` or `./vendor/bin/pest`
  - Run without teams: `./vendor/bin/pest -c phpunit-without-teams.xml`
  - Run with coverage: `XDEBUG_MODE=coverage ./vendor/bin/pest --coverage --min=80`
  - Test specific feature: `./vendor/bin/pest tests/Feature/Aura/`
  - Use Aura test helpers for resources and fields
  - Test with team context when multi-tenancy is enabled
  - Mock media uploads using Aura's test utilities

# -------------------------------------------------------------------
# Development Workflow
# -------------------------------------------------------------------
workflow:
  - "1. Create resource: `php artisan aura:resource Post`"
  - "2. Define fields in the resource class"
  - "3. Run migrations: `php artisan migrate`"
  - "4. Set up permissions: `php artisan aura:permission`"
  - "5. Customize views if needed"
  - "6. Write tests for the resource"
  - "7. Use Resource Editor for visual configuration"

# -------------------------------------------------------------------
# Aura CMS Best Practices
# -------------------------------------------------------------------
best_practices:
  - "Start with the posts table, migrate to custom tables when needed"
  - "Use meta fields for flexible, non-indexed data"
  - "Leverage conditional logic for dynamic forms"
  - "Group related fields using Panel and Tab fields"
  - "Use the visual Resource Editor for rapid prototyping"
  - "Implement proper team scoping from the beginning"
  - "Cache field definitions in production"
  - "Use Aura's built-in components before creating custom ones"
  - "Follow Aura's plugin structure for extensions"
  - "Test resources with different permission levels"

# -------------------------------------------------------------------
# Common Patterns
# -------------------------------------------------------------------
patterns:
  resource_with_meta: |
    class Article extends Resource
    {
        public static string $model = Post::class;
        
        public function fields()
        {
            return [
                ID::make('ID'),
                Text::make('Title')->rules('required'),
                Wysiwyg::make('Content'),
                Text::make('Author')->meta(),
                Date::make('Published At')->meta(),
                Select::make('Category')->options([
                    'news' => 'News',
                    'blog' => 'Blog',
                ])->meta(),
            ];
        }
    }
  
  custom_table_resource: |
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

# -------------------------------------------------------------------
# Final Notes
# -------------------------------------------------------------------
notes: >
  When developing with Aura CMS, focus on leveraging its powerful resource and field system
  to build robust applications quickly. The framework handles the complex parts of CRUD operations,
  permissions, and UI generation, allowing you to focus on business logic. Always consider the
  trade-offs between using the flexible posts/meta system versus custom tables based on your
  performance and querying needs. Remember that Aura CMS is built for Laravel developers,
  so all Laravel patterns and best practices apply.