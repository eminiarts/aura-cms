<?php

namespace Aura\Base\Livewire\Media;

use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Cache\Store;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\MariaDbConnection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\SqlServerConnection;
use Illuminate\Filesystem\Filesystem;
use InvalidArgumentException;
use JsonException;
use ReflectionProperty;
use Throwable;

class MediaSecurityStore implements LockProvider
{
    public readonly MediaSecurityStore $cache;

    public readonly LockProvider $locks;

    private readonly string $configurationFingerprint;

    private readonly CacheRepository $repository;

    private readonly Store&LockProvider $resolvedStore;

    private readonly string $storeName;

    public function __construct(CacheFactory $cache, private readonly ConfigRepository $config)
    {
        $storeName = $config->get('aura.media.security.cache_store');

        if (! is_string($storeName) || $storeName === '') {
            throw new InvalidArgumentException(
                'Aura media security requires an explicitly named dedicated cache store.',
            );
        }

        if ($storeName === $config->get('cache.default')) {
            throw new InvalidArgumentException(
                'Aura media security cache_store must name a dedicated store that is not Laravel\'s default cache store.',
            );
        }

        if ($config->get('cache.serializable_classes') !== false) {
            throw new InvalidArgumentException(
                'Aura media security requires cache.serializable_classes=false so cache reads cannot instantiate PHP objects.',
            );
        }

        $repository = $cache->store($storeName);
        $store = $repository instanceof CacheRepository ? $repository->getStore() : null;

        if (! $repository instanceof CacheRepository
            || get_class($repository) !== CacheRepository::class
            || ! $store instanceof Store
            || ! $store instanceof LockProvider
            || ! in_array(get_class($store), [
                FileStore::class,
                DatabaseStore::class,
            ], true)) {
            throw new InvalidArgumentException(
                'Aura media security requires an exact Laravel file or database cache store with atomic locks; Redis, DynamoDB, Memcached, failover, process-local, custom, and proxied stores are rejected because their pre-read boundary is not proven.',
            );
        }

        $this->storeName = $storeName;
        $this->repository = $repository;
        $this->resolvedStore = $store;
        $this->configurationFingerprint = $this->currentConfigurationFingerprint();
        $this->cache = $this;
        $this->locks = $this;
        $this->assertSafeBoundary();
    }

    public function add(string $key, mixed $value, int $seconds): bool
    {
        $this->assertSafeBoundary();

        return $this->repository->add($key, $value, $seconds);
    }

    public function forget(string $key): bool
    {
        $this->assertSafeBoundary();

        return $this->repository->forget($key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertSafeBoundary();

        return $this->repository->get($key, $default);
    }

    public function lock($name, $seconds = 0, $owner = null)
    {
        $this->assertSafeBoundary();

        return new MediaSecurityLock(
            $this->resolvedStore->lock($name, $seconds, $owner),
            fn () => $this->assertSafeBoundary(),
        );
    }

    public function put(string $key, mixed $value, int $seconds): bool
    {
        $this->assertSafeBoundary();

        return $this->repository->put($key, $value, $seconds);
    }

    public function restoreLock($name, $owner)
    {
        $this->assertSafeBoundary();

        return new MediaSecurityLock(
            $this->resolvedStore->restoreLock($name, $owner),
            fn () => $this->assertSafeBoundary(),
        );
    }

    private function assertDatabaseConfiguration(DatabaseStore $store, array $storeConfig): void
    {
        $connection = $storeConfig['connection'] ?? $this->config->get('database.default');
        $lockConnection = $storeConfig['lock_connection'] ?? $connection;

        if ($this->property($store, 'table') !== ($storeConfig['table'] ?? null)
            || $this->property($store, 'lockTable') !== ($storeConfig['lock_table'] ?? 'cache_locks')
            || $this->property($store, 'lockLottery') !== ($storeConfig['lock_lottery'] ?? [2, 100])
            || $this->property($store, 'defaultLockTimeoutInSeconds') !== ($storeConfig['lock_timeout'] ?? 86400)
            || $store->getPrefix() !== ($storeConfig['prefix'] ?? $this->config->get('cache.prefix'))
            || ! method_exists($store->getConnection(), 'getName')
            || $store->getConnection()->getName() !== $connection
            || ! method_exists($store->getLockConnection(), 'getName')
            || $store->getLockConnection()->getName() !== $lockConnection) {
            $this->rejectConfigurationMutation();
        }

        foreach ([$store->getConnection(), $store->getLockConnection()] as $resolvedConnection) {
            if (! in_array(get_class($resolvedConnection), [
                MariaDbConnection::class,
                MySqlConnection::class,
                PostgresConnection::class,
                SQLiteConnection::class,
                SqlServerConnection::class,
            ], true)) {
                $this->rejectConfigurationMutation();
            }
        }
    }

    private function assertFileConfiguration(FileStore $store, array $storeConfig): void
    {
        if ($store->getDirectory() !== ($storeConfig['path'] ?? null)
            || $this->property($store, 'lockDirectory') !== ($storeConfig['lock_path'] ?? null)
            || $this->property($store, 'filePermission') !== ($storeConfig['permission'] ?? null)
            || get_class($this->property($store, 'files')) !== Filesystem::class) {
            $this->rejectConfigurationMutation();
        }
    }

    private function assertSafeBoundary(): void
    {
        if ($this->config->get('aura.media.security.cache_store') !== $this->storeName
            || $this->config->get('cache.default') === $this->storeName
            || $this->config->get('cache.serializable_classes') !== false
            || get_class($this->repository) !== CacheRepository::class
            || $this->repository->getStore() !== $this->resolvedStore
            || $this->property($this->resolvedStore, 'serializableClasses') !== false
            || ! hash_equals($this->configurationFingerprint, $this->currentConfigurationFingerprint())) {
            $this->rejectConfigurationMutation();
        }

        $storeConfig = $this->storeConfiguration();

        match (get_class($this->resolvedStore)) {
            FileStore::class => $this->assertFileConfiguration($this->resolvedStore, $storeConfig),
            DatabaseStore::class => $this->assertDatabaseConfiguration($this->resolvedStore, $storeConfig),
            default => $this->rejectConfigurationMutation(),
        };
    }

    private function currentConfigurationFingerprint(): string
    {
        try {
            $configuration = [
                'security_store' => $this->config->get('aura.media.security.cache_store'),
                'default_store' => $this->config->get('cache.default'),
                'serializable_classes' => $this->config->get('cache.serializable_classes'),
                'store' => $this->config->get('cache.stores.'.$this->storeName),
            ];

            if (is_array($configuration['store']) && ($configuration['store']['driver'] ?? null) === 'database') {
                $connection = $configuration['store']['connection'] ?? $this->config->get('database.default');
                $lockConnection = $configuration['store']['lock_connection'] ?? $connection;
                $configuration['database'] = [
                    'default' => $this->config->get('database.default'),
                    'connection' => $this->config->get('database.connections.'.$connection),
                    'lock_connection' => $this->config->get('database.connections.'.$lockConnection),
                ];
            }

            return hash('sha256', json_encode($configuration, JSON_THROW_ON_ERROR));
        } catch (JsonException $exception) {
            throw new InvalidArgumentException(
                'Aura media security cache configuration must contain only JSON-safe values.',
                previous: $exception,
            );
        }
    }

    private function property(object $object, string $property): mixed
    {
        try {
            return (new ReflectionProperty($object, $property))->getValue($object);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                'Aura media security could not verify the resolved cache store configuration.',
                previous: $exception,
            );
        }
    }

    private function rejectConfigurationMutation(): never
    {
        throw new InvalidArgumentException(
            'Aura media security cache configuration changed or no longer guarantees object-free reads.',
        );
    }

    /** @return array<string, mixed> */
    private function storeConfiguration(): array
    {
        $storeConfig = $this->config->get('cache.stores.'.$this->storeName);

        if (! is_array($storeConfig)) {
            $this->rejectConfigurationMutation();
        }

        $expectedDriver = match (get_class($this->resolvedStore)) {
            FileStore::class => 'file',
            DatabaseStore::class => 'database',
            default => null,
        };

        if (($storeConfig['driver'] ?? null) !== $expectedDriver) {
            $this->rejectConfigurationMutation();
        }

        return $storeConfig;
    }
}
