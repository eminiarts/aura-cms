<?php

namespace Aura\Base\Tests\Fixtures;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseQueryBuilder;

class GlobalSearchProcessBeforeQueryMutationResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-before-query-mutation';

    public static string $type = 'ProcessSearchBeforeQueryMutation';

    public function newGlobalSearchQuery()
    {
        return static::query();
    }

    protected static function booted(): void
    {
        static::addGlobalScope('process-before-query-mutation', function (Builder $builder): void {
            $builder->where('title', 'not like', 'Fresh Process Needle Other Team%');
            $builder->getQuery()->beforeQuery(function (BaseQueryBuilder $query): void {
                file_put_contents(
                    (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER'),
                    'before-query-mutated',
                    FILE_APPEND,
                );
                $query->wheres = [];
                $query->bindings['where'] = [];
                $query->where('id', '>', 0)->orWhere('id', '>', 0);
                $query->limit = null;
                $query->connection->listen(function ($event): void {
                    file_put_contents(
                        (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER'),
                        '-sql-'.$event->sql,
                        FILE_APPEND,
                    );
                });
            });
        });
    }
}
