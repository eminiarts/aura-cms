<?php

namespace Aura\Base\Services;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use JsonException;

final class EmbeddedComponentContextStore
{
    /** @var array<string, Model> */
    private array $canonicalResources = [];

    /** @var array<string, true> */
    private array $missingResources = [];

    /** @var array<string, Model> */
    private array $signatureResources = [];

    public function __construct(
        private readonly EmbeddedResourceIncarnationStore $incarnations,
        private readonly EmbeddedResourceIncarnationGuard $guard,
    ) {}

    public function canonical(Model $resource): ?Model
    {
        if (! $resource->exists && ! $resource->wasRecentlyCreated) {
            return $resource;
        }

        $key = $resource->getKey();

        if (! is_int($key) && ! is_string($key)) {
            return null;
        }

        return $this->canonicalIdentity($resource::class, $key);
    }

    /**
     * @param  class-string<Model>  $resourceClass
     */
    public function canonicalIdentity(string $resourceClass, int|string $resourceKey): ?Model
    {
        $identity = $this->identity($resourceClass, $resourceKey);

        if (isset($this->canonicalResources[$identity])) {
            return $this->canonicalResources[$identity];
        }

        if (isset($this->missingResources[$identity])) {
            return null;
        }

        $this->primeIdentities([[
            'resource_class' => $resourceClass,
            'resource_key' => $resourceKey,
        ]]);

        return $this->canonicalResources[$identity] ?? null;
    }

    public function find(string $signature): ?Model
    {
        return $this->signatureResources[$signature] ?? null;
    }

    public function flushIncarnations(): void
    {
        $this->incarnations->flush();
    }

    public function forgetCanonical(Model $resource): void
    {
        $key = $resource->getKey();

        if (! is_int($key) && ! is_string($key)) {
            return;
        }

        $identity = $this->identity($resource::class, $key);
        unset($this->canonicalResources[$identity], $this->missingResources[$identity]);
    }

    public function physicallyExists(Model $resource, int|string $resourceKey): bool
    {
        return $resource->getConnection()
            ->table($resource->getTable())
            ->useWritePdo()
            ->where($resource->getKeyName(), $resourceKey)
            ->exists();
    }

    /**
     * @param  iterable<int, Model>  $resources
     */
    public function prime(iterable $resources): void
    {
        $identities = [];

        foreach ($resources as $resource) {
            if (! $resource->exists && ! $resource->wasRecentlyCreated) {
                continue;
            }

            $key = $resource->getKey();

            if (! is_int($key) && ! is_string($key)) {
                continue;
            }

            $identities[] = [
                'resource_class' => $resource::class,
                'resource_key' => $key,
            ];
        }

        $this->primeIdentities($identities);
    }

    /**
     * @param  array<int, array{resource_class: class-string<Model>, resource_key: int|string}>  $identities
     */
    public function primeIdentities(array $identities): void
    {
        $pending = [];

        foreach ($identities as $identity) {
            $cacheKey = $this->identity($identity['resource_class'], $identity['resource_key']);

            if (isset($this->canonicalResources[$cacheKey])
                || isset($this->missingResources[$cacheKey])
            ) {
                continue;
            }

            $pending[$identity['resource_class']][] = $identity['resource_key'];
        }

        foreach ($pending as $resourceClass => $resourceKeys) {
            $resourceKeys = array_values(array_unique($resourceKeys, SORT_REGULAR));

            /** @var Model $prototype */
            $prototype = new $resourceClass;
            $connection = $prototype->getConnection();
            $this->guard->assertInstalled($prototype);
            $load = function () use ($connection, $prototype, $resourceKeys): void {
                if ($connection->getDriverName() === 'sqlite') {
                    $this->acquireSqliteWriteLock($connection);
                }

                $query = $prototype->newQuery()->whereKey($resourceKeys)->applyScopes();
                $query->withoutGlobalScopes();
                $query->withoutEagerLoads();
                $query->select($prototype->qualifyColumn('*'));

                if ($connection->getDriverName() !== 'sqlite') {
                    $query->lockForUpdate();
                }

                $loaded = [];

                foreach ($query->get() as $resource) {
                    $key = $resource->getKey();

                    if (! is_int($key) && ! is_string($key)) {
                        continue;
                    }

                    $cacheKey = $this->identity($resource::class, $key);
                    $this->canonicalResources[$cacheKey] = $resource;
                    $loaded[] = $resource;
                }

                $this->incarnations->prime($loaded);
            };

            if ($connection->transactionLevel() > 0) {
                $load();
            } else {
                $connection->transaction($load, 3);
            }

            foreach ($resourceKeys as $resourceKey) {
                $cacheKey = $this->identity($resourceClass, $resourceKey);

                if (! isset($this->canonicalResources[$cacheKey])) {
                    $this->missingResources[$cacheKey] = true;
                }
            }
        }

    }

    public function remember(string $signature, Model $resource): void
    {
        $this->signatureResources[$signature] = $resource;
    }

    public function token(Model $resource): string
    {
        return $this->incarnations->token($resource);
    }

    public function version(Model $resource): int
    {
        return $this->incarnations->version($resource);
    }

    private function acquireSqliteWriteLock(Connection $connection): void
    {
        $grammar = $connection->getQueryGrammar();
        $version = $grammar->wrap('version');

        $connection->statement(sprintf(
            'update %s set %s = %s where 1 = 0',
            $grammar->wrapTable(EmbeddedResourceIncarnationStore::TABLE),
            $version,
            $version,
        ));
    }

    /**
     * @param  class-string<Model>  $resourceClass
     */
    private function identity(string $resourceClass, int|string $resourceKey): string
    {
        try {
            $encodedKey = json_encode(
                [get_debug_type($resourceKey), $resourceKey],
                JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION,
            );
        } catch (JsonException) {
            $encodedKey = get_debug_type($resourceKey).':'.(string) $resourceKey;
        }

        return $resourceClass.'|'.hash('sha256', $encodedKey);
    }
}
