<?php

namespace Aura\Base\Table;

use Aura\Base\Resource;
use Aura\Base\Support\FieldDisplayValue;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;

final readonly class ComputedTableColumn
{
    /**
     * @param  Closure(resource): mixed  $render
     * @param  Closure(resource): mixed  $export
     */
    private function __construct(
        public string $key,
        public string $label,
        private Closure $render,
        private Closure $export,
        private TableColumnCapability $capability,
    ) {
        if (preg_match('/\A[A-Za-z][A-Za-z0-9_-]*\z/', $key) !== 1) {
            throw new InvalidArgumentException('Computed table column keys must be stable identifiers.');
        }

        if (trim($label) === '') {
            throw new InvalidArgumentException('A computed table column label is required.');
        }

        if ($capability->key !== $key) {
            throw new InvalidArgumentException('The computed table column capability key must match its column key.');
        }
    }

    public function capability(): TableColumnCapability
    {
        return $this->capability;
    }

    public function export(Resource $record): mixed
    {
        return ($this->export)($record);
    }

    /**
     * @param  list<string>  $operators
     * @param  Closure(array<string, mixed>): bool|null  $validateFilter
     * @param  Closure(Builder, resource, array<string, mixed>): void|null  $applyFilter
     * @param  Closure(Builder, resource, 'asc'|'desc'): void|null  $applySort
     * @param  Closure(resource): mixed  $render
     * @param  Closure(resource): mixed  $export
     */
    public static function make(
        string $key,
        string $label,
        Closure $render,
        Closure $export,
        array $operators = [],
        ?Closure $validateFilter = null,
        ?Closure $applyFilter = null,
        ?Closure $applySort = null,
    ): self {
        if (($operators !== [] || $validateFilter !== null) && $applyFilter === null) {
            throw new InvalidArgumentException('Computed table filter declarations require an apply callback.');
        }

        if ($applyFilter !== null && $operators === []) {
            throw new InvalidArgumentException('Computed table filter callbacks require declared operators.');
        }

        $stableSort = $applySort === null
            ? null
            : static function (Builder $query, Resource $resource, string $direction) use ($applySort): void {
                $applySort($query, $resource, $direction);
                $query->orderBy($resource->getQualifiedKeyName());
            };

        return new self(
            $key,
            trim($label),
            $render,
            $export,
            TableColumnCapability::computed(
                key: $key,
                operators: $operators,
                validateFilter: $validateFilter,
                applyFilter: $applyFilter,
                applySort: $stableSort,
            ),
        );
    }

    public function render(Resource $record): mixed
    {
        return FieldDisplayValue::secure(($this->render)($record));
    }
}
