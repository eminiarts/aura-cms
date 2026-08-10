<?php

namespace Aura\Base\Table;

use Aura\Base\Contracts\TableColumnCapabilityResolver;
use Aura\Base\Fields\Filters\FilterCapability;
use Aura\Base\Resource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final class TableQueryStateApplier
{
    public function __construct(
        private readonly ?TableColumnCapabilityResolver $capabilities = null,
        private readonly TableParentScopeResolver $parentScopes = new TableParentScopeResolver,
    ) {}

    public function accepts(Resource $resource, TableQueryState $state): bool
    {
        if ($state->parent !== null && ! $this->parentScopes->accepts($resource, $state->parent)) {
            return false;
        }

        foreach ($state->filters as $group) {
            foreach ($group['filters'] as $filter) {
                $capability = $this->resolveCapability($resource, $filter['name']);

                if ($capability === null || ! $capability->acceptsFilter($filter)) {
                    return false;
                }
            }
        }

        foreach ($state->sorts as $sort) {
            $capability = $this->resolveCapability($resource, $sort['key']);

            if ($capability === null || ! $capability->acceptsSort()) {
                return false;
            }
        }

        return true;
    }

    public function apply(
        Builder $query,
        Resource $resource,
        TableQueryState $state,
        ?Authenticatable $actor = null,
    ): Builder {
        if ($state->parent !== null) {
            $query = $this->parentScopes->apply($query, $resource, $state->parent, $actor);
        }

        if (! $this->acceptsForRead($resource, $state)) {
            return $query->whereRaw('1 = 0');
        }

        $this->applyFilters($query, $resource, $state->filters);
        $this->applySearch($query, $resource, $state->search);
        $this->applySorting($query, $resource, $state->sorts);

        return $query;
    }

    private function acceptsForRead(Resource $resource, TableQueryState $state): bool
    {
        foreach ($state->filters as $group) {
            foreach ($group['filters'] as $filter) {
                $capability = $this->resolveCapability($resource, $filter['name']);

                if ($capability === null || ! $capability->recognizesOperator($filter['operator'])) {
                    return false;
                }

                if (! in_array($filter['operator'], ['is_empty', 'is_not_empty', 'date_is_empty', 'date_is_not_empty'], true)
                    && ! FilterCapability::hasValue($filter['value'] ?? null)) {
                    continue;
                }

                if (! $capability->acceptsFilter($filter)) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * @param  list<array{operator: 'and'|'or', filters: list<array<string, mixed>>}>  $groups
     */
    private function applyFilterGroups(Builder $query, Resource $resource, array $groups, int $groupIndex): void
    {
        if ($groupIndex === 0) {
            $this->applySingleGroup($query, $resource, $groups[0]);

            return;
        }

        $query->where(function (Builder $query) use ($resource, $groups, $groupIndex): void {
            $this->applyFilterGroups($query, $resource, $groups, $groupIndex - 1);
        });

        $group = $groups[$groupIndex];
        $method = $group['operator'] === 'or' ? 'orWhere' : 'where';
        $query->{$method}(function (Builder $query) use ($resource, $group): void {
            $this->applySingleGroup($query, $resource, $group);
        });
    }

    /**
     * @param  list<array{operator: 'and'|'or', filters: list<array<string, mixed>>}>  $groups
     */
    private function applyFilters(Builder $query, Resource $resource, array $groups): void
    {
        if ($groups === []) {
            return;
        }

        $query->where(function (Builder $query) use ($resource, $groups): void {
            $this->applyFilterGroups($query, $resource, $groups, count($groups) - 1);
        });
    }

    private function applySearch(Builder $query, Resource $resource, ?string $search): void
    {
        if ($search === null || $search === '') {
            return;
        }

        if (method_exists($resource, 'modifySearch')) {
            $resource->modifySearch($query, $search);

            return;
        }

        $searchableFields = $resource->getSearchableFields()->pluck('slug');
        $metaFields = $searchableFields->filter(fn (string $field): bool => $resource->isMetaField($field));

        $query->where(function (Builder $query) use ($resource, $search, $searchableFields, $metaFields): void {
            foreach ($searchableFields as $field) {
                if (! $metaFields->contains($field)) {
                    $query->orWhere($resource->qualifyColumn($field), 'like', '%'.$search.'%');
                }
            }

            if ($metaFields->isEmpty()) {
                return;
            }

            $metaTable = $resource->getMetaTable();
            $query->orWhereExists(function ($query) use ($resource, $search, $metaTable, $metaFields): void {
                $query->select(DB::raw(1))
                    ->from($metaTable)
                    ->whereColumn($resource->getQualifiedKeyName(), $metaTable.'.'.$resource->getMetaForeignKey())
                    ->whereIn($metaTable.'.key', $metaFields)
                    ->where($metaTable.'.value', 'like', '%'.$search.'%');
            });
        });
    }

    /**
     * @param  array{operator: 'and'|'or', filters: list<array<string, mixed>>}  $group
     */
    private function applySingleGroup(Builder $query, Resource $resource, array $group): void
    {
        foreach ($group['filters'] as $index => $filter) {
            $capability = $this->resolveCapability($resource, $filter['name']);

            if ($capability === null || ! $capability->recognizesOperator($filter['operator'])) {
                $query->whereRaw('1 = 0');

                continue;
            }

            if (! in_array($filter['operator'], ['is_empty', 'is_not_empty', 'date_is_empty', 'date_is_not_empty'], true)
                && ! FilterCapability::hasValue($filter['value'] ?? null)) {
                continue;
            }

            $method = $index > 0 && $filter['main_operator'] === 'or' ? 'orWhere' : 'where';
            $query->{$method}(function (Builder $query) use ($resource, $filter, $capability): void {
                if (! $capability->applyFilter($query, $resource, $filter)) {
                    $query->whereRaw('1 = 0');
                }
            });
        }
    }

    /**
     * @param  list<array{key: string, direction: 'asc'|'desc'}>  $sorts
     */
    private function applySorting(Builder $query, Resource $resource, array $sorts): void
    {
        $query->getQuery()->orders = null;

        if ($sorts === []) {
            $query->orderBy(
                $resource->qualifyColumn($resource->defaultTableSort()),
                $resource->defaultTableSortDirection(),
            )->orderBy($resource->getQualifiedKeyName());

            return;
        }

        foreach ($sorts as $sort) {
            $capability = $this->resolveCapability($resource, $sort['key']);

            if ($capability === null || ! $capability->applySort($query, $resource, $sort['direction'])) {
                $query->whereRaw('1 = 0');

                return;
            }
        }
    }

    private function resolveCapability(Resource $resource, string $key): ?TableColumnCapability
    {
        $capability = $this->capabilities?->resolve($resource, $key);

        if ($capability !== null) {
            return $capability;
        }

        return (new ResourceTableColumnCapabilityResolver)->resolve($resource, $key);
    }
}
