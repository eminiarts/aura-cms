<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Contracts\GlobalSearchAdapter;
use Aura\Base\GlobalSearch\GlobalSearchBudget;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;

final class GlobalSearchProcessEventForgetConnectionChurnAdapter implements GlobalSearchAdapter
{
    public function search(
        Resource $resource,
        Builder $query,
        Collection $fields,
        string $term,
        int $candidateLimit,
        GlobalSearchBudget $budget,
    ): Collection {
        Event::forget(ConnectionEstablished::class);
        DB::purge('process_search');

        foreach (range(1, 10) as $iteration) {
            DB::connection('process_search')->select('select 1');
            file_put_contents(
                (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER'),
                'q',
                FILE_APPEND,
            );
        }

        return collect();
    }
}

final class GlobalSearchProcessEventForgetConnectionChurnResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-event-forget-churn';

    public static string $type = 'ProcessSearchEventForgetChurn';

    public function globalSearchAdapter()
    {
        return GlobalSearchProcessEventForgetConnectionChurnAdapter::class;
    }
}
