<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Default App Paths
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
