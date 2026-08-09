<?php

namespace Aura\Base\Fields\Filters;

use Aura\Base\Contracts\AppliesFieldFilter;
use Aura\Base\Resource;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use LogicException;

final class FilterCapability
{
    public const BOOLEAN = 'boolean';

    public const CUSTOM = 'custom';

    public const DATE = 'date';

    public const DATE_RANGE = 'date-range';

    public const OPTION = 'option';

    public const RELATIONSHIP = 'relationship';

    public const TEXT = 'text';

    /**
     * @param  array<string, string>  $operators
     * @param  list<array{value: string|int|float|bool, wire_value: string, label: string}>  $values
     * @param  array<string, mixed>  $context
     * @param  class-string<AppliesFieldFilter>  $queryHandler
     */
    private function __construct(
        private readonly string $type,
        private readonly string $component,
        private readonly array $operators,
        private readonly array $values = [],
        private readonly array $context = [],
        private readonly string $queryHandler = ResourceFieldFilter::class,
    ) {}

    /**
     * @param  array<string, mixed>  $field
     * @param  array{name: string, operator: string, value?: mixed, options?: array<string, mixed>}  $filter
     */
    public function apply(Builder $query, Resource $resource, array $field, array $filter): void
    {
        $operator = $filter['operator'] ?? null;

        if (! is_string($operator) || ! array_key_exists($operator, $this->operators)) {
            $this->matchNothing($query);

            return;
        }

        $filter['name'] = $field['slug'];

        $normalized = $this->normalizeFilter($filter);

        if ($normalized === null) {
            $this->matchNothing($query);

            return;
        }

        $handler = app($this->queryHandler);

        if (! $handler instanceof AppliesFieldFilter) {
            throw new LogicException(sprintf('%s must implement %s.', $this->queryHandler, AppliesFieldFilter::class));
        }

        $handler->apply($query, $resource, $field, $normalized, $this);
    }

    /**
     * @param  array<string, string>  $operators
     */
    public static function boolean(array $operators): self
    {
        return new self(
            type: self::BOOLEAN,
            component: 'aura::fields.filters.boolean',
            operators: $operators,
            values: (new FilterOptionNormalizer)->normalize([
                ['value' => 1, 'label' => __('Yes')],
                ['value' => 0, 'label' => __('No')],
            ]),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->context;
    }

    /**
     * @param  array<string, string>  $operators
     * @param  class-string<AppliesFieldFilter>  $queryHandler
     * @param  iterable<array-key, mixed>  $values
     * @param  array<string, mixed>  $context
     */
    public static function custom(
        string $component,
        array $operators,
        string $queryHandler,
        iterable $values = [],
        array $context = [],
    ): self {
        return new self(
            type: self::CUSTOM,
            component: $component,
            operators: $operators,
            values: (new FilterOptionNormalizer)->normalize($values),
            context: $context,
            queryHandler: $queryHandler,
        );
    }

    /**
     * @param  array<string, string>  $operators
     */
    public static function date(array $operators): self
    {
        return new self(
            type: self::DATE,
            component: 'aura::fields.filters.date',
            operators: $operators,
        );
    }

    /**
     * @param  array<string, string>  $operators
     */
    public static function dateRange(array $operators): self
    {
        return new self(
            type: self::DATE_RANGE,
            component: 'aura::fields.filters.date-range',
            operators: $operators,
        );
    }

    public static function hasValue(mixed $value): bool
    {
        if (is_array($value)) {
            return collect($value)->contains(fn ($item) => self::hasValue($item));
        }

        return $value !== null && (! is_string($value) || trim($value) !== '');
    }

    /**
     * @param  array<string, string>  $operators
     * @param  iterable<array-key, mixed>  $values
     */
    public static function option(array $operators, iterable $values): self
    {
        return new self(
            type: self::OPTION,
            component: 'aura::fields.filters.option',
            operators: $operators,
            values: (new FilterOptionNormalizer)->normalize($values),
        );
    }

    /**
     * @param  array<string, string>  $operators
     */
    public static function relationship(
        array $operators,
        string $component,
        string $resourceType,
        string $ownerPivotKey = 'related_id',
        string $valuePivotKey = 'resource_id',
        string $ownerTypeColumn = 'related_type',
        string $valueTypeColumn = 'resource_type',
    ): self {
        return new self(
            type: self::RELATIONSHIP,
            component: $component,
            operators: $operators,
            context: [
                'resource_type' => $resourceType,
                'owner_pivot_key' => $ownerPivotKey,
                'value_pivot_key' => $valuePivotKey,
                'owner_type_column' => $ownerTypeColumn,
                'value_type_column' => $valueTypeColumn,
            ],
            queryHandler: RelationshipFieldFilter::class,
        );
    }

    /**
     * @param  array<string, string>  $operators
     */
    public static function text(array $operators): self
    {
        return new self(
            type: self::TEXT,
            component: 'aura::fields.filters.text',
            operators: $operators,
        );
    }

    /**
     * @return array{
     *     type: string,
     *     component: string,
     *     operators: array<string, string>,
     *     values: list<array{value: string|int|float|bool, wire_value: string, label: string}>,
     *     context: array<string, mixed>,
     *     query: class-string<AppliesFieldFilter>
     * }
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'component' => $this->component,
            'operators' => $this->operators,
            'values' => $this->values,
            'context' => $this->context,
            'query' => $this->queryHandler,
        ];
    }

    private function isDate(mixed $value): bool
    {
        if (! is_string($value)) {
            return false;
        }

        $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

        return $date && $date->format('Y-m-d') === $value;
    }

    private function matchNothing(Builder $query): void
    {
        $query->whereRaw('1 = 0');
    }

    /**
     * @param  array{name: string, operator: string, value?: mixed, options?: array<string, mixed>}  $filter
     * @return array{name: string, operator: string, value?: mixed, options?: array<string, mixed>}|null
     */
    private function normalizeFilter(array $filter): ?array
    {
        if (in_array($filter['operator'], ['is_empty', 'is_not_empty', 'date_is_empty', 'date_is_not_empty'], true)) {
            $filter['value'] = null;

            return $filter;
        }

        if ($this->type === self::DATE) {
            $value = $filter['value'] ?? null;

            if (! is_string($value)) {
                return null;
            }

            $date = DateTimeImmutable::createFromFormat('!Y-m-d', $value);

            return $date && $date->format('Y-m-d') === $value ? $filter : null;
        }

        if ($this->type === self::DATE_RANGE) {
            $value = $filter['value'] ?? null;

            if (! is_array($value) || ! $this->isDate($value['from'] ?? null) || ! $this->isDate($value['to'] ?? null)) {
                return null;
            }

            return $value['from'] <= $value['to'] ? $filter : null;
        }

        if ($this->type === self::RELATIONSHIP) {
            $values = is_array($filter['value'] ?? null) ? $filter['value'] : [$filter['value'] ?? null];
            $values = array_values(array_filter(
                $values,
                fn ($value) => (is_string($value) && trim($value) !== '') || is_int($value),
            ));

            if ($values === []) {
                return null;
            }

            $filter['value'] = $values;

            return $filter;
        }

        if (! in_array($this->type, [self::OPTION, self::BOOLEAN], true) && ! ($this->type === self::CUSTOM && $this->values !== [])) {
            return self::hasValue($filter['value'] ?? null) ? $filter : null;
        }

        $value = $filter['value'] ?? null;

        if (is_array($value)) {
            return null;
        }

        foreach ($this->values as $option) {
            if ($value === $option['value'] || (is_scalar($value) && (string) $value === $option['wire_value'])) {
                $filter['value'] = $option['value'];

                return $filter;
            }
        }

        return null;
    }
}
