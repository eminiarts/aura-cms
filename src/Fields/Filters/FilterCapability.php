<?php

namespace Aura\Base\Fields\Filters;

use Aura\Base\Contracts\AppliesFieldFilter;
use Aura\Base\Resource;
use DateTimeImmutable;
use Illuminate\Database\Eloquent\Builder;
use InvalidArgumentException;
use LogicException;

final class FilterCapability
{
    public const BOOLEAN = 'boolean';

    public const CUSTOM = 'custom';

    public const DATE = 'date';

    public const DATE_RANGE = 'date-range';

    public const DATETIME = 'datetime';

    public const OPTION = 'option';

    public const RELATIONSHIP = 'relationship';

    public const TEXT = 'text';

    private const WIRE_PREFIX = '__aura_filter:';

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
    ) {
        if (trim($this->component) === '') {
            throw new InvalidArgumentException('A filter capability component is required.');
        }

        foreach ($this->operators as $operator => $label) {
            if (! is_string($operator) || trim($operator) === '' || ! is_string($label) || trim($label) === '') {
                throw new InvalidArgumentException('Filter capability operators must use non-empty string keys and labels.');
            }
        }

        if (! is_a($this->queryHandler, AppliesFieldFilter::class, true)) {
            throw new InvalidArgumentException(sprintf('%s must implement %s.', $this->queryHandler, AppliesFieldFilter::class));
        }
    }

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
     * @param  array<string, mixed>  $context
     */
    public static function custom(
        string $component,
        array $operators,
        string $queryHandler,
        mixed $values = [],
        array $context = [],
        bool $multiple = false,
    ): self {
        return new self(
            type: self::CUSTOM,
            component: $component,
            operators: $operators,
            values: (new FilterOptionNormalizer)->normalize($values),
            context: $context + ['multiple' => $multiple],
            queryHandler: $queryHandler,
        );
    }

    /**
     * @param  array<string, string>  $operators
     */
    public static function date(array $operators, string $storageFormat = 'Y-m-d'): self
    {
        return new self(
            type: self::DATE,
            component: 'aura::fields.filters.date',
            operators: $operators,
            context: ['storage_format' => $storageFormat, 'precision' => 'date'],
            queryHandler: TemporalFieldFilter::class,
        );
    }

    /**
     * @param  array<string, string>  $operators
     */
    public static function dateRange(array $operators, string $storageFormat = 'Y-m-d'): self
    {
        return new self(
            type: self::DATE_RANGE,
            component: 'aura::fields.filters.date-range',
            operators: $operators,
            context: ['storage_format' => $storageFormat, 'precision' => 'date'],
            queryHandler: TemporalFieldFilter::class,
        );
    }

    /**
     * @param  array<string, string>  $operators
     */
    public static function datetime(array $operators, string $storageFormat = 'Y-m-d H:i:s'): self
    {
        return new self(
            type: self::DATETIME,
            component: 'aura::fields.filters.datetime',
            operators: $operators,
            context: ['storage_format' => $storageFormat, 'precision' => 'datetime'],
            queryHandler: TemporalFieldFilter::class,
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
     * @param  class-string<AppliesFieldFilter>  $queryHandler
     * @param  array<string, mixed>  $context
     */
    public static function option(
        array $operators,
        mixed $values,
        string $queryHandler = ResourceFieldFilter::class,
        array $context = [],
        bool $multiple = false,
    ): self {
        return new self(
            type: self::OPTION,
            component: 'aura::fields.filters.option',
            operators: $operators,
            values: (new FilterOptionNormalizer)->normalize($values),
            context: $context + ['multiple' => $multiple],
            queryHandler: $queryHandler,
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

        if ($this->type === self::DATE || $this->type === self::DATETIME) {
            $normalized = $this->normalizeTemporalValue(
                $filter['value'] ?? null,
                $this->type === self::DATETIME,
            );

            if ($normalized === null) {
                return null;
            }

            $filter['value'] = $normalized;

            return $filter;
        }

        if ($this->type === self::DATE_RANGE) {
            $value = $filter['value'] ?? null;

            if (! is_array($value)) {
                return null;
            }

            $from = $this->normalizeTemporalValue($value['from'] ?? null, false);
            $to = $this->normalizeTemporalValue($value['to'] ?? null, false);

            if ($from === null || $to === null || $from > $to) {
                return null;
            }

            $filter['value'] = ['from' => $from, 'to' => $to];

            return $filter;
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

        if ($this->context['multiple'] ?? false) {
            return $this->normalizeMultipleValue($filter);
        }

        if (! in_array($this->type, [self::OPTION, self::BOOLEAN], true) && ! ($this->type === self::CUSTOM && $this->values !== [])) {
            return self::hasValue($filter['value'] ?? null) ? $filter : null;
        }

        $option = $this->resolveOption($filter['value'] ?? null);

        if ($option === null) {
            return null;
        }

        $filter['value'] = $option;

        return $filter;
    }

    /**
     * @param  array{name: string, operator: string, value?: mixed, options?: array<string, mixed>}  $filter
     * @return array{name: string, operator: string, value?: mixed, options?: array<string, mixed>}|null
     */
    private function normalizeMultipleValue(array $filter): ?array
    {
        $values = is_array($filter['value'] ?? null) ? $filter['value'] : [$filter['value'] ?? null];
        $normalized = [];

        foreach ($values as $value) {
            if (is_array($value) || is_object($value) || $value === null || (is_string($value) && trim($value) === '')) {
                return null;
            }

            $resolved = $this->values === [] ? $value : $this->resolveOption($value);

            if ($resolved === null) {
                return null;
            }

            if (! in_array($resolved, $normalized, true)) {
                $normalized[] = $resolved;
            }
        }

        if ($normalized === []) {
            return null;
        }

        $filter['value'] = $normalized;

        return $filter;
    }

    private function normalizeTemporalValue(mixed $value, bool $includeTime): ?string
    {
        if (! is_string($value) || trim($value) === '') {
            return null;
        }

        $formats = $includeTime
            ? ['Y-m-d\\TH:i:s', 'Y-m-d\\TH:i', 'Y-m-d H:i:s', 'Y-m-d H:i', $this->context['storage_format'] ?? null]
            : ['Y-m-d', $this->context['storage_format'] ?? null];

        foreach (array_unique(array_filter($formats, 'is_string')) as $format) {
            $date = DateTimeImmutable::createFromFormat('!'.$format, $value);
            $errors = DateTimeImmutable::getLastErrors();

            if ($date === false || ($errors !== false && ($errors['warning_count'] > 0 || $errors['error_count'] > 0)) || $date->format($format) !== $value) {
                continue;
            }

            return $date->format($includeTime ? 'Y-m-d H:i:s' : 'Y-m-d');
        }

        return null;
    }

    private function resolveOption(mixed $value): string|int|float|bool|null
    {
        if (is_array($value) || is_object($value) || $value === null) {
            return null;
        }

        foreach ($this->values as $option) {
            if (is_string($value) && hash_equals($option['wire_value'], $value)) {
                return $option['value'];
            }
        }

        foreach ($this->values as $option) {
            if ($value === $option['value'] && (! is_string($value) || ! str_starts_with($value, self::WIRE_PREFIX))) {
                return $option['value'];
            }
        }

        return null;
    }
}
