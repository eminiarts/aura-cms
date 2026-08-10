<?php

use Aura\Base\AuraServiceProvider;
use Aura\Base\Providers\AppServiceProvider;
use Aura\Base\Providers\AuthServiceProvider;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessServiceProvider;
use Lab404\Impersonate\ImpersonateServiceProvider;
use Livewire\LivewireServiceProvider;
use Orchestra\Testbench\Foundation\Application;

if (getenv('AURA_GLOBAL_SEARCH_FIXTURE_MODE') === 'bootstrap-capability-scope') {
    file_put_contents(
        (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER'),
        isset($completionCapabilityToken) ? 'visible' : 'hidden',
    );
}

return Application::create(
    basePath: Orchestra\Testbench\default_skeleton_path(),
    options: [
        'extra' => [
            'providers' => [
                LivewireServiceProvider::class,
                ImpersonateServiceProvider::class,
                AuthServiceProvider::class,
                AppServiceProvider::class,
                AuraServiceProvider::class,
                GlobalSearchProcessServiceProvider::class,
            ],
        ],
    ],
);
