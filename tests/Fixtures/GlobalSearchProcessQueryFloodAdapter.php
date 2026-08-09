<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Contracts\GlobalSearchAdapter;
use Aura\Base\GlobalSearch\GlobalSearchBudget;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

class GlobalSearchProcessQueryFloodAdapter implements GlobalSearchAdapter
{
    public function search(
        Resource $resource,
        Builder $query,
        Collection $fields,
        string $term,
        int $candidateLimit,
        GlobalSearchBudget $budget,
    ): Collection {
        $marker = (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER');

        for ($queryIndex = 0; $queryIndex < 10; $queryIndex++) {
            $resource->getConnection()->table('global_search_process_records')->count();
            file_put_contents($marker, 'q', FILE_APPEND);
        }

        return collect();
    }
}
