<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resource;
use Illuminate\Contracts\View\View;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;

class GlobalSearch extends Component
{
    private const DEFAULT_GLOBAL_LIMIT = 15;

    private const DEFAULT_MAX_FIELDS_PER_RESOURCE = 8;

    private const DEFAULT_MAX_QUERY_LENGTH = 64;

    private const DEFAULT_MAX_RESOURCES = 25;

    private const DEFAULT_MINIMUM_QUERY_LENGTH = 2;

    private const DEFAULT_PER_RESOURCE_LIMIT = 5;

    private const HARD_MAX_FIELDS_PER_RESOURCE = 32;

    private const HARD_MAX_GLOBAL_LIMIT = 100;

    private const HARD_MAX_PER_RESOURCE_LIMIT = 50;

    private const HARD_MAX_QUERY_LENGTH = 256;

    private const HARD_MAX_RESOURCES = 100;

    private const RANK_ATTRIBUTE = 'aura_global_search_rank';

    public $bookmarks;

    public $search = '';

    #[Computed]
    public function getSearchResultsProperty(): Collection
    {
        $searchTerm = $this->normalizedSearchTerm();

        if (Str::length($searchTerm) < $this->configuredLimit(
            'minimum_query_length',
            self::DEFAULT_MINIMUM_QUERY_LENGTH,
            self::HARD_MAX_QUERY_LENGTH,
        )) {
            return collect();
        }

        $globalLimit = $this->configuredLimit(
            'global_limit',
            self::DEFAULT_GLOBAL_LIMIT,
            self::HARD_MAX_GLOBAL_LIMIT,
        );
        $candidates = collect();

        foreach ($this->searchableResources() as $resourceOrder => $resource) {
            $candidates = $candidates->concat(
                $this->searchResource($resource, $resourceOrder, $searchTerm, $globalLimit),
            );
        }

        return $candidates
            ->sort(fn (array $left, array $right): int => $this->compareCandidates($left, $right))
            ->take($globalLimit)
            ->pluck('resource')
            ->groupBy(fn (Resource $resource): string => $resource->getType());
    }

    public function mount(): void
    {
        if (! config('aura.features.global_search')) {
            abort(403, 'Global search is disabled');
        }

        if (auth()->check()) {
            $this->bookmarks = auth()->user()->getOptionBookmarks();
        } else {
            $this->bookmarks = [];
        }
    }

    public function render(): View
    {
        if (auth()->check()) {
            $this->bookmarks = auth()->user()->getOptionBookmarks();
        } else {
            $this->bookmarks = [];
        }

        return view('aura::livewire.global-search');
    }

    private function compareCandidates(array $left, array $right): int
    {
        $rankComparison = $right['rank'] <=> $left['rank'];

        if ($rankComparison !== 0) {
            return $rankComparison;
        }

        $resourceComparison = $left['resource_order'] <=> $right['resource_order'];

        if ($resourceComparison !== 0) {
            return $resourceComparison;
        }

        $leftKey = $left['resource']->getKey();
        $rightKey = $right['resource']->getKey();

        if (is_int($leftKey) && is_int($rightKey)) {
            return $leftKey <=> $rightKey;
        }

        return strcmp((string) $leftKey, (string) $rightKey);
    }

    private function configuredLimit(string $key, int $default, int $hardMaximum): int
    {
        $configured = config("aura.global_search.{$key}", $default);
        $value = is_numeric($configured) ? (int) $configured : $default;

        return min(max($value, 1), $hardMaximum);
    }

    private function escapeLikeTerm(string $searchTerm): string
    {
        return str_replace(['!', '%', '_'], ['!!', '!%', '!_'], $searchTerm);
    }

    private function fieldWeight(array $field, int $fallback): int
    {
        $weight = $field['global_search_weight'] ?? $fallback;

        return min(max(is_numeric($weight) ? (int) $weight : $fallback, 0), 10_000);
    }

    /**
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function matchCondition(
        Builder $query,
        Resource $resource,
        string $field,
        string $pattern,
        bool $exact,
    ): array {
        $grammar = $query->getQuery()->getGrammar();
        $comparison = $exact ? '= LOWER(?)' : "LIKE LOWER(?) ESCAPE '!'";

        if (! $resource->isMetaField($field)) {
            $column = $grammar->wrap($resource->qualifyColumn($field));

            return [
                $this->textExpression($query, $column).' '.$comparison,
                [$pattern],
            ];
        }

        $metaTable = $resource->getMetaTable();
        $qualifiedMetaForeignKey = $grammar->wrap($metaTable.'.'.$resource->getMetaForeignKey());
        $qualifiedMetaKey = $grammar->wrap($metaTable.'.key');
        $qualifiedMetaType = $grammar->wrap($metaTable.'.metable_type');
        $qualifiedMetaValue = $grammar->wrap($metaTable.'.value');

        return [
            'EXISTS (SELECT 1 FROM '.$grammar->wrapTable($metaTable)
                .' WHERE '.$grammar->wrap($resource->getQualifiedKeyName()).' = '.$qualifiedMetaForeignKey
                .' AND '.$qualifiedMetaType.' = ?'
                .' AND '.$qualifiedMetaKey.' = ?'
                .' AND '.$this->textExpression($query, $qualifiedMetaValue).' '.$comparison.')',
            [$resource->getMorphClass(), $field, $pattern],
        ];
    }

    private function normalizedSearchTerm(): string
    {
        if (! is_scalar($this->search)) {
            return '';
        }

        $maximumLength = $this->configuredLimit(
            'maximum_query_length',
            self::DEFAULT_MAX_QUERY_LENGTH,
            self::HARD_MAX_QUERY_LENGTH,
        );

        return Str::substr(trim((string) $this->search), 0, $maximumLength);
    }

    /**
     * @param  Collection<int, array<string, mixed>>  $fields
     * @return array{0: string, 1: array<int, mixed>}
     */
    private function rankExpression(
        Builder $query,
        Resource $resource,
        Collection $fields,
        string $searchTerm,
    ): array {
        $escapedSearchTerm = $this->escapeLikeTerm($searchTerm);
        $matchTypes = [
            ['name' => 'exact', 'pattern' => $searchTerm, 'exact' => true],
            ['name' => 'prefix', 'pattern' => $escapedSearchTerm.'%', 'exact' => false],
            ['name' => 'contains', 'pattern' => '%'.$escapedSearchTerm.'%', 'exact' => false],
        ];
        $criteria = [];
        $sequence = 0;

        foreach ($fields as $fieldIndex => $field) {
            $fieldWeight = $this->fieldWeight($field, $fields->count() - $fieldIndex);

            foreach ($matchTypes as $matchType) {
                [$condition, $bindings] = $this->matchCondition(
                    $query,
                    $resource,
                    $field['slug'],
                    $matchType['pattern'],
                    $matchType['exact'],
                );

                $criteria[] = [
                    'bindings' => $bindings,
                    'condition' => $condition,
                    'score' => $this->rankingWeight($matchType['name']) + $fieldWeight,
                    'sequence' => $sequence++,
                ];
            }
        }

        usort($criteria, function (array $left, array $right): int {
            return ($right['score'] <=> $left['score'])
                ?: ($left['sequence'] <=> $right['sequence']);
        });

        $bindings = [];
        $clauses = collect($criteria)->map(function (array $criterion) use (&$bindings): string {
            array_push($bindings, ...$criterion['bindings']);

            return "WHEN {$criterion['condition']} THEN {$criterion['score']}";
        })->implode(' ');

        return ["CASE {$clauses} ELSE 0 END", $bindings];
    }

    private function rankingWeight(string $matchType): int
    {
        $defaults = [
            'exact' => 300,
            'prefix' => 200,
            'contains' => 100,
        ];
        $configured = config("aura.global_search.ranking.{$matchType}", $defaults[$matchType]);

        return min(max(is_numeric($configured) ? (int) $configured : $defaults[$matchType], 0), 1_000_000);
    }

    /**
     * @return Collection<int, \Aura\Base\Resource>
     */
    private function searchableResources(): Collection
    {
        $maximumResources = $this->configuredLimit(
            'max_resources',
            self::DEFAULT_MAX_RESOURCES,
            self::HARD_MAX_RESOURCES,
        );

        return collect(app('aura')::getResources())
            ->filter(fn ($resource): bool => is_string($resource) && is_subclass_of($resource, Resource::class))
            ->unique()
            ->filter(fn (string $resource): bool => $resource::getGlobalSearch() === true)
            ->take($maximumResources)
            ->map(fn (string $resource): Resource => app($resource))
            ->filter(fn (Resource $resource): bool => Gate::allows('viewAny', $resource))
            ->values();
    }

    /**
     * @return Collection<int, array{resource: \Aura\Base\Resource, rank: int, resource_order: int}>
     */
    private function searchResource(
        Resource $resource,
        int $resourceOrder,
        string $searchTerm,
        int $globalLimit,
    ): Collection {
        $maximumFields = $this->configuredLimit(
            'max_fields_per_resource',
            self::DEFAULT_MAX_FIELDS_PER_RESOURCE,
            self::HARD_MAX_FIELDS_PER_RESOURCE,
        );
        $fields = $resource->getGlobalSearchableFields()
            ->filter(fn ($field): bool => is_array($field) && is_string($field['slug'] ?? null))
            ->unique('slug')
            ->take($maximumFields)
            ->values();

        if ($fields->isEmpty()) {
            return collect();
        }

        $query = $resource->newGlobalSearchQuery()
            ->select($resource->getTable().'.*');
        $containsPattern = '%'.$this->escapeLikeTerm($searchTerm).'%';

        $query->where(function (Builder $query) use ($containsPattern, $fields, $resource): void {
            foreach ($fields as $field) {
                [$condition, $bindings] = $this->matchCondition(
                    $query,
                    $resource,
                    $field['slug'],
                    $containsPattern,
                    false,
                );

                $query->orWhereRaw($condition, $bindings);
            }
        });

        [$rankExpression, $rankBindings] = $this->rankExpression($query, $resource, $fields, $searchTerm);
        $rankAlias = $query->getQuery()->getGrammar()->wrap(self::RANK_ATTRIBUTE);
        $perResourceLimit = min(
            $globalLimit,
            $this->configuredLimit(
                'per_resource_limit',
                self::DEFAULT_PER_RESOURCE_LIMIT,
                self::HARD_MAX_PER_RESOURCE_LIMIT,
            ),
        );

        return $query
            ->selectRaw("{$rankExpression} AS {$rankAlias}", $rankBindings)
            ->reorder()
            ->orderByDesc(self::RANK_ATTRIBUTE)
            ->orderBy($resource->getQualifiedKeyName())
            ->limit($perResourceLimit)
            ->get()
            ->filter(fn (Resource $result): bool => Gate::allows('view', $result))
            ->map(function (Resource $result) use ($resourceOrder): ?array {
                $url = $result->globalSearchUrl();

                if (! is_string($url) || $url === '') {
                    return null;
                }

                $rank = (int) $result->getAttribute(self::RANK_ATTRIBUTE);
                $result->offsetUnset(self::RANK_ATTRIBUTE);
                $result->setAttribute('type', $result->getType());
                $result->setAttribute('view_url', $url);

                return [
                    'resource' => $result,
                    'rank' => $rank,
                    'resource_order' => $resourceOrder,
                ];
            })
            ->filter()
            ->values();
    }

    private function textExpression(Builder $query, string $wrappedColumn): string
    {
        $connection = $query->getConnection();
        $driver = $connection instanceof Connection
            ? $connection->getDriverName()
            : config('database.connections.'.config('database.default').'.driver');

        return match ($driver) {
            'mysql', 'mariadb' => "LOWER(CAST({$wrappedColumn} AS CHAR))",
            'sqlsrv' => "LOWER(CAST({$wrappedColumn} AS NVARCHAR(MAX)))",
            default => "LOWER(CAST({$wrappedColumn} AS TEXT))",
        };
    }
}
