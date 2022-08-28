<?php

use Eminiarts\Aura\Http\Middleware\Authenticate;

// config for Eminiarts/Aura
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
        'register' => [
            // Widgets\AccountWidget::class,
            // Widgets\FilamentInfoWidget::class,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Livewire
    |--------------------------------------------------------------------------
    |
    | This is the namespace and directory that Aura will automatically
    | register Livewire components inside.
    |
    */


    'livewire' => [
        'namespace' => 'App\\Aura',
        'path' => app_path('Aura'),
    ],


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
        'admin' => [
            'web',
            'auth'
        ],

        // 'base' => [
        //     EncryptCookies::class,
        //     AddQueuedCookiesToResponse::class,
        //     StartSession::class,
        //     AuthenticateSession::class,
        //     ShareErrorsFromSession::class,
        //     VerifyCsrfToken::class,
        //     SubstituteBindings::class,
        //     DispatchServingFilamentEvent::class,
        //     MirrorConfigToSubpackages::class,
        // ],
    ],
];
