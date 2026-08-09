<?php

namespace Aura\Base\GlobalSearch;

use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Resource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Support\Facades\Auth;
use Throwable;

final class GlobalSearchWorker extends GlobalSearch
{
    /**
     * @param  array<string, mixed>  $request
     * @return array<string, mixed>
     */
    public function execute(array $request): array
    {
        $operation = $request['operation'] ?? null;

        if (! in_array($operation, ['discover', 'search'], true)) {
            return [];
        }

        app()->instance('aura.global_search.worker_operation', $operation);

        if ($this->allowedDestinationPatterns() === null) {
            return [];
        }

        $user = $this->resolveWorkerUser($request['context'] ?? null);
        $queryLimit = $request['query_limit'] ?? null;

        if (! $user instanceof Authenticatable
            || ! is_int($queryLimit)
            || $queryLimit < 1
            || $queryLimit > 500) {
            return [];
        }

        $queryGuard = new GlobalSearchQueryGuard($queryLimit);
        $queryGuard->install();

        return match ($operation) {
            'discover' => $this->discover($user, $queryGuard),
            'search' => $this->search($request, $user, $queryGuard, $queryLimit),
        };
    }

    /**
     * @return array{
     *     resources: array<int, class-string<resource>>,
     *     query_count: int,
     *     worker_pid: int|false,
     *     contained: bool
     * }
     */
    private function discover(Authenticatable $user, GlobalSearchQueryGuard $queryGuard): array
    {
        return [
            'resources' => $this->searchableResources($user)
                ->map(fn (Resource $resource): string => $resource::class)
                ->values()
                ->all(),
            'query_count' => $queryGuard->queryCount(),
            'worker_pid' => getmypid(),
            'contained' => FreshProcessGlobalSearchExecutor::workerRuntimeIsContained(),
        ];
    }

    private function resolveWorkerUser(mixed $context): ?Authenticatable
    {
        if (! is_array($context)
            || ! is_string($context['guard'] ?? null)
            || $context['guard'] === ''
            || strlen($context['guard']) > 64
            || (! is_int($context['user_id'] ?? null) && ! is_string($context['user_id'] ?? null))
            || (! is_int($context['team_id'] ?? null)
                && ! is_string($context['team_id'] ?? null)
                && ($context['team_id'] ?? null) !== null)) {
            return null;
        }

        try {
            $guard = Auth::guard($context['guard']);
            $provider = $guard->getProvider();
            $user = $provider->retrieveById($context['user_id']);

            if (! $user instanceof Authenticatable
                || ! $guard instanceof StatefulGuard
                || ! $this->sameIdentifier(data_get($user, 'current_team_id'), $context['team_id'])) {
                return null;
            }

            Auth::shouldUse($context['guard']);
            $guard->setUser($user);

            return $user;
        } catch (Throwable) {
            return null;
        }
    }

    private function sameIdentifier(mixed $actual, mixed $expected): bool
    {
        if ($actual === null || $expected === null) {
            return $actual === $expected;
        }

        return (is_int($actual) || is_string($actual))
            && (string) $actual === (string) $expected;
    }

    /**
     * @param  array<string, mixed>  $request
     * @return array{
     *     results: array<int, array<string, int|string>>,
     *     query_count: int,
     *     worker_pid: int|false,
     *     contained: bool
     * }|array{}
     */
    private function search(
        array $request,
        Authenticatable $user,
        GlobalSearchQueryGuard $queryGuard,
        int $queryLimit,
    ): array {
        $resourceClass = $request['resource'] ?? null;
        $resourceOrder = $request['resource_order'] ?? null;
        $searchTerm = $request['search_term'] ?? null;
        $globalLimit = $request['global_limit'] ?? null;
        $executionTimeoutMilliseconds = $request['execution_timeout_ms'] ?? null;

        if (! is_string($resourceClass)
            || ! is_subclass_of($resourceClass, Resource::class)
            || ! in_array($resourceClass, app('aura')::getResources(), true)
            || ! is_int($resourceOrder)
            || $resourceOrder < 0
            || $resourceOrder >= 100
            || ! is_string($searchTerm)
            || $searchTerm === ''
            || strlen($searchTerm) > 1_024
            || ! is_int($globalLimit)
            || $globalLimit < 1
            || $globalLimit > 100
            || ! is_int($executionTimeoutMilliseconds)
            || $executionTimeoutMilliseconds < 1
            || $executionTimeoutMilliseconds > 10_000) {
            return [];
        }

        $resource = $this->resolveSearchableResource($resourceClass, $user);

        if (! $resource instanceof Resource) {
            return [];
        }

        $budget = new GlobalSearchBudget($queryLimit, $queryLimit);
        $results = $this->searchResource(
            $resource,
            $resourceOrder,
            $searchTerm,
            $globalLimit,
            $user,
            $budget,
            $executionTimeoutMilliseconds,
            true,
        );

        return [
            'results' => $results->map(fn (GlobalSearchResult $result): array => [
                'id' => $result->id,
                'type' => $result->type,
                'title' => $result->title,
                'icon' => $result->icon,
                'url' => $result->url,
                'rank' => $result->rank,
            ])->values()->all(),
            'query_count' => $queryGuard->queryCount(),
            'worker_pid' => getmypid(),
            'contained' => FreshProcessGlobalSearchExecutor::workerRuntimeIsContained(),
        ];
    }
}
