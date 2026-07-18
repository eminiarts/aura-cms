# Upgrading Aura CMS

## Upgrading from 0.x to 1.0

Aura CMS 1.0 is a fresh baseline. There is no automated upgrade path for 0.x schemas or data. Existing 0.x applications should remain on `^0.2` until they have tested a manual migration on a complete database and file backup.

The main breaking changes are:

- PHP 8.4 or newer is required.
- Laravel 12 and 13 are supported; Laravel 10 and 11 are not.
- Livewire 4 is required; Livewire 3 compatibility is not maintained.
- The Resource Editor is available only in the `local` environment and must also be enabled by `aura.features.resource_editor`.
- Teams-disabled installations are supported, but custom resources and application migrations must not assume team columns exist.
- Laravel Octane is supported. When `laravel/octane` is installed, Aura resets its process-level static state on every `RequestReceived`/`TaskReceived`/`TickReceived` event; queue callbacks clear the same state after jobs. Custom long-running loops that resolve Aura resources outside the normal request lifecycle may call `Aura::flushState()` at their own boundary. See the [Laravel Octane](docs/performance.md#laravel-octane) section of the performance guide.

For an existing 0.x application:

1. Keep the application pinned to `eminiarts/aura-cms:^0.2` while preparing the migration.
2. Create a fresh 1.0 installation and compare its published configuration and schema with the application.
3. Write and test application-specific migrations for data that must be retained.
4. Update custom Livewire components to Livewire 4 and test both teams-enabled and teams-disabled behavior as applicable.
5. Upgrade on a staging copy, verify rollback from your own backup, and only then deploy the application change.

Package rollback only removes tables and columns created by that package migration. It does not drop host-owned tables such as an existing `users` or `sessions` table.
