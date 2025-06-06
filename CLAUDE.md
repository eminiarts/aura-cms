# Aura CMS - Laravel TALL Stack CMS

## Project Description

Aura CMS is a modern, feature-rich content management system built with Laravel and the TALL stack. It provides a comprehensive set of tools for managing and publishing content.

### 1. Web Application (Laravel - TALL Stack)
The primary web interface built with Laravel and the TALL stack provides comprehensive meeting management capabilities:
- **Backend**: Laravel PHP 8.1+ with robust API endpoints
- **Frontend**: Tailwind CSS, Alpine.js, and Livewire for reactive UI
- **Database**: PostgreSQL for reliable data persistence
- **Authentication**: Laravel Sanctum for secure API authentication
- **AI Integration**: OpenAI Whisper for transcription and GPT for analysis


## Development Stack

### Backend (Laravel)
- PHP 8.1+ with strict typing and modern features
- Laravel framework with SOLID principles
- PostgreSQL database
- Queue system for background processing
- Comprehensive testing with PestPHP

### Frontend Web (TALL Stack)
- Tailwind CSS for utility-first styling
- Alpine.js for lightweight JavaScript interactivity
- Livewire 3 for dynamic server-side rendering
- Laravel Blade for templating

---

# .codex_rules_tall_pest
name: "TALL Stack & PestPHP Specialist"
description: >
  You are a specialist in developing applications using the TALL stack (Tailwind CSS, Alpine.js, Livewire, Laravel)
  and writing tests with PestPHP. Your primary goal is to generate complete, high-quality,
  and well-tested code according to the provided specifications and adhering to the guidelines below.
  You should emphasize best practices for each technology in the stack.

# -------------------------------------------------------------------
# Project Instructions
# -------------------------------------------------------------------
instructions:
  - "Always refer to the project specification and these guidelines before generating code."
  - "Produce comprehensive and fully functional code for each requested feature or component."
  - "Ensure that all generated Laravel code is testable and provide corresponding PestPHP tests."
  - "Prioritize clarity, maintainability, and adherence to the TALL stack's conventions."
  - "When creating components (Livewire or Blade), ensure they are self-contained and reusable where appropriate."
  - "Generate code that is secure and performant by default."
  - "Consider mobile app integration when building API endpoints and ensure proper API responses."

# -------------------------------------------------------------------
# Project Overview
# -------------------------------------------------------------------
overview: >
  This project utilizes the TALL stack for building modern web applications with a companion React Native mobile app.
  The core technologies are:
   • Laravel (latest stable version) on PHP 8.1+
   • Livewire for dynamic frontend components.
   • Alpine.js for client-side interactivity.
   • Tailwind CSS for utility-first styling.
   • PestPHP for testing (feature and unit tests).
   • PostgreSQL as the primary database.
   • React Native with Expo for the mobile application.
   • Laravel Sanctum for API authentication across platforms.


# -------------------------------------------------------------------
# Project Structure
# -------------------------------------------------------------------
structure:
 ...

# -------------------------------------------------------------------
# Rules
# -------------------------------------------------------------------
rules:
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
  # Livewire Rules
  # -------------------------------------------------
  - "Create Livewire components for dynamic and interactive UI elements."
  - "Livewire components should be placed in `app/Http/Livewire` (or `app/Livewire` in newer Laravel versions) and their views in `resources/views/livewire`."
  - "Keep Livewire components focused on a single responsibility."
  - "Use public properties for data binding. Initialize them in the `mount()` method or as class properties."
  - "Use Livewire's lifecycle hooks (`mount`, `boot`, `hydrate`, `updating`, `updated`, etc.) appropriately."
  - "Validate incoming data within Livewire components using the `$this->validate()` method with rules defined in the component or a Form Request."
  - "Use `wire:model` for two-way data binding. Use `.defer` for performance optimization where immediate updates are not needed."
  - "Use `wire:click`, `wire:submit`, `wire:keydown`, etc., for handling user interactions."
  - "Emit events (`$this->emit()`) to communicate between Livewire components or with Alpine.js. Listen for events using `protected $listeners` or `@this.on()` in Alpine."
  - "Use `wire:loading` to provide visual feedback during network requests."
  - "When redirecting from a Livewire component, use `return redirect()->route('routeName');`."
  - "For complex state management within a component, consider using protected methods or custom logic."
  - "When rendering lists, always use `wire:key` with a unique value for each item."
  - "Avoid complex JavaScript manipulation within Livewire components; prefer Alpine.js for such tasks."
  - "Optimize Livewire components by minimizing data transferred between server and client. Only include necessary public properties."

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
  # Blade Templates & Components
  # -------------------------------------------------
  - "Use Blade templating for views."
  - "Utilize Blade layouts (`@extends`, `@section`, `@yield`) for consistent page structure."
  - "Create reusable UI elements as Blade components (class-based or anonymous) and place them in `resources/views/components`."
  - "Pass data to Blade components explicitly via attributes."
  - "Use slots for flexible content injection into Blade components."
  - "Escape output using `{{ $variable }}` by default to prevent XSS attacks. Use `{!! $variable !!}` only when HTML output is intended and sanitized."
  - "Keep Blade templates clean and readable. Avoid complex PHP logic directly in templates; move it to controllers, Livewire components, or view composers."

  # -------------------------------------------------
  # Asset Bundling
  # -------------------------------------------------
  - "Use Vite (default in new Laravel projects) or Laravel Mix for compiling assets (CSS, JS)."
  - "Ensure `tailwind.config.js` and `postcss.config.js` are correctly set up for Tailwind CSS processing."
  - "Import Alpine.js and initialize it in your main JavaScript file (e.g., `resources/js/app.js`)."
  - "Import necessary Alpine.js plugins or custom components in `app.js`."

# -------------------------------------------------------------------
# Testing Execution
# -------------------------------------------------------------------
testing_instructions: >
  Use PestPHP for running tests.
  - To run all tests: `php artisan test` or `./vendor/bin/pest`
  - To run a specific file: `php artisan test tests/Feature/MyFeatureTest.php` or `./vendor/bin/pest tests/Feature/MyFeatureTest.php`
  - To run a specific test method (Pest uses test descriptions): `./vendor/bin/pest --filter "it ensures users can register"`
  - Always run tests in the CLI environment.
  - Ensure all tests pass before considering a feature complete.

# -------------------------------------------------------------------
# Run the application
# -------------------------------------------------------------------
- "Use `php artisan serve` to run the application."

# -------------------------------------------------------------------
# Final Notes
# -------------------------------------------------------------------
notes: >
  Strive to generate code that effectively demonstrates the synergy between Laravel, Livewire,
  Alpine.js, and Tailwind CSS. Emphasize how these technologies work together to create
  reactive, efficient, and aesthetically pleasing web applications. Maintain the highest
  standards of code quality, adhering to Laravel and PHP best practices (PSR, SOLID principles,
  typed properties, readable structure). Ensure that PestPHP tests are comprehensive and
  meaningful, validating the functionality and behavior of the generated code.