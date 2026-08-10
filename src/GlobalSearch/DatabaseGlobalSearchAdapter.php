<?php

namespace Aura\Base\GlobalSearch;

use Aura\Base\Contracts\GlobalSearchAdapter;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Stringable;

final class DatabaseGlobalSearchAdapter implements GlobalSearchAdapter
{
    /**
     * @param  Collection<int, array<string, mixed>>  $fields
     * @return Collection<int, GlobalSearchCandidate>
     */
    public function search(
        Resource $resource,
        Builder $query,
        Collection $fields,
        string $term,
        int $candidateLimit,
        GlobalSearchBudget $budget,
    ): Collection {
        if (! $budget->claimQuery($resource)) {
            return collect();
        }

        $sealedQuery = clone $query->getQuery();

        if ($sealedQuery->beforeQueryCallbacks !== []) {
            return collect();
        }

        $sealedQuery
            ->select($resource->getTable().'.*')
            ->distinct()
            ->reorder();
        $sealedQuery->limit = null;
        $sealedQuery->offset = null;
        $sealedQuery->unionLimit = null;
        $sealedQuery->unionOffset = null;
        $trustedSql = $sealedQuery->toSql();
        $trustedBindings = $sealedQuery->getBindings();
        $candidateAlias = 'aura_global_search_candidates';
        $candidateQuery = $resource->getConnection()
            ->query()
            ->fromSub($sealedQuery, $candidateAlias)
            ->select($candidateAlias.'.*')
            ->orderBy($candidateAlias.'.'.$resource->getKeyName())
            ->limit($candidateLimit);

        if ($sealedQuery->beforeQueryCallbacks !== []
            || $candidateQuery->beforeQueryCallbacks !== []
            || $candidateQuery->limit !== $candidateLimit
            || $candidateQuery->offset !== null
            || $sealedQuery->toSql() !== $trustedSql
            || $sealedQuery->getBindings() !== $trustedBindings
            || ($candidateQuery->getRawBindings()['from'] ?? null) !== $trustedBindings) {
            return collect();
        }

        $rows = $candidateQuery->get();

        if ($rows->count() > $candidateLimit) {
            return collect();
        }

        if ($rows->isEmpty()) {
            return collect();
        }

        $metaFields = $fields
            ->filter(fn (array $field): bool => $resource->isMetaField($field['slug']))
            ->pluck('slug')
            ->values();
        $metaValues = $this->metaValues($resource, $rows, $metaFields, $candidateLimit, $budget);

        if ($metaValues === null) {
            return collect();
        }

        return $rows
            ->map(function (object $row) use ($fields, $metaValues, $resource, $term): ?GlobalSearchCandidate {
                $attributes = (array) $row;
                $key = $attributes[$resource->getKeyName()] ?? null;

                if (! is_int($key) && ! is_string($key)) {
                    return null;
                }

                $rank = $this->rank($resource, $attributes, $metaValues[(string) $key] ?? [], $fields, $term);

                if ($rank === 0) {
                    return null;
                }

                $model = $resource->newInstance([], true);
                $model->setRawAttributes($attributes, true);
                $model->setConnection($resource->getConnectionName());
                $model->preventsLazyLoading = true;

                return new GlobalSearchCandidate($model, $rank);
            })
            ->filter()
            ->sort(function (GlobalSearchCandidate $left, GlobalSearchCandidate $right): int {
                $rankComparison = $right->rank <=> $left->rank;

                if ($rankComparison !== 0) {
                    return $rankComparison;
                }

                return $this->compareKeys($left->resource->getKey(), $right->resource->getKey());
            })
            ->values();
    }

    private function compareKeys(mixed $left, mixed $right): int
    {
        if (is_int($left) && is_int($right)) {
            return $left <=> $right;
        }

        return strcmp((string) $left, (string) $right);
    }

    private function fieldWeight(array $field, int $fallback): int
    {
        $weight = $field['global_search_weight'] ?? $fallback;

        return min(max(is_numeric($weight) ? (int) $weight : $fallback, 0), 10_000);
    }

    /**
     * @param  Collection<int, object>  $rows
     * @param  Collection<int, string>  $fields
     * @return array<string, array<string, mixed>>|null
     */
    private function metaValues(
        Resource $resource,
        Collection $rows,
        Collection $fields,
        int $candidateLimit,
        GlobalSearchBudget $budget,
    ): ?array {
        if ($fields->isEmpty()) {
            return [];
        }

        if (! $budget->claimQuery($resource)) {
            return null;
        }

        $keys = $rows
            ->map(fn (object $row): mixed => $row->{$resource->getKeyName()} ?? null)
            ->filter(fn (mixed $key): bool => is_int($key) || is_string($key))
            ->values();
        $maximumMetaRows = max(1, $candidateLimit * $fields->count());
        $metaRows = $resource->getConnection()
            ->table($resource->getMetaTable())
            ->where('metable_type', $resource->getMorphClass())
            ->whereIn($resource->getMetaForeignKey(), $keys)
            ->whereIn('key', $fields)
            ->select([$resource->getMetaForeignKey(), 'key', 'value'])
            ->orderBy($resource->getMetaForeignKey())
            ->orderBy('key')
            ->limit($maximumMetaRows)
            ->get();

        $values = [];

        foreach ($metaRows as $metaRow) {
            $values[(string) $metaRow->{$resource->getMetaForeignKey()}][(string) $metaRow->key] = $metaRow->value;
        }

        return $values;
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @param  array<string, mixed>  $meta
     * @param  Collection<int, array<string, mixed>>  $fields
     */
    private function rank(
        Resource $resource,
        array $attributes,
        array $meta,
        Collection $fields,
        string $term,
    ): int {
        $best = 0;

        foreach ($fields as $fieldIndex => $field) {
            $slug = $field['slug'];
            $value = $resource->isMetaField($slug)
                ? ($meta[$slug] ?? null)
                : ($attributes[$slug] ?? null);
            $text = $this->searchableText($value);

            if ($text === null) {
                continue;
            }

            $match = match (true) {
                $text === $term => 'exact',
                str_starts_with($text, $term) => 'prefix',
                str_contains($text, $term) => 'contains',
                default => null,
            };

            if ($match === null) {
                continue;
            }

            $fallback = $fields->count() - $fieldIndex;
            $best = max($best, $this->rankingWeight($match) + $this->fieldWeight($field, $fallback));
        }

        return $best;
    }

    private function rankingWeight(string $match): int
    {
        $defaults = ['exact' => 300, 'prefix' => 200, 'contains' => 100];
        $configured = config("aura.global_search.ranking.{$match}", $defaults[$match]);

        return min(max(is_numeric($configured) ? (int) $configured : $defaults[$match], 0), 1_000_000);
    }

    private function searchableText(mixed $value): ?string
    {
        if (is_string($value) || is_int($value) || is_float($value)) {
            return (string) $value;
        }

        if ($value instanceof Stringable) {
            return (string) $value;
        }

        return null;
    }
}
