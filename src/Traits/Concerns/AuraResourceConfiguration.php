<?php

namespace Aura\Base\Traits\Concerns;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

trait AuraResourceConfiguration
{
    public static $createEnabled = true;

    public static $editEnabled = true;

    public static $globalSearch = true;

    public static bool $indexViewEnabled = true;

    public static $viewEnabled = true;

    public array $widgetSettings = [
        'default' => '30d',
        'options' => [
            '1d' => '1 Day',
            '7d' => '7 Days',
            '30d' => '30 Days',
            '60d' => '60 Days',
            '90d' => '90 Days',
            '180d' => '180 Days',
            '365d' => '365 Days',
            'all' => 'All',
            'ytd' => 'Year to Date',
            'qtd' => 'Quarter to Date',
            'mtd' => 'Month to Date',
            'wtd' => 'Week to Date',
            'last-year' => 'Last Year',
            'last-month' => 'Last Month',
            'last-week' => 'Last Week',
            'custom' => 'Custom',
        ],
    ];

    protected static array $searchable = [];

    protected static bool $title = false;

    public static function getFields()
    {
        return [];
    }

    public static function getGlobalSearch()
    {
        return static::$globalSearch;
    }

    /**
     * Get the fields that participate in global search, in ranking order.
     *
     * A non-empty $searchable property is the explicit contract. Its values may
     * be slugs, or slug => weight pairs. The field-level searchable flag remains
     * the fallback for resources that predate that property.
     *
     * @return Collection<int, non-empty-array>
     */
    public function getGlobalSearchableFields(): Collection
    {
        $fields = $this->inputFields()
            ->filter(fn ($field): bool => is_array($field) && is_string($field['slug'] ?? null))
            ->keyBy('slug');

        if (static::$searchable === []) {
            return $fields
                ->filter(fn (array $field): bool => (bool) ($field['searchable'] ?? false))
                ->values();
        }

        return collect(static::$searchable)
            ->map(function ($configuration, $key) use ($fields): ?array {
                $slug = is_int($key) ? $configuration : $key;

                if (! is_string($slug) || ! $fields->has($slug)) {
                    return null;
                }

                $field = $fields->get($slug);
                $weight = is_int($key)
                    ? null
                    : (is_array($configuration) ? ($configuration['weight'] ?? null) : $configuration);

                if (is_numeric($weight)) {
                    $field['global_search_weight'] = (int) $weight;
                }

                return $field;
            })
            ->filter()
            ->values();
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public function newGlobalSearchQuery(): Builder
    {
        return static::query();
    }

    public static function usesTitle(): bool
    {
        return static::$title;
    }
}
