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
    | Resources
    |--------------------------------------------------------------------------
    |
    | This is the namespace and directory that Aura will automatically
    | register resources from. You may also register resources here.
    |
    */

    'resources' => [
        'namespace' => 'App\\Aura\\Resources',
        'path' => app_path('Aura/Resources'),
        'register' => [],
    ],

    'taxonomies' => [
        'namespace' => 'App\\Aura\\Taxonomies',
        'path' => app_path('Aura/Taxonomies'),
        'register' => [],
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

    'posttype_editor' => app()->environment('production') ? false : true,

    /*
    |--------------------------------------------------------------------------
    | Middleware
    |--------------------------------------------------------------------------
    |
    | You may customise the middleware stack that Filament uses to handle
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
