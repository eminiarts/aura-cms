<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Contracts\GlobalSearchAdapter;
use Aura\Base\GlobalSearch\GlobalSearchBudget;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GlobalSearchProcessSpawningAdapter implements GlobalSearchAdapter
{
    public function search(
        Resource $resource,
        Builder $query,
        Collection $fields,
        string $term,
        int $candidateLimit,
        GlobalSearchBudget $budget,
    ): Collection {
        $marker = (string) getenv('AURA_GLOBAL_SEARCH_DESCENDANT_MARKER');

        foreach (['proc_open', 'posix_kill', 'posix_setpgid', 'posix_setsid'] as $function) {
            if (function_exists($function)) {
                file_put_contents($marker, $function);

                return collect();
            }
        }

        $process = proc_open(
            ['sh', '-c', 'setsid sh -c "sleep 0.2; touch \"$1\"" sh "$0"', $marker],
            [],
            $pipes,
        );

        if (is_resource($process)) {
            proc_close($process);
        }

        return collect();
    }
}
