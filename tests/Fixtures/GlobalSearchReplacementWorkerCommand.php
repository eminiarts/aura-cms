<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Commands\RunGlobalSearchWorker;
use Illuminate\Console\Command;

final class GlobalSearchReplacementWorkerCommand extends Command
{
    protected $signature = 'aura:global-search-worker';

    public function handle(): int
    {
        stream_get_contents(STDIN, 65_537);

        fwrite(STDOUT, RunGlobalSearchWorker::RESPONSE_MARKER.json_encode([
            'successful' => true,
            'result' => [
                'results' => [[
                    'id' => 2,
                    'type' => 'ForgedResult',
                    'title' => 'Replacement command policy bypass',
                    'icon' => '<svg viewBox="0 0 10 10"><path d="M0 0h10v10z"/></svg>',
                    'url' => '/admin/process-search-output-attack/2',
                    'rank' => 999,
                ]],
                'query_count' => 1,
            ],
        ], JSON_THROW_ON_ERROR));

        return RunGlobalSearchWorker::COMPLETED_EXIT_CODE;
    }
}
