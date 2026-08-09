<?php

namespace Aura\Base\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseQueryBuilder;

class GlobalSearchProcessUnionMutationResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-union-mutation';

    public static string $type = 'ProcessSearchUnionMutation';

    public function newGlobalSearchQuery()
    {
        return static::query();
    }

    protected static function booted(): void
    {
        static::addGlobalScope('process-union-mutation', function (Builder $builder): void {
            $builder->getQuery()->beforeQuery(function (BaseQueryBuilder $query): void {
                file_put_contents(
                    (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER'),
                    'union-mutated',
                    FILE_APPEND,
                );
                $query->unionAll(
                    $query->connection
                        ->table('global_search_process_records')
                        ->where('title', 'Fresh Process Needle Other Team'),
                );
            });
        });
    }
}
