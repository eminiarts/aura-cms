<?php

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
        'dashboard' => Aura\Base\Livewire\Dashboard::class,
        'profile' => Aura\Base\Livewire\Profile::class,
        'settings' => Aura\Base\Livewire\Settings::class,
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
        'user' => Aura\Base\Resources\User::class,
        'team' => Aura\Base\Resources\Team::class,
        'team-invitation' => Aura\Base\Resources\TeamInvitation::class,
        'role' => Aura\Base\Resources\Role::class,
        'permission' => Aura\Base\Resources\Permission::class,
        'post' => Aura\Base\Resources\Post::class,
        'option' => Aura\Base\Resources\Option::class,
        'attachment' => Aura\Base\Resources\Attachment::class,
        'category' => Aura\Base\Resources\Category::class,
        'tag' => Aura\Base\Resources\Tag::class,
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
        'sidebar-type' => 'primary',
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
        'create_teams' => true,
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
