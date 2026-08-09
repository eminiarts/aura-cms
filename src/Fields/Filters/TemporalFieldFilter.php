<?php

namespace Aura\Base\Fields\Filters;

use Aura\Base\Contracts\AppliesFieldFilter;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Grammars\MySqlGrammar;
use Illuminate\Database\Query\Grammars\PostgresGrammar;
use Illuminate\Database\Query\Grammars\SQLiteGrammar;
use Illuminate\Database\Query\Grammars\SqlServerGrammar;

final class TemporalFieldFilter implements AppliesFieldFilter
{
    /**
     * @param  array<string, mixed>  $field
     * @param  array{name: string, operator: string, value?: mixed, options?: array<string, mixed>}  $filter
     */
    public function apply(
        Builder $query,
        Resource $resource,
        array $field,
        array $filter,
        FilterCapability $capability,
    ): void {
        if ($resource->isMetaField($field['slug'])) {
            $this->applyMetaFilter($query, $filter, $capability);

            return;
        }

        if ($resource->isTableField($field['slug']) || $resource->usesCustomTable()) {
            $this->applyOperator(
                $query,
                $query->getModel()->qualifyColumn($field['slug']),
                $filter,
                $capability,
            );

            return;
        }

        $this->applyMetaFilter($query, $filter, $capability);
    }

    /**
     * @param  array{name: string, operator: string, value?: mixed, options?: array<string, mixed>}  $filter
     */
    private function applyMetaFilter(Builder $query, array $filter, FilterCapability $capability): void
    {
        if (in_array($filter['operator'], ['is_empty', 'date_is_empty'], true)) {
            $query->where(function (Builder $query) use ($filter): void {
                $query->whereDoesntHave('meta', function (Builder $query) use ($filter): void {
                    $query->where('key', $filter['name']);
                })->orWhereHas('meta', function (Builder $query) use ($filter): void {
                    $query->where('key', $filter['name'])
                        ->where(function (Builder $query): void {
                            $query->whereNull('value')->orWhere('value', '');
                        });
                });
            });

            return;
        }

        if (in_array($filter['operator'], ['is_not_empty', 'date_is_not_empty'], true)) {
            $query->whereHas('meta', function (Builder $query) use ($filter): void {
                $query->where('key', $filter['name'])
                    ->whereNotNull('value')
                    ->where('value', '!=', '');
            });

            return;
        }

        $query->whereHas('meta', function (Builder $query) use ($filter, $capability): void {
            $query->where('key', $filter['name']);
            $this->applyOperator($query, 'value', $filter, $capability);
        });
    }

    /**
     * @param  array{name: string, operator: string, value?: mixed, options?: array<string, mixed>}  $filter
     */
    private function applyOperator(
        Builder $query,
        string $column,
        array $filter,
        FilterCapability $capability,
    ): void {
        if (in_array($filter['operator'], ['is_empty', 'date_is_empty'], true)) {
            $query->where(function (Builder $query) use ($column): void {
                $query->whereNull($column)->orWhere($column, '');
            });

            return;
        }

        if (in_array($filter['operator'], ['is_not_empty', 'date_is_not_empty'], true)) {
            $query->whereNotNull($column)->where($column, '!=', '');

            return;
        }

        $context = $capability->context();
        $storageFormat = $context['storage_format'] ?? null;
        $precision = $context['precision'] ?? null;

        if (! is_string($storageFormat) || ! in_array($precision, ['date', 'datetime'], true)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $expression = $this->canonicalExpression($query, $column, $storageFormat, $precision === 'datetime');

        if ($expression === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        [$sql, $storageLength] = $expression;
        $driver = $this->driver($query);

        if ($driver === null) {
            $query->whereRaw('1 = 0');

            return;
        }

        $lengthFunction = $driver === 'sqlsrv' ? 'len' : 'length';
        $wrappedColumn = $query->getQuery()->getGrammar()->wrap($column);
        $value = $filter['value'] ?? null;
        $operator = match ($filter['operator']) {
            'is', 'date_is' => '=',
            'is_not', 'date_is_not' => '!=',
            'before', 'date_before' => '<',
            'after', 'date_after' => '>',
            'on_or_before', 'date_on_or_before' => '<=',
            'on_or_after', 'date_on_or_after' => '>=',
            default => null,
        };

        if ($filter['operator'] === 'date_between' && is_array($value)) {
            $query->whereRaw(
                sprintf('%s(%s) = ? and %s >= ? and %s <= ?', $lengthFunction, $wrappedColumn, $sql, $sql),
                [$storageLength, $value['from'] ?? null, $value['to'] ?? null],
            );

            return;
        }

        if ($operator === null || ! is_string($value)) {
            $query->whereRaw('1 = 0');

            return;
        }

        $query->whereRaw(
            sprintf('%s(%s) = ? and %s %s ?', $lengthFunction, $wrappedColumn, $sql, $operator),
            [$storageLength, $value],
        );
    }

    /**
     * @return array{string, int}|null
     */
    private function canonicalExpression(
        Builder $query,
        string $column,
        string $format,
        bool $includeTime,
    ): ?array {
        $formatDefinition = $this->formatDefinition($format);

        if ($formatDefinition === null) {
            return null;
        }

        [$offsets, $storageLength] = $formatDefinition;
        $required = $includeTime ? ['Y', 'm', 'd', 'H', 'i'] : ['Y', 'm', 'd'];

        foreach ($required as $token) {
            if (! isset($offsets[$token])) {
                return null;
            }
        }

        $wrappedColumn = $query->getQuery()->getGrammar()->wrap($column);
        $driver = $this->driver($query);

        if ($driver === null) {
            return null;
        }
        $parts = [
            $this->substring($driver, $wrappedColumn, $offsets['Y'][0], $offsets['Y'][1]),
            "'-'",
            $this->substring($driver, $wrappedColumn, $offsets['m'][0], $offsets['m'][1]),
            "'-'",
            $this->substring($driver, $wrappedColumn, $offsets['d'][0], $offsets['d'][1]),
        ];

        if ($includeTime) {
            array_push(
                $parts,
                "' '",
                $this->substring($driver, $wrappedColumn, $offsets['H'][0], $offsets['H'][1]),
                "':'",
                $this->substring($driver, $wrappedColumn, $offsets['i'][0], $offsets['i'][1]),
                "':'",
                isset($offsets['s'])
                    ? $this->substring($driver, $wrappedColumn, $offsets['s'][0], $offsets['s'][1])
                    : "'00'",
            );
        }

        $sql = in_array($driver, ['mysql', 'mariadb', 'sqlsrv'], true)
            ? 'concat('.implode(', ', $parts).')'
            : implode(' || ', $parts);

        return [$sql, $storageLength];
    }

    private function driver(Builder $query): ?string
    {
        $grammar = $query->getQuery()->getGrammar();

        return match (true) {
            $grammar instanceof MySqlGrammar => 'mysql',
            $grammar instanceof PostgresGrammar => 'pgsql',
            $grammar instanceof SqlServerGrammar => 'sqlsrv',
            $grammar instanceof SQLiteGrammar => 'sqlite',
            default => null,
        };
    }

    /**
     * @return array{array<string, array{int, int}>, int}|null
     */
    private function formatDefinition(string $format): ?array
    {
        $widths = ['Y' => 4, 'm' => 2, 'd' => 2, 'H' => 2, 'i' => 2, 's' => 2];
        $offsets = [];
        $position = 1;
        $length = strlen($format);

        for ($index = 0; $index < $length; $index++) {
            $character = $format[$index];

            if ($character === '\\') {
                $index++;

                if ($index >= $length) {
                    return null;
                }

                $position++;

                continue;
            }

            if (isset($widths[$character])) {
                if (isset($offsets[$character])) {
                    return null;
                }

                $offsets[$character] = [$position, $widths[$character]];
                $position += $widths[$character];

                continue;
            }

            if (ctype_alpha($character)) {
                return null;
            }

            $position++;
        }

        return [$offsets, $position - 1];
    }

    private function substring(string $driver, string $column, int $start, int $length): string
    {
        if ($driver === 'sqlsrv') {
            return sprintf('substring(%s, %d, %d)', $column, $start, $length);
        }

        return sprintf('substr(%s, %d, %d)', $column, $start, $length);
    }
}
