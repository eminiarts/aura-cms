<?php

use Aura\Base\GlobalSearch\DatabaseGlobalSearchAdapter;
use Aura\Base\Livewire\Dashboard;
use Aura\Base\Livewire\MediaManager;
use Aura\Base\Livewire\Profile;
use Aura\Base\Livewire\Settings;
use Aura\Base\Resources\Attachment;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;

return [
    /*
    |--------------------------------------------------------------------------
    | Path
    |--------------------------------------------------------------------------
    |
    | The default is `admin` but you can change it to whatever works best and
    | doesn't conflict with the routing in your application.
    |
    */

    'path' => env('AURA_PATH', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Domain
    |--------------------------------------------------------------------------
    |
    | You may change the domain where AURA should be active. If the domain
    | is empty, all domains will be valid.
    |
    */

    'domain' => env('AURA_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Teams
    |--------------------------------------------------------------------------
    |
    | If you change this to false, you will not be able to create teams.
    | You will need to rerun your migrations to remove the teams table.
    | Run `php artisan migrate:fresh`.
    |
    */

    'teams' => env('AURA_TEAMS', true),

    'security' => [
        'modal_requests' => [
            // The file store is suitable only for a single application host.
            // Multi-node deployments must use one shared atomic store, such
            // as Redis or database, that every node can reach.
            'cache_store' => env('AURA_MODAL_REQUEST_CACHE_STORE', 'file'),
            'ttl_seconds' => 120,
        ],
        'table_mutations' => [
            'chunk_size' => 100,
            'max_records' => 500,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Schema Migration Locks
    |--------------------------------------------------------------------------
    |
    | Bound how long schema updates wait for another process that is changing
    | the same physical database table. PostgreSQL and MySQL locks coordinate
    | through the database server. SQLite uses a host-local temporary flock;
    | it does not coordinate shared SQLite/NFS files across multiple hosts.
    | Polling applies to PostgreSQL/SQLite; MySQL delegates the configured
    | timeout to GET_LOCK().
    |
    */

    'schema' => [
        'lock_timeout' => 30,
        'lock_poll_interval_milliseconds' => 50,
    ],

    /*
    |--------------------------------------------------------------------------
    | Component Slots
    |--------------------------------------------------------------------------
    |
    | A non-null class is an explicit host choice. Leave a slot null to allow
    | one plugin candidate to win, with Aura's component as the fallback.
    |
    */

    'component-slots' => [
        'global-search' => null,
        'media-manager' => null,
    ],

    /*
    |--------------------------------------------------------------------------
    | Components
    |--------------------------------------------------------------------------
    |
    | You can customise the Livewire components that Aura uses
    |
    */

    'components' => [
        'dashboard' => Dashboard::class,
        'profile' => Profile::class,
        'settings' => Settings::class,

        // Deprecated host compatibility key. New applications should configure
        // `aura.component-slots.media-manager`; plugins must declare candidates
        // through Aura::registerComponentSlots() instead of changing config.
        'media-manager' => MediaManager::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | You can customise the resources that Aura uses
    |
    */

    'resources' => [
        'user' => User::class,
        'team' => Team::class,
        'team-invitation' => TeamInvitation::class,
        'role' => Role::class,
        'permission' => Permission::class,
        'option' => Option::class,
        'attachment' => Attachment::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Theme
    |--------------------------------------------------------------------------
    |
    | You can customise the Aura theme
    |
    */

    'theme' => [
        'color-palette' => 'aura',
        'gray-color-palette' => 'slate',
        'darkmode-type' => 'auto',

        /*
        | The system stack is the default and makes no network requests. To use
        | a custom font, publish/serve its CSS in the host application and set
        | stylesheet to a local public path such as "fonts/brand.css".
        */
        'font' => [
            'family' => [
                'ui-sans-serif',
                'system-ui',
                'sans-serif',
                'Apple Color Emoji',
                'Segoe UI Emoji',
                'Segoe UI Symbol',
                'Noto Color Emoji',
            ],
            'stylesheet' => false,
        ],

        /*
        | Semantic colors are RGB channel strings so Tailwind opacity modifiers
        | keep working. CSS custom-property references are also supported.
        */
        'colors' => [
            'light' => [
                'primary' => 'var(--primary-600)',
                'background' => '255 255 255',
                'panel' => '250 250 250',
                'border' => '228 228 231',
                'text' => '24 24 27',
                'muted' => '82 82 91',
                'success' => '22 163 74',
                'warning' => '217 119 6',
                'danger' => '220 38 38',
            ],
            'dark' => [
                'primary' => 'var(--primary-600)',
                'background' => '9 9 11',
                'panel' => '24 24 27',
                'border' => '63 63 70',
                'text' => '244 244 245',
                'muted' => '161 161 170',
                'success' => '22 163 74',
                'warning' => '217 119 6',
                'danger' => '220 38 38',
            ],
        ],

        'sidebar-size' => 'standard',
        'sidebar-type' => 'dark',
        'sidebar-darkmode-type' => 'dark',

        'login-bg' => false,
        'login-bg-darkmode' => false,

        'app-favicon' => false,
        'app-favicon-darkmode' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Views
    |--------------------------------------------------------------------------
    |
    | You can customise the views that Aura uses
    |
    */

    'views' => [
        'layout' => 'aura::layouts.app',
        'login-layout' => 'aura::layout.login',
        'dashboard' => 'aura::dashboard',
        'index' => 'aura::index',
        'view' => 'aura::view',
        'create' => 'aura::create',
        'edit' => 'aura::edit',
        'navigation' => 'aura::components.navigation',
        'logo' => 'aura::application-logo',
    ],

    /*
    |--------------------------------------------------------------------------
    | Features
    |--------------------------------------------------------------------------
    |
    | You can customise the features that Aura uses
    |
    */

    'features' => [
        'global_search' => true,
        'bookmarks' => true,
        'last_visited_pages' => true,
        'notifications' => true,
        'plugins' => true,
        'settings' => true,
        'profile' => true,
        'create_resource' => true,
        'resource_view' => true,
        'resource_edit' => true,
        'resource_editor' => config('app.env') == 'local' ? true : false,
        'custom_tables_for_resources' => false, // default = false
        // By default, resources are using the posts and meta table.
        // If you want to use custom tables by default, you can set this to true.

        // Append the resolved `fields` accessor to every resource's array/JSON
        // serialization (toArray()/toJson()). This is the historical default
        // (true) and is kept for backward compatibility. Resolving `fields`
        // computes every input field's value, which is expensive when many
        // models are serialized (e.g. large tables under Livewire). New apps
        // that do not depend on `fields` appearing in serialized output should
        // set this to false for better large-table performance; callers that
        // still need it can opt in per model via `$model->append('fields')`.
        'legacy_fields_append' => true,

    ],

    /*
    |--------------------------------------------------------------------------
    | Field Value Defaults
    |--------------------------------------------------------------------------
    |
    | Date-only values are never timezone shifted. Datetime values are stored
    | in one timezone and rendered in the configured display timezone. Null
    | timezone defaults follow the host application's timezone.
    |
    */

    'fields' => [
        'date' => [
            'display_format' => 'd.m.Y',
        ],
        'datetime' => [
            'display_format' => 'd.m.Y H:i',
            'storage_timezone' => null,
            'display_timezone' => null,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Global Search
    |--------------------------------------------------------------------------
    |
    | Search work is bounded at the resource, field, candidate, and global
    | levels. Ranking combines match quality with each searchable field's
    | configured weight; ties use resource registration order and model key.
    |
    */

    'global_search' => [
        'adapter' => DatabaseGlobalSearchAdapter::class,
        'execution_backend' => 'process',
        'worker_php' => null,
        'worker_artisan' => null,
        'worker_autoload' => null,
        'worker_bootstrap' => null,
        'worker_connections' => ['@default'],
        'minimum_query_length' => 2,
        'maximum_query_length' => 64,
        'max_resources' => 25,
        'max_resource_candidates' => 100,
        'max_fields_per_resource' => 8,
        'candidate_limit' => 100,
        'per_resource_limit' => 5,
        'global_limit' => 15,
        'max_title_dependencies' => 4,
        'max_queries_per_resource' => 4,
        'max_total_queries' => 100,
        'per_resource_timeout_ms' => 500,
        'total_timeout_ms' => 3_000,
        'isolated_payload_bytes' => 1_048_576,
        'icon_bytes' => 8_192,
        'allowed_route_names' => ['aura.*'],
        'ranking' => [
            'exact' => 300,
            'prefix' => 200,
            'contains' => 100,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Auth
    |--------------------------------------------------------------------------
    |
    | You can customise the auth features that Aura uses
    |
    */

    'auth' => [
        'registration' => env('AURA_REGISTRATION', true),
        'redirect' => '/admin',
        '2fa' => true,
        'user_invitations' => true,
        'invitation_expiry' => 7,
        'invitation_connections' => [],
        'invitation_legacy_connection' => null,
        'create_teams' => env('AURA_CREATE_TEAMS', true),
    ],

    /*
    |--------------------------------------------------------------------------
    | Media
    |--------------------------------------------------------------------------
    |
    | You can customise the media features that Aura uses
    |
    */

    'media' => [
        // Filesystem disk (config/filesystems.php) that uploads, thumbnails and
        // served images live on, plus the base folder within that disk. The
        // defaults keep media on the public disk under `media/`; point them at
        // e.g. an S3 disk to store uploads off the local filesystem.
        'disk' => 'public',
        'path' => 'media',
        'quality' => 80,
        'restrict_to_dimensions' => true,

        'max_file_size' => 10000,

        'generate_thumbnails' => true,
        'security' => [
            // Configure an explicitly named, non-default Laravel database store
            // with dedicated, distinct cache and lock tables. It must not alias
            // the default cache's physical tables, including database children
            // of a default failover store, custom defaults that resolve to a database,
            // and aliases created by connection prefixes or alternate paths to the
            // same database. Use unqualified lowercase base-table identifiers;
            // Aura binds operations to the validated schema-qualified tables.
            // Aura maintains reserved persistent identity rows and validates them
            // under the same transaction as each operation so same-name replacement
            // fails closed without privileged database metadata access.
            // All views, temporary tables, and synonyms fail closed.
            // Also set Laravel's global cache.serializable_classes option
            // to false. File, Redis, DynamoDB,
            // Memcached, failover, process-local, custom, and proxied stores fail
            // closed. Multi-node deployments need one shared network database.
            'cache_store' => null,
            'owner_token_ttl' => 900,
            'selection_ttl' => 15,
            'selection_retention' => 60,
        ],
        'dimensions' => [
            [
                'name' => 'xs',
                'width' => 200,
            ],
            [
                'name' => 'sm',
                'width' => 600,
            ],
            [
                'name' => 'md',
                'width' => 1200,
            ],
            [
                'name' => 'lg',
                'width' => 2000,
            ],
            [
                'name' => 'thumbnail',
                'width' => 600,
                'height' => 600,
            ],
        ],
    ],
];
