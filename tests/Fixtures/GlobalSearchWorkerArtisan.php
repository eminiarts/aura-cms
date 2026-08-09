<?php

use Aura\Base\AuraServiceProvider;
use Aura\Base\Providers\AppServiceProvider;
use Aura\Base\Providers\AuthServiceProvider;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessServiceProvider;
use Illuminate\Foundation\Application as LaravelApplication;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Foundation\Application;
use Symfony\Component\Console\Input\ArgvInput;

require dirname(__DIR__, 2).'/vendor/autoload.php';

$app = Application::create(
    basePath: Orchestra\Testbench\default_skeleton_path(),
    options: [
        'extra' => [
            'providers' => [
                LivewireServiceProvider::class,
                AuthServiceProvider::class,
                AppServiceProvider::class,
                AuraServiceProvider::class,
                GlobalSearchProcessServiceProvider::class,
            ],
        ],
    ],
);

/** @var LaravelApplication $app */
$status = $app->handleCommand(new ArgvInput);

exit($status);
