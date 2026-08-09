<?php

namespace Aura\Base\GlobalSearch;

use Aura\Base\Resource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as BaseQueryBuilder;

final class GlobalSearchQuerySealer
{
    private const MAXIMUM_CALLBACK_PASSES = 8;

    private const MAXIMUM_CALLBACKS = 64;

    public function seal(Resource $resource, Builder $query, Authenticatable $user): ?Builder
    {
        $preparedQuery = $query->applyScopes();
        $preparedQuery = $resource->applyGlobalSearchVisibility($preparedQuery, $user);

        if (! $preparedQuery instanceof Builder) {
            return null;
        }

        $executedCallbacks = $this->drainCallbacks($preparedQuery->getQuery());

        if ($executedCallbacks === null) {
            return null;
        }

        $sealedQuery = $preparedQuery->applyScopes();
        $sealedQuery = $resource->applyGlobalSearchVisibility($sealedQuery, $user);

        if (! $sealedQuery instanceof Builder) {
            return null;
        }

        $baseQuery = $sealedQuery->getQuery();

        if (! is_array($baseQuery->beforeQueryCallbacks)
            || count($baseQuery->beforeQueryCallbacks) > $executedCallbacks) {
            return null;
        }

        $baseQuery->beforeQueryCallbacks = [];
        $sealedQuery->withoutGlobalScopes();

        return $sealedQuery;
    }

    private function drainCallbacks(BaseQueryBuilder $query): ?int
    {
        $executedCallbacks = 0;

        for ($pass = 0; $pass < self::MAXIMUM_CALLBACK_PASSES; $pass++) {
            if (! is_array($query->beforeQueryCallbacks)) {
                return null;
            }

            if ($query->beforeQueryCallbacks === []) {
                return $executedCallbacks;
            }

            $callbacks = $query->beforeQueryCallbacks;
            $query->beforeQueryCallbacks = [];
            $executedCallbacks += count($callbacks);

            if ($executedCallbacks > self::MAXIMUM_CALLBACKS) {
                return null;
            }

            foreach ($callbacks as $callback) {
                $callback($query);
            }
        }

        return $query->beforeQueryCallbacks === [] ? $executedCallbacks : null;
    }
}
