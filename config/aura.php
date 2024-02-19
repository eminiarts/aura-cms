<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Aura Path
    |--------------------------------------------------------------------------
    |
    | The default is `admin` but you can change it to whatever works best and
    | doesn't conflict with the routing in your application.
    |
    */

    'path' => env('AURA_PATH', 'admin'),

    /*
    |--------------------------------------------------------------------------
    | Aura Core Path
    |--------------------------------------------------------------------------
    |
    | This is the path which AURA will use to load its core routes and assets.
    | You may change it if it conflicts with your other routes.
    |
    */

    'core_path' => env('AURA_CORE_PATH', 'aura'),

    /*
    |--------------------------------------------------------------------------
    | Aura Domain
    |--------------------------------------------------------------------------
    |
    | You may change the domain where AURA should be active. If the domain
    | is empty, all domains will be valid.
    |
    */

    'domain' => env('AURA_DOMAIN'),

    /*
    |--------------------------------------------------------------------------
    | Teams & Multi-tenancy
    |--------------------------------------------------------------------------
    |
    | If you change this to false, you will not be able to create teams.
    | You will need to rerun your migrations to remove the teams table.
    | Run `php artisan migrate:fresh`.
    |
    */

    'teams' => true,

    /*
   |--------------------------------------------------------------------------
   | Livewire Components
   |--------------------------------------------------------------------------
   |
   | You can customise the Livewire components that AURA uses to render
   |
   */

    'components' => [
        'dashboard' => Aura\Base\Livewire\Dashboard::class,
        'profile' => Aura\Base\Livewire\Profile::class,
        'team_settings' => Aura\Base\Livewire\TeamSettings::class,
        'config' => Aura\Base\Livewire\Config::class,
    ],

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

    'views' => [
        'layout' => 'aura::layouts.app',
        'dashboard' => 'aura::dashboard',
        'index' => 'aura::index',
        'view' => 'aura::view',
        'create' => 'aura::create',
        'edit' => 'aura::edit',
        'navigation' => 'aura::components.navigation',
    ],

    'features' => [
        'global_search' => true,
        'notifications' => true,
        'bookmarks' => true,

        'plugins' => true,
        'flows' => false,
        'forms' => true,

        'resource_editor' => config('app.env') == 'production' ? false : true,
        'theme_options' => true,
        'global_config' => true,

        'user_profile' => true,

        'create_resource' => true,

        'resource_view' => true,
        'resource_edit' => true,

        'register' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Resources
    |--------------------------------------------------------------------------
    |
    | This is the namespace and directory that Aura will automatically
    | register resources from. You may also register resources here.
    |
    */

    'paths' => [
        'resources' => [
            'namespace' => 'App\\Aura\\Resources',
            'path' => app_path('Aura/Resources'),
            'register' => [],
        ],

        'fields' => [
            'namespace' => 'App\\Aura\\Fields',
            'path' => app_path('Aura/Fields'),
            'register' => [],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Widgets
    |--------------------------------------------------------------------------
    |
    | This is the namespace and directory that Aura will automatically
    | register dashboard widgets from. You may also register widgets here.
    |
    */

    'widgets' => [
        'namespace' => 'App\\Aura\\Widgets',
        'path' => app_path('Aura/Widgets'),
        'register' => [],
    ],

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | You may customise the middleware stack that Aura uses to handle
    | requests.
    |
    */

    'middleware' => [
        'aura-admin' => [
            'web',
            'auth',
        ],

        'aura-guest' => [
            'web',
        ],

        'aura-base' => [
            \App\Http\Middleware\EncryptCookies::class,
            \Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse::class,
            \Illuminate\Session\Middleware\StartSession::class,
            \Illuminate\View\Middleware\ShareErrorsFromSession::class,
            \App\Http\Middleware\VerifyCsrfToken::class,
            \Illuminate\Routing\Middleware\SubstituteBindings::class,
        ],
    ],
];
