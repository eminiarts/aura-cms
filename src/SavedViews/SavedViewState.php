<?php

namespace Aura\Base\SavedViews;

use Aura\Base\Contracts\TableColumnCapabilityResolver;
use Aura\Base\Resource;
use Aura\Base\Support\KanbanConfiguration;
use Aura\Base\Table\TableQueryState;
use Aura\Base\Table\TableQueryStateApplier;
use InvalidArgumentException;
use JsonSerializable;

final readonly class SavedViewState implements JsonSerializable
{
    public const VERSION = 1;

    /**
     * @param  list<string>  $columns
     * @param  array{key: string, columns: list<array{key: string, visible: bool}>}|null  $grouping
     */
    private function __construct(
        public TableQueryState $query,
        public array $columns,
        public string $viewMode,
        public ?array $grouping,
    ) {}

    /** @param array<string, mixed> $state */
    public static function fromArray(array $state, Resource $resource): self
    {
        self::assertJsonCompatible($state);
        $state = self::upgrade($state);

        if (array_diff(array_keys($state), ['v', 'query', 'columns', 'view_mode', 'grouping']) !== []) {
            throw new InvalidArgumentException('Unknown saved-view state key.');
        }

        if (($state['v'] ?? null) !== self::VERSION || ! is_array($state['query'] ?? null)) {
            throw new InvalidArgumentException('Unsupported saved-view state version.');
        }

        $query = TableQueryState::fromArray($state['query']);
        $resolver = $resource instanceof TableColumnCapabilityResolver ? $resource : null;

        if (! (new TableQueryStateApplier($resolver))->accepts($resource, $query)) {
            throw new InvalidArgumentException('The saved-view query state is no longer supported by this resource.');
        }

        $columns = self::normalizeColumns($state['columns'] ?? [], $resource);
        $viewMode = self::normalizeViewMode($state['view_mode'] ?? 'list', $resource);
        $grouping = self::normalizeGrouping($state['grouping'] ?? null, $resource, $viewMode);

        return new self($query, $columns, $viewMode, $grouping);
    }

    /** @return array<string, mixed> */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /** @return array<string, mixed> */
    public function toArray(): array
    {
        return [
            'v' => self::VERSION,
            'query' => $this->query->toArray(),
            'columns' => $this->columns,
            'view_mode' => $this->viewMode,
            'grouping' => $this->grouping,
        ];
    }

    private static function assertJsonCompatible(mixed $value): void
    {
        if ($value === null || is_bool($value) || is_int($value)) {
            return;
        }

        if (is_float($value)) {
            if (is_finite($value)) {
                return;
            }

            throw new InvalidArgumentException('Saved-view state must contain finite JSON numbers.');
        }

        if (is_string($value)) {
            if (preg_match('//u', $value) === 1) {
                return;
            }

            throw new InvalidArgumentException('Saved-view state must contain valid UTF-8 strings.');
        }

        if (is_array($value)) {
            foreach ($value as $key => $item) {
                if (is_string($key) && preg_match('//u', $key) !== 1) {
                    throw new InvalidArgumentException('Saved-view state must contain valid UTF-8 keys.');
                }

                self::assertJsonCompatible($item);
            }

            return;
        }

        throw new InvalidArgumentException('Saved-view state must contain only JSON-compatible values.');
    }

    /** @return list<string> */
    private static function normalizeColumns(mixed $columns, Resource $resource): array
    {
        if (! is_array($columns) || ! array_is_list($columns)) {
            throw new InvalidArgumentException('Saved-view columns must be a list.');
        }

        $available = array_fill_keys(array_keys($resource->getTableHeaders()->all()), true);
        $normalized = [];

        foreach ($columns as $column) {
            if (! is_string($column) || $column === '' || ! isset($available[$column]) || in_array($column, $normalized, true)) {
                throw new InvalidArgumentException('Saved-view columns must be unique declared table columns.');
            }

            $normalized[] = $column;
        }

        return $normalized;
    }

    /** @return array{key: string, columns: list<array{key: string, visible: bool}>}|null */
    private static function normalizeGrouping(mixed $grouping, Resource $resource, string $viewMode): ?array
    {
        if ($grouping === null) {
            return null;
        }

        if ($viewMode !== 'kanban'
            || ! is_array($grouping)
            || array_keys($grouping) !== ['key', 'columns']
            || ! is_string($grouping['key'])
            || ! is_array($grouping['columns'])
            || ! array_is_list($grouping['columns'])) {
            throw new InvalidArgumentException('Invalid saved-view grouping state.');
        }

        $configuration = KanbanConfiguration::for($resource);
        $declared = array_keys($configuration['columns']);
        $normalized = [];

        foreach ($grouping['columns'] as $column) {
            if (! is_array($column)
                || array_keys($column) !== ['key', 'visible']
                || ! is_string($column['key'])
                || ! is_bool($column['visible'])
                || ! in_array($column['key'], $declared, true)
                || collect($normalized)->contains('key', $column['key'])) {
                throw new InvalidArgumentException('Invalid saved-view grouping column.');
            }

            $normalized[] = ['key' => $column['key'], 'visible' => $column['visible']];
        }

        if ($grouping['key'] !== $configuration['group_field']
            || array_diff($declared, array_column($normalized, 'key')) !== []) {
            throw new InvalidArgumentException('The saved-view grouping no longer matches the resource declaration.');
        }

        return ['key' => $grouping['key'], 'columns' => $normalized];
    }

    private static function normalizeViewMode(mixed $viewMode, Resource $resource): string
    {
        if (! is_string($viewMode)) {
            throw new InvalidArgumentException('Invalid saved-view mode.');
        }

        $supported = ['list'];

        if (is_string($resource->tableGridView()) && $resource->tableGridView() !== '') {
            $supported[] = 'grid';
        }

        if (KanbanConfiguration::for($resource)['enabled']) {
            $supported[] = 'kanban';
        }

        if (! in_array($viewMode, $supported, true)) {
            throw new InvalidArgumentException('The saved-view mode is not supported by this resource.');
        }

        return $viewMode;
    }

    /**
     * Version zero was the pre-release flat state used by the original saved-filter prototype.
     *
     * @param  array<string, mixed>  $state
     * @return array<string, mixed>
     */
    private static function upgrade(array $state): array
    {
        if (($state['v'] ?? null) !== 0) {
            return $state;
        }

        if (array_diff(array_keys($state), ['v', 'filters', 'search', 'sorts', 'parent', 'columns', 'view_mode', 'grouping']) !== []) {
            throw new InvalidArgumentException('Unknown legacy saved-view state key.');
        }

        return [
            'v' => self::VERSION,
            'query' => [
                'v' => TableQueryState::VERSION,
                'filters' => $state['filters'] ?? [],
                'search' => $state['search'] ?? null,
                'sorts' => $state['sorts'] ?? [],
                ...array_key_exists('parent', $state) ? ['parent' => $state['parent']] : [],
            ],
            'columns' => $state['columns'] ?? [],
            'view_mode' => $state['view_mode'] ?? 'list',
            'grouping' => $state['grouping'] ?? null,
        ];
    }
}
