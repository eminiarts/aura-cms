<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Exceptions\GlobalSearchExecutionUnavailable;
use Aura\Base\GlobalSearch\FreshProcessGlobalSearchExecutor;
use Aura\Base\GlobalSearch\GlobalSearchWorkerContext;
use Aura\Base\Resources\User;
use Illuminate\Console\Command;

final class GlobalSearchSupervisorFixtureCommand extends Command
{
    protected $signature = 'aura:test-supervise-global-search';

    public function handle(): int
    {
        config(['aura.global_search.execution_backend' => 'process']);
        $artisanPath = getenv('AURA_GLOBAL_SEARCH_WORKER_ARTISAN');
        $workingDirectory = getenv('AURA_GLOBAL_SEARCH_WORKING_DIRECTORY');

        if (! is_string($artisanPath) || ! is_string($workingDirectory)) {
            return 1;
        }

        $environment = [];

        foreach ([
            'APP_ENV',
            'AURA_GLOBAL_SEARCH_HOOK_MARKER',
            'AURA_GLOBAL_SEARCH_PROCESS_FIXTURE',
            'AURA_GLOBAL_SEARCH_FIXTURE_MODE',
            'DB_CONNECTION',
            'DB_DATABASE',
            'DB_DATABASE_TENANT_A',
            'DB_DATABASE_TENANT_B',
        ] as $name) {
            $value = getenv($name);

            if (is_string($value)) {
                $environment[$name] = $value;
            }
        }

        $user = User::on(config('database.default'))->withoutGlobalScopes()->find(1);
        $context = $user === null
            ? null
            : (new GlobalSearchWorkerContext)->create($user, 'web');

        if ($context === null) {
            return 1;
        }

        try {
            (new FreshProcessGlobalSearchExecutor(
                artisanPath: $artisanPath,
                environment: $environment,
                workingDirectory: $workingDirectory,
                autoloadPath: dirname(__DIR__, 2).'/vendor/autoload.php',
                bootstrapPath: __DIR__.'/GlobalSearchWorkerBootstrap.php',
            ))->run([
                'operation' => 'discover',
                'context' => $context,
                'query_limit' => 50,
            ], 5_000, 1_048_576);
        } catch (GlobalSearchExecutionUnavailable) {
            return 1;
        }

        return 0;
    }
}
