<?php

namespace Aura\Base\Traits\Concerns;

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
            '1d' => 'Last day',
            '7d' => 'Last 7 days',
            '30d' => 'Last 30 days',
            '60d' => 'Last 60 days',
            '90d' => 'Last 90 days',
            '180d' => 'Last 180 days',
            '365d' => 'Last 365 days',
            'all' => 'All',
            'ytd' => 'Year to date',
            'qtd' => 'Quarter to date',
            'mtd' => 'Month to date',
            'wtd' => 'Week to date',
            'last-year' => 'Last year',
            'last-month' => 'Last month',
            'last-week' => 'Last week',
            'custom' => 'Custom',
        ],
    ];

    protected static bool $title = false;

    public static function getFields(): array
    {
        return [];
    }

    public static function getGlobalSearch()
    {
        return static::$globalSearch;
    }

    public static function getWidgets(): array
    {
        return [];
    }

    public static function usesTitle(): bool
    {
        return static::$title;
    }
}
