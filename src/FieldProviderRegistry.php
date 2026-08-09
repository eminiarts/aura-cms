<?php

namespace Aura\Base;

use Aura\Base\Contracts\ContextualFieldProvider;
use Aura\Base\Contracts\DefinesFields;
use Aura\Base\Contracts\FieldProvider;
use Aura\Base\Exceptions\FieldProviderConflictException;
use Closure;
use InvalidArgumentException;
use ReflectionClass;

class FieldProviderRegistry
{
    public const DECLARATIVE_CACHE_KEY = 'declarative';

    /**
     * @var array<string, array{provider: class-string<FieldProvider>, resources: array<int, class-string<DefinesFields>|string>, mode: FieldProviderMode, priority: int}>
     */
    protected array $baselineProviders = [];

    /**
     * @var array<string, array<int, string>>
     */
    protected array $providerManagedFieldSlugs = [];

    /**
     * @var array<string, array{provider: class-string<FieldProvider>, resources: array<int, class-string<DefinesFields>|string>, mode: FieldProviderMode, priority: int}>
     */
    protected array $providers = [];

    /**
     * @var array<string, string>
     */
    protected array $providerVersions = [];

    /**
     * @var array<class-string<DefinesFields>, array<string, FieldProviderResolution>>
     */
    protected array $resolvedDefinitions = [];

    /**
     * @var array<string, array<array-key, array<string, mixed>>>
     */
    protected array $resolvedProviderFields = [];

    public function captureBaselineState(): void
    {
        $this->baselineProviders = $this->providers;
    }

    public function flushResolved(): void
    {
        $this->providerManagedFieldSlugs = [];
        $this->providerVersions = [];
        $this->resolvedDefinitions = [];
        $this->resolvedProviderFields = [];
    }

    public function flushState(): void
    {
        $this->providers = $this->baselineProviders;
        $this->flushResolved();
    }

    /**
     * Forget version snapshots while retaining immutable, version-keyed field
     * output. Providers whose version did not change avoid another fields()
     * query; providers with a new version resolve a new output entry.
     */
    public function refreshVersions(): void
    {
        $this->providerManagedFieldSlugs = [];
        $this->providerVersions = [];
        $this->resolvedDefinitions = [];
    }

    /**
     * @param  FieldProvider|class-string<FieldProvider>  $provider
     * @param  array<int, class-string<DefinesFields>|string>  $resources
     */
    public function register(
        FieldProvider|string $provider,
        array $resources = ['*'],
        FieldProviderMode $mode = FieldProviderMode::Append,
        int $priority = 0,
    ): void {
        if ($provider instanceof FieldProvider) {
            throw new InvalidArgumentException('Field providers must be registered by class name so workers can build a fresh provider instance.');
        }

        $id = $provider;

        if (! is_a($provider, FieldProvider::class, true)) {
            throw new InvalidArgumentException("Field provider [{$id}] must implement ".FieldProvider::class.'.');
        }

        if (! (new ReflectionClass($provider))->isInstantiable()) {
            throw new InvalidArgumentException("Field provider [{$id}] must be an instantiable class name.");
        }

        if (isset($this->providers[$id])) {
            throw new InvalidArgumentException("Field provider [{$id}] is already registered.");
        }

        if ($resources === [] || collect($resources)->contains(fn ($resource) => ! $this->isValidResourceTarget($resource))) {
            throw new InvalidArgumentException('Field provider resources must be a non-empty array of Aura resource class names or [*].');
        }

        if (is_a($provider, ContextualFieldProvider::class, true)) {
            if (in_array('*', $resources, true)) {
                throw new InvalidArgumentException('Contextual field providers cannot use the wildcard target; explicitly target Aura\\Base\\Resource subclasses.');
            }

            $baseResourceTarget = collect($resources)->first(
                fn (string $resource): bool => is_a($resource, BaseResource::class, true)
                    && ! is_a($resource, Resource::class, true),
            );

            if ($baseResourceTarget !== null) {
                throw new InvalidArgumentException('Contextual field providers cannot target '.BaseResource::class.' subclasses because they do not provide contextual state isolation.');
            }
        }

        $this->providers[$id] = [
            'provider' => $provider,
            'resources' => array_values(array_unique($resources)),
            'mode' => $mode,
            'priority' => $priority,
        ];

        $this->flushResolved();
        FieldCacheManager::flush(flushProviderResults: false);
    }

    /**
     * @param  class-string<DefinesFields>  $resourceClass
     * @param  Closure(): array<array-key, array<string, mixed>>  $declarativeFields
     */
    public function resolve(string $resourceClass, Closure $declarativeFields): FieldProviderResolution
    {
        $providers = collect($this->providers)
            ->filter(function (array $registration) use ($resourceClass): bool {
                return collect($registration['resources'])->contains(function (string $target) use ($resourceClass): bool {
                    if ($target === '*') {
                        return $this->isAuraResource($resourceClass);
                    }

                    return is_a($resourceClass, $target, true);
                });
            })
            ->map(fn (array $registration, string $id): array => [
                'id' => $id,
                ...$registration,
            ])
            ->sortBy([
                ['priority', 'asc'],
                ['id', 'asc'],
            ])
            ->values()
            ->map(function (array $registration) use ($resourceClass): array {
                $id = $registration['id'];
                $provider = $this->buildProvider($registration['provider']);
                $contextValues = $provider->cacheContext($resourceClass);

                if ($contextValues !== [] && ! $provider instanceof ContextualFieldProvider) {
                    throw new InvalidArgumentException("Context-dependent field provider [{$id}] must implement ".ContextualFieldProvider::class.' and declare its complete managed field slug manifest.');
                }

                $context = new FieldProviderContext($resourceClass, $contextValues);
                $contextFingerprint = $context->fingerprint();
                $versionCacheKey = $this->cacheKey($id, $contextFingerprint);

                if (! array_key_exists($versionCacheKey, $this->providerVersions)) {
                    $this->providerVersions[$versionCacheKey] = serialize($provider->cacheVersion($context));
                }

                $serializedVersion = $this->providerVersions[$versionCacheKey];
                $providerFieldsCacheKey = $this->cacheKey($id, $contextFingerprint, $serializedVersion);

                if (! array_key_exists($providerFieldsCacheKey, $this->resolvedProviderFields)) {
                    $this->resolvedProviderFields[$providerFieldsCacheKey] = array_map(
                        fn (array $field): array => $this->normalizeField($field, $id),
                        $provider->fields($context),
                    );
                }

                $managedFieldSlugs = $provider instanceof ContextualFieldProvider
                    ? $this->managedFieldSlugs($id, $resourceClass, $provider)
                    : array_values(array_unique(array_column(
                        $this->resolvedProviderFields[$providerFieldsCacheKey],
                        'slug',
                    )));
                $undeclaredFieldSlugs = array_values(array_diff(
                    array_column($this->resolvedProviderFields[$providerFieldsCacheKey], 'slug'),
                    $managedFieldSlugs,
                ));

                if ($undeclaredFieldSlugs !== []) {
                    throw new InvalidArgumentException("Context-dependent field provider [{$id}] returned undeclared field slug [{$undeclaredFieldSlugs[0]}].");
                }

                return [
                    'id' => $id,
                    'context' => $contextFingerprint,
                    'fields' => $this->resolvedProviderFields[$providerFieldsCacheKey],
                    'mode' => $registration['mode'],
                    'managedFieldSlugs' => $managedFieldSlugs,
                    'priority' => $registration['priority'],
                    'version' => $serializedVersion,
                ];
            })
            ->all();

        $cacheKey = $providers === []
            ? self::DECLARATIVE_CACHE_KEY
            : hash('sha256', serialize(collect($providers)->map(fn (array $provider): array => [
                'id' => $provider['id'],
                'context' => $provider['context'],
                'mode' => $provider['mode']->value,
                'priority' => $provider['priority'],
                'version' => $provider['version'],
            ])->all()));

        if (! isset($this->resolvedDefinitions[$resourceClass][$cacheKey])) {
            $fields = $declarativeFields();

            $this->resolvedDefinitions[$resourceClass][$cacheKey] = new FieldProviderResolution(
                $cacheKey,
                $this->applyProviders($fields, $providers),
                array_values(array_unique(array_merge(...array_map(
                    fn (array $provider): array => $provider['managedFieldSlugs'],
                    $providers,
                )))),
            );
        }

        return $this->resolvedDefinitions[$resourceClass][$cacheKey];
    }

    /**
     * @param  array<array-key, array<string, mixed>>  $fields
     * @param  array<int, array{id: string, fields: array<array-key, array<string, mixed>>, mode: FieldProviderMode, priority: int}>  $providers
     * @return array<int, array<string, mixed>>
     */
    protected function applyProviders(array $fields, array $providers): array
    {
        $fields = array_values($fields);
        $fieldIndexes = [];
        $replacementOwners = [];

        foreach ($fields as $index => $field) {
            if (isset($field['slug']) && is_string($field['slug'])) {
                $fieldIndexes[$field['slug']] = $index;
            }
        }

        foreach ($providers as $provider) {
            foreach ($provider['fields'] as $field) {
                $slug = $field['slug'];

                if ($provider['mode'] === FieldProviderMode::Append) {
                    if (array_key_exists($slug, $fieldIndexes)) {
                        throw new FieldProviderConflictException("Field provider [{$provider['id']}] cannot append duplicate field [{$slug}].");
                    }

                    $fieldIndexes[$slug] = count($fields);
                    $fields[] = $field;

                    continue;
                }

                if (! array_key_exists($slug, $fieldIndexes)) {
                    throw new FieldProviderConflictException("Field provider [{$provider['id']}] cannot replace missing field [{$slug}].");
                }

                if (isset($replacementOwners[$slug]) && $replacementOwners[$slug]['priority'] === $provider['priority']) {
                    $previousProvider = $replacementOwners[$slug]['id'];

                    throw new FieldProviderConflictException("Field providers [{$previousProvider}] and [{$provider['id']}] both replace [{$slug}] at priority [{$provider['priority']}].");
                }

                $fields[$fieldIndexes[$slug]] = $field;
                $replacementOwners[$slug] = [
                    'id' => $provider['id'],
                    'priority' => $provider['priority'],
                ];
            }
        }

        return $fields;
    }

    /**
     * Build the top-level provider directly so a host application's singleton
     * binding cannot leak mutable provider state between worker lifecycles.
     * Constructor dependencies still resolve through Laravel's container.
     *
     * @param  class-string<FieldProvider>  $provider
     */
    protected function buildProvider(string $provider): FieldProvider
    {
        $instance = app()->build($provider);

        if (! $instance instanceof FieldProvider) {
            throw new InvalidArgumentException("Field provider [{$provider}] must implement ".FieldProvider::class.'.');
        }

        return $instance;
    }

    protected function cacheKey(string ...$parts): string
    {
        return hash('sha256', serialize($parts));
    }

    protected function isAuraResource(string $resourceClass): bool
    {
        return is_a($resourceClass, Resource::class, true)
            || is_a($resourceClass, BaseResource::class, true);
    }

    protected function isValidResourceTarget(mixed $target): bool
    {
        if (! is_string($target)) {
            return false;
        }

        if ($target === '*') {
            return true;
        }

        return $this->isAuraResource($target);
    }

    /**
     * @param  class-string<DefinesFields>  $resourceClass
     * @return array<int, string>
     */
    protected function managedFieldSlugs(
        string $providerId,
        string $resourceClass,
        ContextualFieldProvider $provider,
    ): array {
        $cacheKey = $this->cacheKey($providerId, $resourceClass);

        if (! array_key_exists($cacheKey, $this->providerManagedFieldSlugs)) {
            $managedFieldSlugs = $provider->managedFieldSlugs($resourceClass);

            foreach ($managedFieldSlugs as $slug) {
                if (! is_string($slug) || $slug === '') {
                    throw new InvalidArgumentException("Context-dependent field provider [{$providerId}] returned an invalid managed field slug manifest.");
                }
            }

            $this->providerManagedFieldSlugs[$cacheKey] = array_values(array_unique($managedFieldSlugs));
        }

        return $this->providerManagedFieldSlugs[$cacheKey];
    }

    /**
     * @param  array<string, mixed>  $field
     * @return array<string, mixed>
     */
    protected function normalizeField(array $field, string $provider): array
    {
        $slug = $field['slug'] ?? null;

        if (! is_string($slug) || $slug === '') {
            throw new InvalidArgumentException("Field provider [{$provider}] returned a field without a non-empty string slug.");
        }

        return $field;
    }
}
