# Changelog

All notable changes to `aura-cms` will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.0.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [Unreleased]

### Added

- Per-resource page component hooks (`indexComponent()`, `createComponent()`, `editComponent()`, `viewComponent()`): a resource can swap in a custom Livewire component for any of its admin pages while keeping the default URI and `aura.{slug}.*` route name, so all generated links keep working.
- `aura:customize` command: customize a resource page by copying its Blade view into `resources/views/aura/{slug}/`, generating a custom Livewire component in `app/Livewire/`, or both (`--mode=view|component|full`). Wires the resource to the generated files automatically and scaffolds an app-level subclass for package resources (User, Team, …).

### Removed

- The broken `aura:customize-component` command (superseded by `aura:customize`; it never copied views and generated routes outside the admin middleware group).

## [1.0.0](https://github.com/eminiarts/aura-cms/compare/v0.2.0...v1.0.0) - 2026-07-18

First stable release (pre-released as `v1.0.0-beta.1` on 2026-07-17).

### Changed

- Raised the support matrix to PHP 8.4+, Laravel 12/13, and Livewire 4.
- Made package migrations ownership-aware so rollback preserves host-owned tables and columns.
- Added a central `Aura::flushState()` reset point for tests and long-running queue workers.
- Made the Resource Editor a local-development-only, super-admin-only tool.
- Restored and enforced the teams-disabled test suite.
- Decomposed the `AuraModelConfig` god-trait into focused concern traits behind a stable aggregator; added the `DefinesFields` contract (no behavior change).
- Documented the dynamic attribute resolution order (`Resource::__get`) in a single place.

### Added

- Laravel Octane support: Aura is a container singleton and flushes its request-scoped state on Octane's request/task/tick events (`laravel/octane` stays optional).
- Batched table rendering: `BelongsTo` and `Image` columns resolve in one query per page instead of one per row; `Tags`/`AdvancedSelect` columns eager-load once (`PreloadsTableDisplay` / `ProvidesTableEagerLoad` contracts).
- A fast path for table cell display that avoids building the full fields collection for plain columns.
- `aura.features.legacy_fields_append` config flag: set to `false` to stop appending the computed `fields` attribute to every model serialization (recommended for new apps).
- Dedicated test coverage for every field type, plus a guard test that fails when a new field ships untested.

### Fixed

- `whereMetaContains` now uses portable JSON containment (previously MySQL-only raw SQL that failed on SQLite/Postgres).
- Resource URL helpers (`createUrl`/`editUrl`/`indexUrl`/`viewUrl`) return `null` for unregistered routes instead of throwing; table row actions only render existing links.
- The Attachment media grid is no longer shadowed by generically registered attachment routes.

### Security

- Hardened role assignment, stored rich content, saved-filter rendering, logout, and media access authorization.
- The Resource Editor now also requires a super admin (middleware and component).

### Upgrade notes

Aura 1.0 is a fresh baseline and does not provide an automated 0.x data migration. See [UPGRADING.md](UPGRADING.md).

## [0.2.0](https://github.com/eminiarts/aura-cms/compare/v0.1.0...v0.2.0) - 2026-06-03

### What changed

- Added plugin extension points for custom field payload handling, including support for translatable field indicators in field wrappers.
- Improved Livewire compatibility and modal/component registration behavior.
- Hardened media uploads and added coverage for media uploader security.
- Fixed authentication edge cases around case-insensitive email login and password reset.
- Added configurable team creation via `AURA_CREATE_TEAMS` / `create_teams` config.
- Improved resource field saving, edit validation, Roles field behavior, and handling for missing or unknown field slugs.
- Refined user create form layout and updated UI styling/build assets.
- Expanded and refreshed documentation plus broader feature/test coverage.

### Upgrade notes

This release is tagged as `v0.2.0` and can be required with Composer using `eminiarts/aura-cms:^0.2.0`. It is also the baseline required by `eminiarts/aura-translations:^0.1.0`.

## [0.1.0](https://github.com/eminiarts/aura-cms/releases/tag/v0.1.0) - 2025-11-11

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
  - `aura:install-config` - Interactive installation wizard
  - `aura:resource` - Generate new resources
  - `aura:field` - Create custom field types
  - `aura:plugin` - Scaffold new plugins
  - `aura:user` - Create admin users
  - `aura:create-resource-permissions` - Generate resource permissions
  - `aura:database-to-resources` - Generate resources from existing database tables
  - `aura:customize-component` - Customize components
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
  - Intervention Image ^3.0
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

**Recommendation**: Use version constraint `^0.2.0` in your `composer.json` to receive 0.x bug fixes while avoiding the 1.0 support-matrix change.


---

## Versioning Strategy

- **0.1.x**: Current stable state, bug fixes and minor improvements only
- **1.0.0**: PHP 8.4+, Laravel 12/13, Livewire 4, and the supported V1 baseline
- **Breaking changes**: Expected between 0.x and 1.0, plan accordingly

## Support

- **Issues**: [GitHub Issues](https://github.com/eminiarts/aura-cms/issues)
- **Security**: [SECURITY.md](SECURITY.md)
- **Documentation**: [docs/](docs/)
