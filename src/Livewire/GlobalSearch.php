<?php

namespace Aura\Base\Livewire;

use Aura\Base\Contracts\GlobalSearchAdapter;
use Aura\Base\GlobalSearch\GlobalSearchBudget;
use Aura\Base\GlobalSearch\GlobalSearchCandidate;
use Aura\Base\GlobalSearch\GlobalSearchResult;
use Aura\Base\Models\Meta;
use Aura\Base\Resource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Http\Request;
use Illuminate\Routing\Route as RoutingRoute;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\Str;
use Livewire\Attributes\Computed;
use Livewire\Component;
use ReflectionClass;
use ReflectionException;
use ReflectionMethod;
use Stringable;
use Throwable;

class GlobalSearch extends Component
{
    private const DEFAULT_CANDIDATE_LIMIT = 100;

    private const DEFAULT_GLOBAL_LIMIT = 15;

    private const DEFAULT_MAX_FIELDS_PER_RESOURCE = 8;

    private const DEFAULT_MAX_QUERIES_PER_RESOURCE = 4;

    private const DEFAULT_MAX_QUERY_LENGTH = 64;

    private const DEFAULT_MAX_RESOURCE_CANDIDATES = 100;

    private const DEFAULT_MAX_RESOURCES = 25;

    private const DEFAULT_MAX_TITLE_DEPENDENCIES = 4;

    private const DEFAULT_MAX_TOTAL_QUERIES = 100;

    private const DEFAULT_MINIMUM_QUERY_LENGTH = 2;

    private const DEFAULT_PER_RESOURCE_LIMIT = 5;

    private const HARD_MAX_CANDIDATE_LIMIT = 500;

    private const HARD_MAX_FIELDS_PER_RESOURCE = 32;

    private const HARD_MAX_GLOBAL_LIMIT = 100;

    private const HARD_MAX_PER_RESOURCE_LIMIT = 50;

    private const HARD_MAX_QUERIES_PER_RESOURCE = 16;

    private const HARD_MAX_QUERY_LENGTH = 256;

    private const HARD_MAX_RESOURCE_CANDIDATES = 500;

    private const HARD_MAX_RESOURCES = 100;

    private const HARD_MAX_TITLE_DEPENDENCIES = 8;

    private const HARD_MAX_TOTAL_QUERIES = 500;

    public $bookmarks;

    public $search = '';

    #[Computed]
    public function getSearchResultsProperty()
    {
        $searchTerm = $this->normalizedSearchTerm();

        if (Str::length($searchTerm) < $this->configuredLimit(
            'minimum_query_length',
            self::DEFAULT_MINIMUM_QUERY_LENGTH,
            self::HARD_MAX_QUERY_LENGTH,
        )) {
            return collect();
        }

        $user = auth()->user();

        if (! $user instanceof Authenticatable) {
            return collect();
        }

        $globalLimit = $this->configuredLimit(
            'global_limit',
            self::DEFAULT_GLOBAL_LIMIT,
            self::HARD_MAX_GLOBAL_LIMIT,
        );
        $budget = new GlobalSearchBudget(
            $this->configuredLimit(
                'max_total_queries',
                self::DEFAULT_MAX_TOTAL_QUERIES,
                self::HARD_MAX_TOTAL_QUERIES,
            ),
            $this->configuredLimit(
                'max_queries_per_resource',
                self::DEFAULT_MAX_QUERIES_PER_RESOURCE,
                self::HARD_MAX_QUERIES_PER_RESOURCE,
            ),
        );
        $results = collect();

        foreach ($this->searchableResources($user) as $resourceOrder => $resource) {
            if ($budget->exhausted()) {
                break;
            }

            try {
                $results = $results->concat(
                    $this->searchResource($resource, $resourceOrder, $searchTerm, $globalLimit, $user, $budget),
                );
            } catch (Throwable) {
                continue;
            }
        }

        return $results
            ->sort(fn (GlobalSearchResult $left, GlobalSearchResult $right): int => $this->compareResults($left, $right))
            ->take($globalLimit)
            ->groupBy(fn (GlobalSearchResult $result): string => $result->type);
    }

    #[Computed]
    public function maximumQueryLength(): int
    {
        return $this->configuredLimit(
            'maximum_query_length',
            self::DEFAULT_MAX_QUERY_LENGTH,
            self::HARD_MAX_QUERY_LENGTH,
        );
    }

    public function mount()
    {
        if (! config('aura.features.global_search')) {
            abort(403, 'Global search is disabled');
        }

        $this->refreshBookmarks();
    }

    public function render()
    {
        $this->refreshBookmarks();

        return view('aura::livewire.global-search');
    }

    private function allowedDestinationPatterns(): array
    {
        $patterns = config('aura.global_search.allowed_route_names', ['aura.*']);

        if (! is_array($patterns)) {
            return ['aura.*'];
        }

        return collect($patterns)
            ->filter(fn (mixed $pattern): bool => is_string($pattern) && $pattern !== '')
            ->take(20)
            ->values()
            ->all();
    }

    private function compareKeys(mixed $left, mixed $right): int
    {
        if (is_int($left) && is_int($right)) {
            return $left <=> $right;
        }

        return strcmp((string) $left, (string) $right);
    }

    private function compareResults(GlobalSearchResult $left, GlobalSearchResult $right): int
    {
        return ($right->rank <=> $left->rank)
            ?: ($left->resourceOrder <=> $right->resourceOrder)
            ?: $this->compareKeys($left->id, $right->id);
    }

    private function configuredLimit(string $key, int $default, int $hardMaximum): int
    {
        $configured = config("aura.global_search.{$key}", $default);
        $value = is_numeric($configured) ? (int) $configured : $default;

        return min(max($value, 1), $hardMaximum);
    }

    private function destinationFor(Resource $result): ?string
    {
        if ($this->overridesResourceMethod($result, 'globalSearchDestination')) {
            return $this->namedDestination($result->globalSearchDestination());
        }

        if ($this->overridesResourceMethod($result, 'globalSearchUrl')) {
            return $this->validatedLegacyDestination($result->globalSearchUrl());
        }

        return $this->namedDestination($result->globalSearchDestination());
    }

    /**
     * @param  Collection<int, array{candidate: GlobalSearchCandidate, url: string}>  $authorized
     */
    private function hydrateTitleDependencies(
        Resource $resource,
        Collection $authorized,
        GlobalSearchBudget $budget,
    ): bool {
        $dependencies = $resource->globalSearchTitleDependencies();

        if (! is_array($dependencies)) {
            return false;
        }

        $maximumDependencies = $this->configuredLimit(
            'max_title_dependencies',
            self::DEFAULT_MAX_TITLE_DEPENDENCIES,
            self::HARD_MAX_TITLE_DEPENDENCIES,
        );
        $metaFields = $this->validDependencyNames($dependencies['meta'] ?? [], $maximumDependencies);
        $relations = $this->validDependencyNames(
            $dependencies['relations'] ?? [],
            max(0, $maximumDependencies - $metaFields->count()),
        );
        $models = new EloquentCollection(
            $authorized->map(fn (array $entry): Resource => $entry['candidate']->resource)->all(),
        );

        if ($resource->usesMeta()) {
            foreach ($models as $model) {
                $model->setRelation('meta', (new Meta)->newCollection());
            }
        }

        if ($metaFields->isNotEmpty() && ! $this->loadTitleMeta($resource, $models, $metaFields, $budget)) {
            return false;
        }

        foreach ($relations as $relationName) {
            if (! $this->loadTitleRelation($resource, $models, $relationName, $budget)) {
                return false;
            }
        }

        foreach ($models as $model) {
            $model->preventsLazyLoading = true;
        }

        return true;
    }

    private function isAllowedGetRoute(?RoutingRoute $route): bool
    {
        $routeName = $route?->getName();

        return is_string($routeName)
            && Str::is($this->allowedDestinationPatterns(), $routeName)
            && in_array('GET', $route->methods(), true);
    }

    private function isSameOrigin(string $url): bool
    {
        $base = parse_url(url('/'));
        $target = parse_url($url);

        if (! is_array($base) || ! is_array($target)) {
            return false;
        }

        $baseScheme = strtolower((string) ($base['scheme'] ?? ''));
        $targetScheme = strtolower((string) ($target['scheme'] ?? ''));

        if (! in_array($targetScheme, ['http', 'https'], true) || $baseScheme !== $targetScheme) {
            return false;
        }

        return strtolower((string) ($base['host'] ?? '')) === strtolower((string) ($target['host'] ?? ''))
            && $this->urlPort($base) === $this->urlPort($target)
            && ! isset($target['user'])
            && ! isset($target['pass']);
    }

    /**
     * @param  EloquentCollection<int, \Aura\Base\Resource>  $models
     * @param  Collection<int, string>  $metaFields
     */
    private function loadTitleMeta(
        Resource $resource,
        EloquentCollection $models,
        Collection $metaFields,
        GlobalSearchBudget $budget,
    ): bool {
        if (! $resource->usesMeta() || ! $budget->claimQuery($resource)) {
            return false;
        }

        $keys = $models->modelKeys();

        if ($keys === []) {
            return true;
        }

        $maximumRows = max(1, count($keys) * $metaFields->count());
        $prototype = new Meta;
        $prototype->setConnection($resource->getConnectionName());
        $metaRows = $resource->getConnection()
            ->table($resource->getMetaTable())
            ->where('metable_type', $resource->getMorphClass())
            ->whereIn($resource->getMetaForeignKey(), $keys)
            ->whereIn('key', $metaFields)
            ->orderBy($resource->getMetaForeignKey())
            ->orderBy('key')
            ->limit($maximumRows)
            ->get();
        $grouped = $metaRows->groupBy(fn (object $row): string => (string) $row->{$resource->getMetaForeignKey()});

        foreach ($models as $model) {
            $metaModels = $grouped->get((string) $model->getKey(), collect())
                ->map(function (object $row) use ($prototype): Meta {
                    $meta = $prototype->newInstance([], true);
                    $meta->setRawAttributes((array) $row, true);
                    $meta->setConnection($prototype->getConnectionName());

                    return $meta;
                });
            $model->setRelation('meta', $prototype->newCollection($metaModels->all()));
        }

        return true;
    }

    /** @param  EloquentCollection<int, \Aura\Base\Resource>  $models */
    private function loadTitleRelation(
        Resource $resource,
        EloquentCollection $models,
        string $relationName,
        GlobalSearchBudget $budget,
    ): bool {
        $sample = $models->first();

        if (! $sample instanceof Resource || ! method_exists($sample, $relationName)) {
            return false;
        }

        $relation = $sample->{$relationName}();

        if (! $relation instanceof BelongsTo || ! $budget->claimQuery($resource)) {
            return false;
        }

        $models->load($relationName);

        return true;
    }

    private function namedDestination(mixed $destination): ?string
    {
        if (! is_array($destination)
            || ! is_string($destination['route'] ?? null)
            || ! is_array($destination['parameters'] ?? null)
            || count($destination['parameters']) > 10
            || ! Route::has($destination['route'])) {
            return null;
        }

        $route = Route::getRoutes()->getByName($destination['route']);
        $parameterNames = collect($destination['parameters'])->keys();

        if (! $this->isAllowedGetRoute($route)
            || $parameterNames->contains(fn (mixed $name): bool => ! is_string($name))
            || $parameterNames->diff($route->parameterNames())->isNotEmpty()
            || collect($destination['parameters'])->contains(
                fn (mixed $value): bool => (! is_string($value) && ! is_int($value))
                    || (is_string($value) && strlen($value) > 512),
            )) {
            return null;
        }

        try {
            $url = route($destination['route'], $destination['parameters']);
        } catch (Throwable) {
            return null;
        }

        return $this->isSameOrigin($url) ? $url : null;
    }

    private function normalizedSearchTerm(): string
    {
        if (! is_scalar($this->search)) {
            return '';
        }

        return Str::substr(trim((string) $this->search), 0, $this->maximumQueryLength());
    }

    private function overridesResourceMethod(Resource $resource, string $method): bool
    {
        try {
            return (new ReflectionMethod($resource, $method))->getDeclaringClass()->getName() !== Resource::class;
        } catch (ReflectionException) {
            return false;
        }
    }

    private function presentCandidate(
        GlobalSearchCandidate $candidate,
        string $url,
        int $resourceOrder,
    ): ?GlobalSearchResult {
        $resource = $candidate->resource;
        $key = $resource->getKey();

        if (! is_int($key) && ! is_string($key)) {
            return null;
        }

        $title = $resource->title();

        if (! is_string($title) && ! is_int($title) && ! is_float($title) && ! $title instanceof Stringable) {
            return null;
        }

        $title = trim((string) $title);
        $type = $resource->getType();
        $icon = $resource->getIcon();

        if ($title === '' || ! is_string($type) || $type === '' || ! is_string($icon)) {
            return null;
        }

        return new GlobalSearchResult(
            $key,
            $type,
            Str::limit($title, 512, ''),
            $icon,
            $url,
            $candidate->rank,
            $resourceOrder,
        );
    }

    private function refreshBookmarks(): void
    {
        $user = auth()->user();

        $this->bookmarks = $user !== null && method_exists($user, 'getOptionBookmarks')
            ? $user->getOptionBookmarks()
            : [];
    }

    /**
     * @return Collection<int, \Aura\Base\Resource>
     */
    private function searchableResources(Authenticatable $user): Collection
    {
        $maximumResources = $this->configuredLimit(
            'max_resources',
            self::DEFAULT_MAX_RESOURCES,
            self::HARD_MAX_RESOURCES,
        );
        $maximumCandidates = $this->configuredLimit(
            'max_resource_candidates',
            self::DEFAULT_MAX_RESOURCE_CANDIDATES,
            self::HARD_MAX_RESOURCE_CANDIDATES,
        );
        $resources = collect();
        $seen = [];
        $examined = 0;

        foreach (app('aura')::getResources() as $resourceClass) {
            if ($resources->count() >= $maximumResources || $examined >= $maximumCandidates) {
                break;
            }

            $examined++;

            if (! is_string($resourceClass)
                || isset($seen[$resourceClass])
                || ! is_subclass_of($resourceClass, Resource::class)) {
                continue;
            }

            $seen[$resourceClass] = true;

            try {
                if (! (new ReflectionClass($resourceClass))->isInstantiable()
                    || $resourceClass::getGlobalSearch() !== true) {
                    continue;
                }

                $resource = app($resourceClass);

                if (config('aura.teams')
                    && data_get($user, 'current_team_id') === null
                    && $resource->globalSearchAllowsMissingTeamContext($user) !== true) {
                    continue;
                }

                if (! Gate::forUser($user)->allows('viewAny', $resource)) {
                    continue;
                }

                $resources->push($resource);
            } catch (Throwable) {
                continue;
            }
        }

        return $resources->values();
    }

    /**
     * @return Collection<int, GlobalSearchResult>
     */
    private function searchResource(
        Resource $resource,
        int $resourceOrder,
        string $searchTerm,
        int $globalLimit,
        Authenticatable $user,
        GlobalSearchBudget $budget,
    ): Collection {
        $maximumFields = $this->configuredLimit(
            'max_fields_per_resource',
            self::DEFAULT_MAX_FIELDS_PER_RESOURCE,
            self::HARD_MAX_FIELDS_PER_RESOURCE,
        );
        $fields = collect($resource->getGlobalSearchableFields())
            ->filter(fn (mixed $field): bool => is_array($field) && is_string($field['slug'] ?? null))
            ->unique('slug')
            ->take($maximumFields)
            ->values();

        if ($fields->isEmpty()) {
            return collect();
        }

        $query = $resource->newGlobalSearchQuery();

        if (! $query instanceof Builder) {
            return collect();
        }

        $query = $resource->applyGlobalSearchVisibility($query, $user);

        if (! $query instanceof Builder) {
            return collect();
        }

        $adapterClass = $resource->globalSearchAdapter();

        if (! is_string($adapterClass) || ! is_a($adapterClass, GlobalSearchAdapter::class, true)) {
            return collect();
        }

        $adapter = app($adapterClass);

        if (! $adapter instanceof GlobalSearchAdapter) {
            return collect();
        }

        $candidateLimit = $this->configuredLimit(
            'candidate_limit',
            self::DEFAULT_CANDIDATE_LIMIT,
            self::HARD_MAX_CANDIDATE_LIMIT,
        );
        $candidates = $adapter->search(
            $resource,
            $query,
            $fields,
            $searchTerm,
            $candidateLimit,
            $budget,
        )->take($candidateLimit);
        $authorized = collect();

        foreach ($candidates as $candidate) {
            if (! $candidate instanceof GlobalSearchCandidate
                || $resource::class !== $candidate->resource::class
                || $candidate->rank < 1
                || ! Gate::forUser($user)->allows('view', $candidate->resource)) {
                continue;
            }

            $url = $this->destinationFor($candidate->resource);

            if ($url !== null) {
                $authorized->push(['candidate' => $candidate, 'url' => $url]);
            }
        }

        if ($authorized->isEmpty() || ! $this->hydrateTitleDependencies($resource, $authorized, $budget)) {
            return collect();
        }

        $perResourceLimit = min(
            $globalLimit,
            $this->configuredLimit(
                'per_resource_limit',
                self::DEFAULT_PER_RESOURCE_LIMIT,
                self::HARD_MAX_PER_RESOURCE_LIMIT,
            ),
        );

        return $authorized
            ->map(function (array $entry) use ($resourceOrder): ?GlobalSearchResult {
                try {
                    return $this->presentCandidate($entry['candidate'], $entry['url'], $resourceOrder);
                } catch (Throwable) {
                    return null;
                }
            })
            ->filter()
            ->take($perResourceLimit)
            ->values();
    }

    /** @param  array<string, mixed>  $parts */
    private function urlPort(array $parts): int
    {
        if (isset($parts['port'])) {
            return (int) $parts['port'];
        }

        return strtolower((string) ($parts['scheme'] ?? '')) === 'https' ? 443 : 80;
    }

    private function validatedLegacyDestination(mixed $destination): ?string
    {
        if (! is_string($destination)
            || $destination === ''
            || strlen($destination) > 2048
            || preg_match('/[\x00-\x20\x7F\\\\]/', $destination) === 1) {
            return null;
        }

        $isRelative = str_starts_with($destination, '/') && ! str_starts_with($destination, '//');
        $parts = parse_url($destination);

        if (! is_array($parts)
            || isset($parts['query'])
            || isset($parts['fragment'])
            || (! $isRelative && ! $this->isSameOrigin($destination))) {
            return null;
        }

        try {
            $route = Route::getRoutes()->match(Request::create($destination, 'GET'));
        } catch (Throwable) {
            return null;
        }

        if (! $this->isAllowedGetRoute($route)) {
            return null;
        }

        return $this->namedDestination([
            'route' => $route->getName(),
            'parameters' => $route->parameters(),
        ]);
    }

    /** @return Collection<int, non-falsy-string> */
    private function validDependencyNames(mixed $dependencies, int $limit): Collection
    {
        if (! is_array($dependencies) || $limit < 1) {
            return collect();
        }

        return collect($dependencies)
            ->filter(fn (mixed $dependency): bool => is_string($dependency)
                && preg_match('/^[A-Za-z_][A-Za-z0-9_]*$/', $dependency) === 1)
            ->unique()
            ->take($limit)
            ->values();
    }
}
