<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Contracts\GlobalSearchAdapter;
use Aura\Base\GlobalSearch\GlobalSearchBudget;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

final class GlobalSearchProcessConnectionChurnAdapter implements GlobalSearchAdapter
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

        foreach (range(1, 10) as $iteration) {
            $connection = DB::connection('process_search');
            $connection->select('select 1');
            file_put_contents($marker, 'q', FILE_APPEND);
            DB::purge('process_search');
            unset($connection);
            gc_collect_cycles();
        }

        return collect();
    }
}

final class GlobalSearchProcessConnectionChurnResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-connection-churn';

    public static string $type = 'ProcessSearchConnectionChurn';

    public function globalSearchAdapter()
    {
        return GlobalSearchProcessConnectionChurnAdapter::class;
    }
}
