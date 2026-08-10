<?php

namespace Aura\Base\GlobalSearch;

use Aura\Base\Resource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Database\Query\Expression;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Grammar;
use Illuminate\Database\Query\Builder as BaseQueryBuilder;
use Throwable;

final class GlobalSearchQuerySealer
{
    private const MAXIMUM_CALLBACK_PASSES = 8;

    private const MAXIMUM_CALLBACKS = 64;

    private const MAXIMUM_QUERY_DEPTH = 16;

    private const MAXIMUM_QUERY_NODES = 2_048;

    public function seal(Resource $resource, Builder $query, Authenticatable $user): ?Builder
    {
        $preparedQuery = (clone $query)->applyScopes();
        $preparedQuery = $resource->applyGlobalSearchVisibility($preparedQuery, $user);

        if (! $preparedQuery instanceof Builder) {
            return null;
        }

        $baseQuery = $preparedQuery->getQuery();
        $callbackShape = $this->immutableCallbackShape($baseQuery);
        $authorizedShape = $this->immutableAuthorizedShape($baseQuery);
        $allowedRawFragments = $this->rawFragments($baseQuery);
        $allowedOperators = $this->allowedOperators($baseQuery);
        $callbacks = $baseQuery->beforeQueryCallbacks;

        if ($callbackShape === null
            || $authorizedShape === null
            || $allowedRawFragments === null
            || $allowedOperators === null
            || ! is_array($callbacks)
            || count($callbacks) > self::MAXIMUM_CALLBACKS
            || ! $this->whereClausesAreSafe($baseQuery, $allowedOperators)) {
            return null;
        }

        $baseQuery->beforeQueryCallbacks = [];
        $callbackQuery = clone $baseQuery;
        $callbackQuery->wheres = [];
        $callbackQuery->bindings['where'] = [];
        $callbackQuery->beforeQueryCallbacks = $callbacks;
        $executedCallbacks = $this->drainCallbacks($callbackQuery);

        $verificationQuery = (clone $query)->applyScopes();
        $verificationQuery = $resource->applyGlobalSearchVisibility($verificationQuery, $user);

        if (! $verificationQuery instanceof Builder) {
            return null;
        }

        $verificationBaseQuery = $verificationQuery->getQuery();

        if ($executedCallbacks === null
            || $this->immutableAuthorizedShape($baseQuery) !== $authorizedShape
            || $this->immutableAuthorizedShape($verificationBaseQuery) !== $authorizedShape
            || $this->immutableCallbackShape($callbackQuery) !== $callbackShape
            || ! $this->rawFragmentsAreSubset($callbackQuery, $allowedRawFragments)
            || ! $this->whereClausesAreSafe($callbackQuery, $allowedOperators)
            || ! $this->appendCallbackConstraints($baseQuery, $callbackQuery)) {
            return null;
        }

        $baseQuery->beforeQueryCallbacks = [];
        $preparedQuery->withoutGlobalScopes();

        return $this->queryIsSafeForDefaultAdapter($resource, $baseQuery)
            ? $preparedQuery
            : null;
    }

    /** @return array<string, true>|null */
    private function allowedOperators(BaseQueryBuilder $query): ?array
    {
        try {
            $operators = [...$query->operators, ...$query->grammar->getOperators()];
        } catch (Throwable) {
            return null;
        }

        $allowed = [];

        foreach ($operators as $operator) {
            if (! is_string($operator) || $operator === '' || strlen($operator) > 64) {
                return null;
            }

            $allowed[strtolower($operator)] = true;
        }

        return $allowed;
    }

    private function appendCallbackConstraints(
        BaseQueryBuilder $authorizedQuery,
        BaseQueryBuilder $callbackQuery,
    ): bool {
        if (! is_array($callbackQuery->wheres)
            || ! is_array($callbackQuery->bindings)
            || ! is_array($callbackQuery->bindings['where'] ?? null)
            || ! is_array($callbackQuery->bindings['order'] ?? null)
            || ! is_array($authorizedQuery->bindings)
            || ! is_array($authorizedQuery->bindings['order'] ?? null)) {
            return false;
        }

        try {
            if ($callbackQuery->wheres !== []) {
                $nestedQuery = $authorizedQuery->forNestedWhere();
                $nestedQuery->wheres = $callbackQuery->wheres;
                $nestedQuery->bindings['where'] = $callbackQuery->bindings['where'];
                $authorizedQuery->addNestedWhereQuery($nestedQuery, 'and');
            }

            $authorizedQuery->orders = $callbackQuery->orders;
            $authorizedQuery->limit = $callbackQuery->limit;
            $authorizedQuery->offset = $callbackQuery->offset;
            $authorizedQuery->bindings['order'] = $callbackQuery->bindings['order'] ?? [];
        } catch (Throwable) {
            return false;
        }

        return true;
    }

    /**
     * @param  array<int, string>  $fragments
     * @param  array<int, true>  $seenQueries
     */
    private function collectRawFragments(
        mixed $value,
        Grammar $grammar,
        array &$fragments,
        array &$seenQueries,
        int &$remainingNodes,
        int $depth,
        string $path,
        bool $trackLocation,
    ): bool {
        if ($depth > self::MAXIMUM_QUERY_DEPTH || $remainingNodes-- < 1) {
            return false;
        }

        if ($value instanceof Expression) {
            $rawValue = $value->getValue($grammar);
            $fragments[] = $trackLocation
                ? $path.':expression:'.spl_object_id($value).':'.(string) $rawValue
                : 'expression:'.get_debug_type($value).':'.(string) $rawValue;

            return true;
        }

        if ($value instanceof Builder) {
            return $this->collectRawFragments(
                $value->getQuery(),
                $value->getQuery()->grammar,
                $fragments,
                $seenQueries,
                $remainingNodes,
                $depth + 1,
                $path.'.eloquent',
                $trackLocation,
            );
        }

        if ($value instanceof BaseQueryBuilder) {
            $queryId = spl_object_id($value);

            if (isset($seenQueries[$queryId])) {
                return true;
            }

            $seenQueries[$queryId] = true;

            foreach ([
                'columns' => $value->columns,
                'from' => $value->from,
                'joins' => $value->joins,
                'wheres' => $value->wheres,
                'groups' => $value->groups,
                'havings' => $value->havings,
                'orders' => $value->orders,
                'unions' => $value->unions,
                'union_orders' => $value->unionOrders,
            ] as $componentName => $component) {
                if (! $this->collectRawFragments(
                    $component,
                    $value->grammar,
                    $fragments,
                    $seenQueries,
                    $remainingNodes,
                    $depth + 1,
                    $path.'.'.$componentName,
                    $trackLocation,
                )) {
                    return false;
                }
            }

            return true;
        }

        if (! is_array($value)) {
            return true;
        }

        if (is_string($value['type'] ?? null)
            && strtolower($value['type']) === 'raw'
            && (is_string($value['sql'] ?? null) || is_numeric($value['sql'] ?? null))) {
            $fragments[] = $trackLocation
                ? $path.':clause:'.(string) $value['sql']
                : 'clause:'.(string) $value['sql'];
        }

        foreach ($value as $key => $nestedValue) {
            if (! $this->collectRawFragments(
                $nestedValue,
                $grammar,
                $fragments,
                $seenQueries,
                $remainingNodes,
                $depth + 1,
                $path.'['.(string) $key.']',
                $trackLocation,
            )) {
                return false;
            }
        }

        return true;
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

    /** @return array<string, mixed>|null */
    private function immutableAuthorizedShape(BaseQueryBuilder $query): ?array
    {
        try {
            $shape = clone $query;
            $shape->beforeQueryCallbacks = [];

            return [
                'sql' => $shape->toSql(),
                'bindings' => $shape->getRawBindings(),
                'connection' => spl_object_id($shape->connection),
                'grammar' => spl_object_id($shape->grammar),
                'processor' => spl_object_id($shape->processor),
                'operators' => $shape->operators,
                'bitwise_operators' => $shape->bitwiseOperators,
                'use_write_pdo' => $shape->useWritePdo,
                'fetch_using' => $shape->fetchUsing,
                'timeout' => $shape->timeout,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /** @return array<string, mixed>|null */
    private function immutableCallbackShape(BaseQueryBuilder $query): ?array
    {
        try {
            $shape = clone $query;
            $shape->wheres = [];
            $shape->orders = null;
            $shape->limit = null;
            $shape->offset = null;
            $shape->beforeQueryCallbacks = [];
            $shape->bindings['where'] = [];
            $shape->bindings['order'] = [];

            return [
                'sql' => $shape->toSql(),
                'bindings' => $shape->getRawBindings(),
                'connection' => spl_object_id($shape->connection),
                'grammar' => spl_object_id($shape->grammar),
                'processor' => spl_object_id($shape->processor),
                'operators' => $shape->operators,
                'bitwise_operators' => $shape->bitwiseOperators,
                'use_write_pdo' => $shape->useWritePdo,
                'fetch_using' => $shape->fetchUsing,
                'timeout' => $shape->timeout,
            ];
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @param  array<int, true>  $seenQueries
     */
    private function queryContainsUnion(
        BaseQueryBuilder $query,
        array &$seenQueries,
        int &$remainingNodes,
        int $depth,
    ): bool {
        if ($depth > self::MAXIMUM_QUERY_DEPTH || $remainingNodes-- < 1) {
            return true;
        }

        $queryId = spl_object_id($query);

        if (isset($seenQueries[$queryId])) {
            return false;
        }

        $seenQueries[$queryId] = true;

        if (is_array($query->unions) && $query->unions !== []) {
            return true;
        }

        foreach ([$query->columns, $query->from, $query->joins, $query->wheres, $query->groups, $query->havings] as $component) {
            if ($this->valueContainsUnion($component, $seenQueries, $remainingNodes, $depth + 1)) {
                return true;
            }
        }

        return false;
    }

    private function queryIsSafeForDefaultAdapter(Resource $resource, BaseQueryBuilder $query): bool
    {
        $remainingNodes = self::MAXIMUM_QUERY_NODES;
        $seenQueries = [];

        return $query->connection === $resource->getConnection()
            && is_string($query->from)
            && $query->from === $resource->getTable()
            && $query->beforeQueryCallbacks === []
            && ! $this->queryContainsUnion($query, $seenQueries, $remainingNodes, 0);
    }

    /** @return array<string, int>|null */
    private function rawFragments(BaseQueryBuilder $query, bool $trackLocation = true): ?array
    {
        $fragments = [];
        $remainingNodes = self::MAXIMUM_QUERY_NODES;
        $seenQueries = [];

        try {
            if (! $this->collectRawFragments(
                $query,
                $query->grammar,
                $fragments,
                $seenQueries,
                $remainingNodes,
                0,
                'query',
                $trackLocation,
            )) {
                return null;
            }
        } catch (Throwable) {
            return null;
        }

        return array_count_values($fragments);
    }

    /** @param array<string, int> $allowedRawFragments */
    private function rawFragmentsAreSubset(BaseQueryBuilder $query, array $allowedRawFragments): bool
    {
        $actualRawFragments = $this->rawFragments($query);

        if ($actualRawFragments === null) {
            return false;
        }

        foreach ($actualRawFragments as $fragment => $count) {
            if ($count > ($allowedRawFragments[$fragment] ?? 0)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int, true>  $seenQueries
     */
    private function valueContainsUnion(
        mixed $value,
        array &$seenQueries,
        int &$remainingNodes,
        int $depth,
    ): bool {
        if ($depth > self::MAXIMUM_QUERY_DEPTH || $remainingNodes-- < 1) {
            return true;
        }

        if ($value instanceof Builder) {
            return $this->queryContainsUnion($value->getQuery(), $seenQueries, $remainingNodes, $depth + 1);
        }

        if ($value instanceof BaseQueryBuilder) {
            return $this->queryContainsUnion($value, $seenQueries, $remainingNodes, $depth + 1);
        }

        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $nestedValue) {
            if ($this->valueContainsUnion($nestedValue, $seenQueries, $remainingNodes, $depth + 1)) {
                return true;
            }
        }

        return false;
    }

    /** @param array<string, true> $allowedOperators */
    private function whereClausesAreSafe(BaseQueryBuilder $query, array $allowedOperators): bool
    {
        $remainingNodes = self::MAXIMUM_QUERY_NODES;
        $seenQueries = [];

        return $this->whereValueIsSafe($query, $allowedOperators, $seenQueries, $remainingNodes, 0);
    }

    /**
     * @param  array<string, true>  $allowedOperators
     * @param  array<int, true>  $seenQueries
     */
    private function whereValueIsSafe(
        mixed $value,
        array $allowedOperators,
        array &$seenQueries,
        int &$remainingNodes,
        int $depth,
    ): bool {
        if ($depth > self::MAXIMUM_QUERY_DEPTH || $remainingNodes-- < 1) {
            return false;
        }

        if ($value instanceof Builder) {
            return $this->whereValueIsSafe(
                $value->getQuery(),
                $allowedOperators,
                $seenQueries,
                $remainingNodes,
                $depth + 1,
            );
        }

        if ($value instanceof BaseQueryBuilder) {
            $queryId = spl_object_id($value);

            if (isset($seenQueries[$queryId])) {
                return true;
            }

            $seenQueries[$queryId] = true;

            return $this->whereValueIsSafe(
                [$value->joins, $value->wheres, $value->havings],
                $allowedOperators,
                $seenQueries,
                $remainingNodes,
                $depth + 1,
            );
        }

        if (! is_array($value)) {
            return true;
        }

        if (array_key_exists('boolean', $value)
            && ! in_array($value['boolean'], ['and', 'or'], true)) {
            return false;
        }

        if (array_key_exists('operator', $value)
            && (! is_string($value['operator'])
                || ! isset($allowedOperators[strtolower($value['operator'])]))) {
            return false;
        }

        foreach ($value as $nestedValue) {
            if (! $this->whereValueIsSafe(
                $nestedValue,
                $allowedOperators,
                $seenQueries,
                $remainingNodes,
                $depth + 1,
            )) {
                return false;
            }
        }

        return true;
    }
}
