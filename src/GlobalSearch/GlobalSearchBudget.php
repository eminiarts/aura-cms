<?php

namespace Aura\Base\GlobalSearch;

use Aura\Base\Resource;

final class GlobalSearchBudget
{
    /** @var array<class-string, int> */
    private array $resourceQueries = [];

    private int $totalQueries = 0;

    public function __construct(
        private readonly int $maximumTotalQueries,
        private readonly int $maximumQueriesPerResource,
    ) {}

    public function claimQuery(Resource $resource): bool
    {
        $resourceClass = $resource::class;
        $resourceQueries = $this->resourceQueries[$resourceClass] ?? 0;

        if ($this->exhausted() || $resourceQueries >= $this->maximumQueriesPerResource) {
            return false;
        }

        $this->totalQueries++;
        $this->resourceQueries[$resourceClass] = $resourceQueries + 1;

        return true;
    }

    public function exhausted(): bool
    {
        return $this->totalQueries >= $this->maximumTotalQueries;
    }

    public function exhaustResource(Resource $resource): void
    {
        $remainingClaims = max(0, $this->maximumQueriesPerResource - $this->queryCountFor($resource));

        for ($claim = 0; $claim < $remainingClaims && ! $this->exhausted(); $claim++) {
            $this->claimQuery($resource);
        }
    }

    public function queryCountFor(Resource $resource): int
    {
        return $this->resourceQueries[$resource::class] ?? 0;
    }

    public function synchronizeResourceQueries(Resource $resource, int $queryCount): void
    {
        $missingClaims = max(0, $queryCount - $this->queryCountFor($resource));

        while ($missingClaims > 0 && $this->claimQuery($resource)) {
            $missingClaims--;
        }
    }
}
