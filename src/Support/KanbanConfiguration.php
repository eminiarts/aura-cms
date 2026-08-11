<?php

namespace Aura\Base\Support;

use Aura\Base\Contracts\TableResource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Collection;

final class KanbanConfiguration
{
    /**
     * @return array{
     *     enabled: bool,
     *     valid: bool,
     *     group_field: string,
     *     columns: array<string, array{value: string, color: string|null}>,
     *     card_title: string,
     *     card_subtitle: string|null,
     *     order_by: array{field: string, direction: 'asc'|'desc'}|null,
     *     show_empty_columns: bool
     * }
     */
    public static function for(Model&TableResource $resource): array
    {
        $legacyView = method_exists($resource, 'tableKanbanView')
            ? $resource->tableKanbanView()
            : false;
        $declared = method_exists($resource, 'kanbanSettings')
            ? $resource->kanbanSettings()
            : [];

        if (! is_array($declared)) {
            $declared = [];
        }

        $enabled = (bool) ($declared['enabled'] ?? (is_string($legacyView) && $legacyView !== ''));
        $groupField = is_string($declared['group_field'] ?? null)
            ? $declared['group_field']
            : 'status';
        $cardTitle = is_string($declared['card_title'] ?? null)
            ? $declared['card_title']
            : 'title';
        $cardSubtitle = is_string($declared['card_subtitle'] ?? null)
            ? $declared['card_subtitle']
            : null;
        $showEmptyColumns = $declared['show_empty_columns'] ?? true;

        $field = $resource->fieldBySlug($groupField);
        $fieldClass = $resource->fieldClassBySlug($groupField);
        $columns = is_array($field) && is_object($fieldClass) && method_exists($fieldClass, 'options')
            ? self::normalizeOptions($fieldClass->options($resource, $field))
            : [];

        $requestedColumns = $declared['columns'] ?? [];
        if (! is_array($requestedColumns)) {
            $requestedColumns = [];
        }

        if ($requestedColumns !== []) {
            $requestedKeys = collect($requestedColumns)
                ->filter(fn (mixed $key): bool => is_string($key) || is_int($key))
                ->map(fn (string|int $key): string => (string) $key)
                ->unique()
                ->values();

            if ($requestedKeys->count() !== count($requestedColumns) || $requestedKeys->contains(fn (string $key): bool => ! isset($columns[$key]))) {
                $columns = [];
            } else {
                $columns = $requestedKeys->mapWithKeys(fn (string $key): array => [$key => $columns[$key]])->all();
            }
        }

        $orderBy = self::normalizeOrder($resource, $declared['order_by'] ?? null);
        $valid = $groupField !== ''
            && $columns !== []
            && self::isDisplayableField($resource, $cardTitle)
            && ($cardSubtitle === null || self::isDisplayableField($resource, $cardSubtitle))
            && is_bool($showEmptyColumns)
            && (! array_key_exists('order_by', $declared) || $declared['order_by'] === null || $orderBy !== null);

        return [
            'enabled' => $enabled && $valid,
            'valid' => $valid,
            'group_field' => $groupField,
            'columns' => $columns,
            'card_title' => $cardTitle,
            'card_subtitle' => $cardSubtitle,
            'order_by' => $orderBy,
            'show_empty_columns' => is_bool($showEmptyColumns) ? $showEmptyColumns : true,
        ];
    }

    private static function isDisplayableField(Model&TableResource $resource, string $field): bool
    {
        return $field !== '' && ($field === $resource->getKeyName() || is_array($resource->fieldBySlug($field)));
    }

    /**
     * @return array<string, array{value: string, color: string|null}>
     */
    private static function normalizeOptions(mixed $options): array
    {
        if ($options instanceof Collection) {
            $options = $options->all();
        }

        if (! is_array($options)) {
            return [];
        }

        $normalized = [];

        foreach ($options as $optionKey => $option) {
            if (is_array($option) && array_key_exists('key', $option)) {
                $key = $option['key'];
                $value = $option['value'] ?? $option['label'] ?? $key;
                $color = $option['color'] ?? null;
            } elseif (! is_int($optionKey)) {
                $key = $optionKey;
                $value = is_array($option) ? ($option['value'] ?? $option['label'] ?? $optionKey) : $option;
                $color = is_array($option) ? ($option['color'] ?? null) : null;
            } else {
                $key = $option;
                $value = $option;
                $color = null;
            }

            if ((! is_string($key) && ! is_int($key)) || (! is_string($value) && ! is_int($value))) {
                continue;
            }

            $normalized[(string) $key] = [
                'value' => (string) $value,
                'color' => is_string($color) ? $color : null,
            ];
        }

        return $normalized;
    }

    /**
     * @return array{field: string, direction: 'asc'|'desc'}|null
     */
    private static function normalizeOrder(Model&TableResource $resource, mixed $orderBy): ?array
    {
        if ($orderBy === null) {
            return null;
        }

        if (! is_array($orderBy)) {
            return null;
        }

        $field = $orderBy['field'] ?? null;
        $direction = $orderBy['direction'] ?? null;

        if (
            ! is_string($field)
            || ! self::isDisplayableField($resource, $field)
            || ! is_string($direction)
            || ! in_array(strtolower($direction), ['asc', 'desc'], true)
        ) {
            return null;
        }

        return [
            'field' => $field,
            'direction' => strtolower($direction),
        ];
    }
}
