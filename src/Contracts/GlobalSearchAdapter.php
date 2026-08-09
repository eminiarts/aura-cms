<?php

namespace Aura\Base\Contracts;

use Aura\Base\GlobalSearch\GlobalSearchBudget;
use Aura\Base\GlobalSearch\GlobalSearchCandidate;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

interface GlobalSearchAdapter
{
    /**
     * Return ranked candidates from a bounded search window.
     *
     * Implementations must claim every database operation through the supplied
     * budget and preserve the visibility constraints already applied to query.
     *
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
    ): Collection;
}
