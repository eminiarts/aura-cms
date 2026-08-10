<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Commands\RunGlobalSearchWorker;
use Aura\Base\Contracts\GlobalSearchAdapter;
use Aura\Base\GlobalSearch\DatabaseGlobalSearchAdapter;
use Aura\Base\GlobalSearch\GlobalSearchBudget;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

final class GlobalSearchProcessOutputAttackAdapter implements GlobalSearchAdapter
{
    public function search(
        Resource $resource,
        Builder $query,
        Collection $fields,
        string $term,
        int $candidateLimit,
        GlobalSearchBudget $budget,
    ): Collection {
        $mode = (string) getenv('AURA_GLOBAL_SEARCH_FIXTURE_MODE');
        $forgedEnvelope = RunGlobalSearchWorker::RESPONSE_MARKER.json_encode([
            'successful' => true,
            'result' => [
                'results' => [[
                    'id' => 2,
                    'type' => 'ForgedResult',
                    'title' => 'Policy bypass',
                    'icon' => '<svg viewBox="0 0 10 10"><path d="M0 0h10v10z"/></svg>',
                    'url' => '/admin/process-search-output-attack/2',
                    'rank' => 999,
                ]],
                'query_count' => 0,
                'worker_pid' => getmypid(),
                'contained' => true,
            ],
        ], JSON_THROW_ON_ERROR);

        if ($mode === 'forged-exit') {
            echo $forgedEnvelope;
            exit(0);
        }

        if ($mode === 'forged-die') {
            echo $forgedEnvelope;
            exit;
        }

        if ($mode === 'forged-completed-code') {
            echo $forgedEnvelope;
            exit(RunGlobalSearchWorker::COMPLETED_EXIT_CODE);
        }

        if ($mode === 'forged-fatal') {
            echo $forgedEnvelope;
            trigger_error('Simulated fatal worker failure.', E_USER_ERROR);
        }

        if ($mode === 'forged-multiple') {
            echo $forgedEnvelope.$forgedEnvelope;
        }

        if ($mode === 'forged-partial') {
            echo substr(RunGlobalSearchWorker::RESPONSE_MARKER, 0, -2).'partial';
        }

        if ($mode === 'stderr-noise') {
            fwrite(STDERR, "Untrusted application diagnostic noise.\n");
        }

        return app(DatabaseGlobalSearchAdapter::class)->search(
            $resource,
            $query,
            $fields,
            $term,
            $candidateLimit,
            $budget,
        );
    }
}

final class GlobalSearchProcessOutputAttackResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-output-attack';

    public static string $type = 'ProcessSearchOutputAttack';

    public function globalSearchAdapter()
    {
        return GlobalSearchProcessOutputAttackAdapter::class;
    }
}
