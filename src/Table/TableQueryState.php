<?php

namespace Aura\Base\Table;

use InvalidArgumentException;
use JsonException;
use JsonSerializable;

final readonly class TableQueryState implements JsonSerializable
{
    public const VERSION = 1;

    /**
     * @param  list<array{operator: 'and'|'or', filters: list<array<string, mixed>>}>  $filters
     * @param  list<array{key: string, direction: 'asc'|'desc'}>  $sorts
     * @param  array{scope: string, id: int|string}|null  $parent
     */
    private function __construct(
        public array $filters,
        public ?string $search,
        public array $sorts,
        public ?array $parent,
    ) {}

    /**
     * @param  array<string, mixed>  $state
     */
    public static function fromArray(array $state): self
    {
        if (array_diff(array_keys($state), ['v', 'filters', 'search', 'sorts', 'parent']) !== []) {
            throw new InvalidArgumentException('Unknown table query-state key.');
        }

        if (($state['v'] ?? self::VERSION) !== self::VERSION) {
            throw new InvalidArgumentException('Unsupported table query-state version.');
        }

        return new self(
            filters: self::normalizeFilters($state['filters'] ?? []),
            search: self::normalizeSearch($state['search'] ?? null),
            sorts: self::normalizeSorts($state['sorts'] ?? []),
            parent: self::normalizeParent($state['parent'] ?? null),
        );
    }

    /**
     * @param  array<string, mixed>  $filters
     * @param  array<string, mixed>  $sorts
     * @param  array{scope: string, id: int|string}|null  $parent
     */
    public static function fromLegacy(
        array $filters,
        mixed $search,
        array $sorts,
        ?array $parent = null,
    ): self {
        $custom = $filters['custom'] ?? [];
        $serializedSorts = [];

        foreach ($sorts as $key => $direction) {
            $serializedSorts[] = ['key' => $key, 'direction' => $direction];
        }

        return self::fromArray([
            'v' => self::VERSION,
            'filters' => $custom,
            'search' => $search,
            'sorts' => $serializedSorts,
            'parent' => $parent,
        ]);
    }

    public static function fromQueryString(string $encoded): self
    {
        if ($encoded === '') {
            return self::fromArray(['v' => self::VERSION]);
        }

        $padding = (4 - strlen($encoded) % 4) % 4;
        $json = base64_decode(strtr($encoded.str_repeat('=', $padding), '-_', '+/'), true);

        if (! is_string($json)) {
            throw new InvalidArgumentException('Invalid table query-state encoding.');
        }

        try {
            $state = json_decode($json, true, 32, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Invalid table query-state JSON.', previous: $exception);
        }

        if (! is_array($state)) {
            throw new InvalidArgumentException('Invalid table query-state payload.');
        }

        return self::fromArray($state);
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        $state = [
            'v' => self::VERSION,
            'filters' => $this->filters,
            'search' => $this->search,
            'sorts' => $this->sorts,
        ];

        if ($this->parent !== null) {
            $state['parent'] = $this->parent;
        }

        return $state;
    }

    public function toQueryString(): string
    {
        try {
            $json = json_encode($this->toArray(), JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Unable to encode table query state.', previous: $exception);
        }

        return rtrim(strtr(base64_encode($json), '+/', '-_'), '=');
    }

    public function withoutParentScope(): self
    {
        return new self($this->filters, $this->search, $this->sorts, null);
    }

    /**
     * @return list<array{operator: 'and'|'or', filters: list<array<string, mixed>>}>
     */
    private static function normalizeFilters(mixed $filters): array
    {
        if (! is_array($filters) || ! array_is_list($filters)) {
            throw new InvalidArgumentException('Table query-state filters must be a list.');
        }

        $containsGroups = collect($filters)->contains(fn (mixed $item): bool => is_array($item) && array_key_exists('filters', $item));
        $containsFlat = collect($filters)->contains(fn (mixed $item): bool => is_array($item) && ! array_key_exists('filters', $item));

        if ($containsGroups && $containsFlat) {
            throw new InvalidArgumentException('Table query-state filters cannot mix grouped and flat filters.');
        }

        $groups = $containsFlat ? [['filters' => $filters]] : $filters;
        $normalized = [];

        foreach ($groups as $group) {
            if (! is_array($group)
                || array_diff(array_keys($group), ['operator', 'filters']) !== []
                || ! is_array($group['filters'] ?? null)
                || ! array_is_list($group['filters'])
                || ! in_array($group['operator'] ?? 'and', ['and', 'or'], true)) {
                throw new InvalidArgumentException('Invalid table query-state filter group.');
            }

            $normalizedFilters = [];

            foreach ($group['filters'] as $filter) {
                if (! is_array($filter)
                    || array_diff(array_keys($filter), ['name', 'operator', 'value', 'main_operator', 'options']) !== []
                    || ! is_string($filter['name'] ?? null)
                    || trim($filter['name']) === ''
                    || ! is_string($filter['operator'] ?? null)
                    || trim($filter['operator']) === ''
                    || ! in_array($filter['main_operator'] ?? 'and', ['and', 'or'], true)
                    || (array_key_exists('options', $filter) && ! is_array($filter['options']))) {
                    throw new InvalidArgumentException('Invalid table query-state filter.');
                }

                $normalizedFilter = [
                    'name' => trim($filter['name']),
                    'operator' => trim($filter['operator']),
                    'value' => $filter['value'] ?? null,
                    'main_operator' => $filter['main_operator'] ?? 'and',
                ];

                if (array_key_exists('options', $filter)) {
                    $normalizedFilter['options'] = $filter['options'];
                }

                $normalizedFilters[] = $normalizedFilter;
            }

            $normalized[] = [
                'operator' => $group['operator'] ?? 'and',
                'filters' => $normalizedFilters,
            ];
        }

        return $normalized;
    }

    /**
     * @return array{scope: string, id: int|string}|null
     */
    private static function normalizeParent(mixed $parent): ?array
    {
        if ($parent === null) {
            return null;
        }

        if (! is_array($parent)
            || array_diff(array_keys($parent), ['scope', 'id']) !== []
            || ! is_string($parent['scope'] ?? null)
            || trim($parent['scope']) === ''
            || (! is_int($parent['id'] ?? null) && ! is_string($parent['id'] ?? null))
            || (is_string($parent['id']) && trim($parent['id']) === '')) {
            throw new InvalidArgumentException('Invalid table parent-scope state.');
        }

        return ['scope' => trim($parent['scope']), 'id' => $parent['id']];
    }

    private static function normalizeSearch(mixed $search): ?string
    {
        if ($search === null || $search === '') {
            return null;
        }

        if (! is_string($search)) {
            throw new InvalidArgumentException('Table query-state search must be a string.');
        }

        return $search;
    }

    /**
     * @return list<array{key: string, direction: 'asc'|'desc'}>
     */
    private static function normalizeSorts(mixed $sorts): array
    {
        if (! is_array($sorts) || ! array_is_list($sorts)) {
            throw new InvalidArgumentException('Table query-state sorts must be a list.');
        }

        $normalized = [];

        foreach ($sorts as $sort) {
            if (! is_array($sort)
                || array_keys($sort) !== ['key', 'direction']
                || ! is_string($sort['key'])
                || trim($sort['key']) === ''
                || ! is_string($sort['direction'])
                || ! in_array(strtolower($sort['direction']), ['asc', 'desc'], true)) {
                throw new InvalidArgumentException('Invalid table query-state sort.');
            }

            $normalized[] = [
                'key' => trim($sort['key']),
                'direction' => strtolower($sort['direction']),
            ];
        }

        return $normalized;
    }
}
