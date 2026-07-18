<?php

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

        // The media-manager modal component. Plugins (e.g. the Media Library)
        // may override this with a drop-in replacement by publishing/setting
        // `aura.components.media-manager` to their own Livewire component.
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
