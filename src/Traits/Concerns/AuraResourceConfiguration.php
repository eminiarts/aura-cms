<?php

namespace Aura\Base\Traits\Concerns;

use Illuminate\Support\Collection;

trait AuraResourceConfiguration
{
    public const SCOPE_GLOBAL = 'global';

    public const SCOPE_OWNER = 'owner';

    public const SCOPE_TEAM = 'team';

    public static $createEnabled = true;

    public static $editEnabled = true;

    public static $globalSearch = true;

    public static bool $indexViewEnabled = true;

    public static ?string $ownerColumn = 'user_id';

    public static ?string $ownerRelation = 'user';

    /**
     * Owner keeps Aura's historical owner-plus-team behavior. Team resources
     * have no user owner, while global resources have neither owner nor team.
     */
    public static string $scopeMode = self::SCOPE_OWNER;

    public static bool $sharedAcrossTeams = false;

    public static ?string $teamColumn = 'team_id';

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

    /**
     * Apply resource-specific SQL visibility before the bounded candidate
     * window is selected. Global model scopes remain active by default.
     */
    public function applyGlobalSearchVisibility($query, $user)
    {
        return $query;
    }

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
    public function getGlobalSearchableFields()
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

    public static function getOwnerColumn(): ?string
    {
        return static::usesOwnerScope() ? static::$ownerColumn : null;
    }

    public static function getOwnerRelation(): ?string
    {
        return static::usesOwnerScope() ? static::$ownerRelation : null;
    }

    public static function getScopeMode(): string
    {
        if (! in_array(static::$scopeMode, [self::SCOPE_OWNER, self::SCOPE_TEAM, self::SCOPE_GLOBAL], true)) {
            throw new \LogicException(sprintf(
                'Resource [%s] declares unsupported scope mode [%s].',
                static::class,
                static::$scopeMode,
            ));
        }

        if (static::$scopeMode === self::SCOPE_OWNER
            && (! is_string(static::$ownerColumn) || static::$ownerColumn === '')) {
            throw new \LogicException(sprintf('Owner-scoped resource [%s] must declare an owner column.', static::class));
        }

        if (config('aura.teams')
            && in_array(static::$scopeMode, [self::SCOPE_OWNER, self::SCOPE_TEAM], true)
            && (! is_string(static::$teamColumn) || static::$teamColumn === '')) {
            throw new \LogicException(sprintf('Team-scoped resource [%s] must declare a team column.', static::class));
        }

        return static::$scopeMode;
    }

    public static function getTeamColumn(): ?string
    {
        return static::usesTeamScope() ? static::$teamColumn : null;
    }

    public static function getWidgets(): array
    {
        return [];
    }

    /**
     * Return the adapter class responsible for bounded candidate discovery.
     */
    public function globalSearchAdapter()
    {
        return config('aura.global_search.adapter');
    }

    /**
     * Explicitly trust searches without a current team. The secure default is
     * false so TeamScope's historical null-team bypass cannot expose records.
     */
    public function globalSearchAllowsMissingTeamContext($user)
    {
        return false;
    }

    /**
     * Declare only the meta keys and direct BelongsTo relations needed by title().
     *
     * @return array{meta: array<int, string>, relations: array<int, string>}
     */
    public function globalSearchTitleDependencies()
    {
        return ['meta' => [], 'relations' => []];
    }

    public function newGlobalSearchQuery()
    {
        return $this->newQuery();
    }

    public static function sharesRecordsAcrossTeams(): bool
    {
        return static::$sharedAcrossTeams;
    }

    public static function usesOwnerScope(): bool
    {
        return static::getScopeMode() === self::SCOPE_OWNER;
    }

    public static function usesTeamScope(): bool
    {
        if (! config('aura.teams')) {
            return false;
        }

        return in_array(static::getScopeMode(), [self::SCOPE_OWNER, self::SCOPE_TEAM], true);
    }

    public static function usesTitle(): bool
    {
        return static::$title;
    }
}
