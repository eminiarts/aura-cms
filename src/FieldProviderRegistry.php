<?php

namespace Aura\Base;

use Aura\Base\Contracts\DefinesFields;
use Aura\Base\Contracts\FieldProvider;
use Aura\Base\Exceptions\FieldProviderConflictException;
use Closure;
use InvalidArgumentException;

class FieldProviderRegistry
{
    public const DECLARATIVE_CACHE_KEY = 'declarative';

    /**
     * @var array<string, array{provider: FieldProvider|class-string<FieldProvider>, resources: array<int, class-string<DefinesFields>|string>, mode: FieldProviderMode, priority: int}>
     */
    protected array $baselineProviders = [];

    /**
     * @var array<string, FieldProvider>
     */
    protected array $providerInstances = [];

    /**
     * @var array<string, array{provider: FieldProvider|class-string<FieldProvider>, resources: array<int, class-string<DefinesFields>|string>, mode: FieldProviderMode, priority: int}>
     */
    protected array $providers = [];

    /**
     * @var array<class-string<DefinesFields>, array<string, FieldProviderResolution>>
     */
    protected array $resolvedDefinitions = [];

    /**
     * @var array<string, array{fields: array<array-key, array<string, mixed>>, version: string}>
     */
    protected array $resolvedProviderFields = [];

    public function captureBaselineState(): void
    {
        $this->baselineProviders = $this->providers;
    }

    public function flushResolved(): void
    {
        $this->providerInstances = [];
        $this->resolvedDefinitions = [];
        $this->resolvedProviderFields = [];
    }

    public function flushState(): void
    {
        $this->providers = $this->baselineProviders;
        $this->flushResolved();
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
        $id = is_string($provider) ? $provider : $provider::class;

        if (is_string($provider) && ! is_a($provider, FieldProvider::class, true)) {
            throw new InvalidArgumentException("Field provider [{$id}] must implement ".FieldProvider::class.'.');
        }

        if (isset($this->providers[$id])) {
            throw new InvalidArgumentException("Field provider [{$id}] is already registered.");
        }

        if ($resources === [] || collect($resources)->contains(fn ($resource) => ! is_string($resource))) {
            throw new InvalidArgumentException('Field provider resources must be a non-empty array of resource class names or [*].');
        }

        $this->providers[$id] = [
            'provider' => $provider,
            'resources' => array_values(array_unique($resources)),
            'mode' => $mode,
            'priority' => $priority,
        ];

        $this->flushResolved();
        Resource::flushFieldCache();
        BaseResource::flushFieldCache();
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
                    return $target === '*' || is_a($resourceClass, $target, true);
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
                $provider = $this->providerInstance($id, $registration['provider']);
                $context = new FieldProviderContext($resourceClass, $provider->cacheContext($resourceClass));
                $providerCacheKey = $id.'|'.$context->fingerprint();

                if (! isset($this->resolvedProviderFields[$providerCacheKey])) {
                    $version = (string) $provider->cacheVersion($context);
                    $fields = array_map(
                        fn (array $field): array => $this->normalizeField($field, $id),
                        $provider->fields($context),
                    );

                    $this->resolvedProviderFields[$providerCacheKey] = [
                        'fields' => $fields,
                        'version' => $version,
                    ];
                }

                return [
                    'id' => $id,
                    'context' => $context->fingerprint(),
                    'fields' => $this->resolvedProviderFields[$providerCacheKey]['fields'],
                    'mode' => $registration['mode'],
                    'priority' => $registration['priority'],
                    'version' => $this->resolvedProviderFields[$providerCacheKey]['version'],
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

    /**
     * @param  FieldProvider|class-string<FieldProvider>  $provider
     */
    protected function providerInstance(string $id, FieldProvider|string $provider): FieldProvider
    {
        if (! isset($this->providerInstances[$id])) {
            $instance = is_string($provider) ? app($provider) : $provider;

            if (! $instance instanceof FieldProvider) {
                throw new InvalidArgumentException("Field provider [{$id}] must implement ".FieldProvider::class.'.');
            }

            $this->providerInstances[$id] = $instance;
        }

        return $this->providerInstances[$id];
    }
}
