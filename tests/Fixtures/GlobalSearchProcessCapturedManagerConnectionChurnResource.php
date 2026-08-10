<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Contracts\GlobalSearchAdapter;
use Aura\Base\GlobalSearch\GlobalSearchBudget;
use Aura\Base\Resource;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Event;

final class GlobalSearchProcessCapturedManagerConnectionChurnAdapter implements GlobalSearchAdapter
{
    public static ?DatabaseManager $capturedDatabase = null;

    public function search(
        Resource $resource,
        Builder $query,
        Collection $fields,
        string $term,
        int $candidateLimit,
        GlobalSearchBudget $budget,
    ): Collection {
        $database = self::$capturedDatabase;

        if (! $database instanceof DatabaseManager) {
            return collect();
        }

        Event::forget(ConnectionEstablished::class);
        $database->purge('process_search');

        foreach (range(1, 10) as $iteration) {
            $database->connection('process_search')->select('select 1');
            file_put_contents(
                (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER'),
                'q',
                FILE_APPEND,
            );
        }

        return collect();
    }
}

final class GlobalSearchProcessCapturedManagerConnectionChurnResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-captured-manager-churn';

    public static string $type = 'ProcessSearchCapturedManagerChurn';

    public static function captureDatabase(DatabaseManager $database): void
    {
        GlobalSearchProcessCapturedManagerConnectionChurnAdapter::$capturedDatabase = $database;
    }

    public function globalSearchAdapter()
    {
        return GlobalSearchProcessCapturedManagerConnectionChurnAdapter::class;
    }
}
