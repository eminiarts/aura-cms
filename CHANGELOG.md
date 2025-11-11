# Changelog

All notable changes to `aura-cms` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [0.1.0] - 2025-11-11

### Initial Release

This is the first official tagged release of Aura CMS, marking a stable checkpoint for projects already using the package. Version 0.1.0 represents the current stable state before planned upgrades to Laravel and Livewire in version 1.0.0.

#### Core Features

##### Resource System
- Powerful resource system extending Laravel Eloquent models
- Support for both shared posts table and custom database tables
- Automatic CRUD generation with permission handling
- Built-in soft deletes and searchable functionality
- Meta fields for flexible data storage without schema changes

##### Field Types (43 Available)
- **Basic Fields**: Text, Textarea, Number, Email, URL, Password, Date, DateTime, Time
- **Rich Content**: Wysiwyg, Markdown, Code
- **Selection**: Select, Radio, Checkbox, Boolean, Toggle
- **Advanced Selection**: AdvancedSelect with search and API support
- **Media**: Image, File, Gallery
- **Relationships**: BelongsTo, HasMany, BelongsToMany, MorphMany, MorphToMany, Tags
- **Structural**: Panel, Tab, Repeater
- **Specialized**: Slug, Color, Icon, Custom, ID
- **Data Display**: Computed, View

##### Multi-Tenancy
- Optional team-based multi-tenancy
- Team scoping with automatic filtering
- Team-specific roles and permissions
- User impersonation support (via lab404/laravel-impersonate)

##### User Management & Authentication
- Built-in authentication with Laravel Fortify
- Role-based access control (RBAC)
- Fine-grained permissions per resource (view, create, edit, delete, restore, forceDelete)
- Super admin role with unrestricted access
- Two-factor authentication support

##### Media Management
- Built-in media library
- Image optimization with Intervention Image
- File upload handling
- Gallery support

##### Admin Interface
- Modern TALL stack (Tailwind CSS, Alpine.js, Laravel, Livewire)
- Responsive design with mobile support
- Dark mode support
- Global search with keyboard shortcuts (⇧⌘K)
- Bookmarks for frequently accessed pages
- Recent pages tracking
- Customizable table views with sorting, filtering, and saved views
- Modal and slide-over components

##### Developer Tools
- Visual Resource Editor for building resources and fields
- Artisan commands for scaffolding:
  - `aura:install` - Interactive installation wizard
  - `aura:resource` - Generate new resources
  - `aura:field` - Create custom field types
  - `aura:plugin` - Scaffold new plugins
  - `aura:user` - Create admin users
  - `aura:permission` - Generate resource permissions
  - `aura:database-to-resources` - Generate resources from existing database tables
  - `aura:customize` - Customize components
  - `aura:publish` - Publish assets and views
- Custom field API for extending functionality
- Plugin system for modular features
- Comprehensive test suite with PestPHP (512 tests, 2657 assertions)

##### Additional Features
- Conditional field logic (show/hide based on other fields)
- Field validation with Laravel's validation rules
- Custom table filters and saved views
- Resource actions (bulk and individual)
- Customizable views and components
- Navigation management
- Event system integration
- Resource editor in development mode

#### Technical Specifications

- **PHP**: ^8.1
- **Laravel**: ^10.0 | ^11.0
- **Livewire**: ^3.0
- **Database**: MySQL, PostgreSQL, SQLite supported
- **Testing**: PestPHP with parallel test support
- **Code Quality**: PHPStan (level 5), Laravel Pint
- **Dependencies**:
  - Laravel Fortify ^1.20
  - Laravel Sanctum ^4.0
  - Intervention Image ^2.7
  - Spatie Laravel Package Tools ^1.16

#### Test Suite

- **512 passing tests** with 2657 assertions
- Parallel test execution support (10 processes)
- Test coverage includes:
  - Resource CRUD operations
  - All 43 field types
  - Team scoping and multi-tenancy
  - Permissions and policies
  - Livewire components
  - Database migrations
  - Conditional logic
  - Table filters and sorting

#### Fixed (November 2025)

##### Test Infrastructure Improvements
- Fixed race conditions in parallel test execution
- Fixed `createAdmin()` helper to properly attach roles using relationships instead of mass assignment
- Added Aura facade cleanup in test teardown to prevent state pollution between tests
- Removed debug code (`ray()` calls) from production files

##### Component Fixes
- **Edit Component**: Added optional `$slug` parameter to `mount()` method for test compatibility
- **View Component**: Added optional `$slug` parameter to `mount()` method for test compatibility
- **Create Component**: Fixed Tags field initialization to return empty array instead of null

##### Test Fixes
- **TeamPolicyTest**: Fixed unique constraint violations during parallel execution by:
  - Adding TeamScope cache clearing after user updates
  - Using `withoutGlobalScope()` to bypass TeamScope when querying roles
  - Adding exception handling for race conditions
- **TableTaxonomyFilterTest**: Updated expected SQL to include `team_id` clause from TeamScope
- **TagsRelationFieldTest**: Fixed user relationship by associating created users with current team
- **ResourceTest**: Fixed resource loading by manually requiring newly created resource files before class detection
- **AdvancedSelectFieldOptionsTest**: Fixed model detection by properly registering multiple resources in AuraFake
- **TagsFieldTest**: Fixed default value initialization for Tags field

#### Known Limitations

- This is a pre-1.0 release - **breaking changes may occur** in future versions
- Documentation is in progress (comprehensive docs planned for 1.0.0)
- Laravel and Livewire upgrades planned for 1.0.0

#### Migration to 1.0.0

Version 1.0.0 will include:
- Laravel framework upgrade (to latest stable)
- Livewire framework upgrade (to latest stable)
- Comprehensive documentation
- API stability guarantees
- Potential breaking changes from framework upgrades

**Recommendation**: Use version constraint `^0.1.0` in your `composer.json` to receive bug fixes while avoiding breaking changes.

---

## Versioning Strategy

- **0.1.x**: Current stable state, bug fixes and minor improvements only
- **1.0.0**: Laravel/Livewire upgrades, full documentation, API stability commitment
- **Breaking changes**: Expected between 0.x and 1.0, plan accordingly

## Support

- **Issues**: [GitHub Issues](https://github.com/eminiarts/aura-cms/issues)
- **Security**: support@eminiarts.ch
- **Documentation**: [docs/](docs/)

[0.1.0]: https://github.com/eminiarts/aura-cms/releases/tag/v0.1.0
