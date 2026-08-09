<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\GlobalSearch\FreshProcessGlobalSearchExecutor;
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
        ] as $name) {
            $value = getenv($name);

            if (is_string($value)) {
                $environment[$name] = $value;
            }
        }

        (new FreshProcessGlobalSearchExecutor($artisanPath, $environment, $workingDirectory))->run([
            'operation' => 'discover',
            'context' => ['guard' => 'web', 'user_id' => 1, 'team_id' => 11],
            'query_limit' => 50,
        ], 5_000, 1_048_576);

        return 0;
    }
}
