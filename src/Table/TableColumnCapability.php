<?php

namespace Aura\Base\Table;

use Aura\Base\Resource;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

final readonly class TableColumnCapability
{
    /**
     * @param  list<string>  $operators
     * @param  Closure(array<string, mixed>): bool|null  $validateFilter
     * @param  Closure(Builder, resource, array<string, mixed>): void|null  $applyFilter
     * @param  Closure(Builder, resource, 'asc'|'desc'): void|null  $applySort
     */
    private function __construct(
        public string $key,
        public array $operators,
        private ?Closure $validateFilter,
        private ?Closure $applyFilter,
        private ?Closure $applySort,
    ) {
        if (trim($key) === '') {
            throw new InvalidArgumentException('A table column capability key is required.');
        }

        foreach ($operators as $operator) {
            if (! is_string($operator) || trim($operator) === '') {
                throw new InvalidArgumentException('Table column operators must be non-empty strings.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    public function acceptsFilter(array $filter): bool
    {
        $operator = $filter['operator'] ?? null;

        return $this->applyFilter !== null
            && is_string($operator)
            && in_array($operator, $this->operators, true)
            && ($this->validateFilter === null || ($this->validateFilter)($filter));
    }

    public function acceptsSort(): bool
    {
        return $this->applySort !== null;
    }

    /**
     * @param  array<string, mixed>  $filter
     */
    public function applyFilter(Builder $query, Resource $resource, array $filter): bool
    {
        if ($this->applyFilter === null || ! $this->acceptsFilter($filter)) {
            return false;
        }

        ($this->applyFilter)($query, $resource, $filter);

        return true;
    }

    public function applySort(Builder $query, Resource $resource, string $direction): bool
    {
        if ($this->applySort === null || ! in_array($direction, ['asc', 'desc'], true)) {
            return false;
        }

        ($this->applySort)($query, $resource, $direction);

        return true;
    }

    /**
     * @param  list<string>  $operators
     * @param  Closure(array<string, mixed>): bool|null  $validateFilter
     * @param  Closure(Builder, resource, array<string, mixed>): void|null  $applyFilter
     * @param  Closure(Builder, resource, 'asc'|'desc'): void|null  $applySort
     */
    public static function computed(
        string $key,
        array $operators = [],
        ?Closure $validateFilter = null,
        ?Closure $applyFilter = null,
        ?Closure $applySort = null,
    ): self {
        return new self($key, array_values(array_unique($operators)), $validateFilter, $applyFilter, $applySort);
    }
}
