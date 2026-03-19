<?php

use Aura\Base\Http\Middleware\EncryptCookies;
use Aura\Base\Http\Middleware\VerifyCsrfToken;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

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
            EncryptCookies::class,
            AddQueuedCookiesToResponse::class,
            StartSession::class,
            ShareErrorsFromSession::class,
            VerifyCsrfToken::class,
            SubstituteBindings::class,
        ],
    ],
];
