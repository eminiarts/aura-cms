<?php

namespace Aura\Base\Livewire\Media;

use Illuminate\Cache\CacheManager;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\FailoverStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Config\Repository as ConfigurationRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Database\Connection;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\MariaDbConnection;
use Illuminate\Database\MySqlConnection;
use Illuminate\Database\PostgresConnection;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Database\SqlServerConnection;
use Illuminate\Foundation\Application;
use InvalidArgumentException;
use JsonException;
use PDO;
use ReflectionProperty;
use Throwable;

class MediaSecurityStore implements LockProvider
{
    public readonly MediaSecurityStore $cache;

    public readonly LockProvider $locks;

    private readonly CacheManager $cacheManager;

    private readonly string $configurationFingerprint;

    private readonly DatabaseManager $databaseManager;

    private readonly string $dataDatabaseIdentity;

    private readonly Connection $dataOperationConnection;

    private readonly PDO $dataPdo;

    private readonly string $dataTableIdentity;

    private readonly string $lockDatabaseIdentity;

    private readonly Connection $lockOperationConnection;

    private readonly PDO $lockPdo;

    private readonly string $lockTableIdentity;

    private readonly CacheRepository $operationRepository;

    private readonly DatabaseStore $operationStore;

    private readonly CacheRepository $repository;

    private readonly DatabaseStore $resolvedStore;

    private readonly string $storeName;

    public function __construct(
        CacheFactory $cache,
        private readonly ConfigRepository $config,
        DatabaseManager $database,
        private readonly Application $application,
    ) {
        if (get_class($cache) !== CacheManager::class
            || get_class($database) !== DatabaseManager::class
            || get_class($config) !== ConfigurationRepository::class
            || $application->make('cache') !== $cache
            || $application->make('db') !== $database
            || $application->make('config') !== $config
            || get_class($application->make('db.factory')) !== ConnectionFactory::class
            || $this->property($cache, 'app') !== $application
            || $this->property($database, 'app') !== $application
            || $this->property($database, 'factory') !== $application->make('db.factory')
            || array_key_exists('database', $this->property($cache, 'customCreators'))) {
            throw new InvalidArgumentException(
                'Aura media security requires Laravel\'s exact application CacheManager and DatabaseManager instances.',
            );
        }

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
            || ! $store instanceof DatabaseStore
            || get_class($store) !== DatabaseStore::class) {
            throw new InvalidArgumentException(
                'Aura media security requires an exact Laravel database cache store; file, Redis, DynamoDB, Memcached, failover, process-local, custom, and proxied stores are rejected because their pre-read boundary is not proven. Subclasses are also rejected.',
            );
        }

        $this->cacheManager = $cache;
        $this->databaseManager = $database;
        $this->storeName = $storeName;
        $this->repository = $repository;
        $this->resolvedStore = $store;
        $this->configurationFingerprint = $this->currentConfigurationFingerprint();

        $storeConfig = $this->storeConfiguration();
        $dataConnection = $store->getConnection();
        $lockConnection = $store->getLockConnection();
        $this->dataDatabaseIdentity = $this->databaseIdentity(
            $dataConnection,
            $this->connectionName($storeConfig, 'connection'),
        );
        $this->lockDatabaseIdentity = $this->databaseIdentity(
            $lockConnection,
            $this->connectionName($storeConfig, 'lock_connection'),
        );
        $this->dataPdo = $this->stableWritePdo($dataConnection);
        $this->lockPdo = $this->stableWritePdo($lockConnection);
        $this->dataOperationConnection = $this->pinnedConnection($dataConnection, $this->dataPdo);
        $this->lockOperationConnection = $lockConnection === $dataConnection
            ? $this->dataOperationConnection
            : $this->pinnedConnection($lockConnection, $this->lockPdo);
        $this->operationStore = clone $store;
        $this->operationStore->setConnection($this->dataOperationConnection);
        $this->operationStore->setLockConnection($this->lockOperationConnection);
        $this->operationRepository = new CacheRepository($this->operationStore);
        $this->dataTableIdentity = $this->tableIdentity(
            $dataConnection,
            $this->dataDatabaseIdentity,
            (string) $this->property($store, 'table'),
        );
        $this->lockTableIdentity = $this->tableIdentity(
            $lockConnection,
            $this->lockDatabaseIdentity,
            (string) $this->property($store, 'lockTable'),
        );
        $this->cache = $this;
        $this->locks = $this;
        $this->assertSafeBoundary();
    }

    public function add(string $key, mixed $value, int $seconds): bool
    {
        $this->assertSafeBoundary();

        return $this->operationRepository->add($key, $value, $seconds);
    }

    public function forget(string $key): bool
    {
        $this->assertSafeBoundary();

        return $this->operationRepository->forget($key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertSafeBoundary();

        return $this->operationRepository->get($key, $default);
    }

    public function lock($name, $seconds = 0, $owner = null)
    {
        $this->assertSafeBoundary();

        return new MediaSecurityLock(
            $this->operationStore->lock($name, $seconds, $owner),
            fn () => $this->assertSafeBoundary(),
        );
    }

    public function put(string $key, mixed $value, int $seconds): bool
    {
        $this->assertSafeBoundary();

        return $this->operationRepository->put($key, $value, $seconds);
    }

    public function restoreLock($name, $owner)
    {
        $this->assertSafeBoundary();

        return new MediaSecurityLock(
            $this->operationStore->restoreLock($name, $owner),
            fn () => $this->assertSafeBoundary(),
        );
    }

    /** @param array<string, mixed> $storeConfig */
    private function assertDatabaseConfiguration(array $storeConfig): void
    {
        $connectionName = $this->connectionName($storeConfig, 'connection');
        $lockConnectionName = $this->connectionName($storeConfig, 'lock_connection');
        $connection = $this->resolvedStore->getConnection();
        $lockConnection = $this->resolvedStore->getLockConnection();

        if ($this->property($this->resolvedStore, 'table') !== ($storeConfig['table'] ?? null)
            || $this->property($this->resolvedStore, 'lockTable') !== ($storeConfig['lock_table'] ?? 'cache_locks')
            || $this->property($this->resolvedStore, 'lockLottery') !== ($storeConfig['lock_lottery'] ?? [2, 100])
            || $this->property($this->resolvedStore, 'defaultLockTimeoutInSeconds') !== ($storeConfig['lock_timeout'] ?? 86400)
            || $this->resolvedStore->getPrefix() !== ($storeConfig['prefix'] ?? $this->config->get('cache.prefix'))
            || $connection !== $this->databaseManager->connection($connectionName)
            || $lockConnection !== $this->databaseManager->connection($lockConnectionName)
            || $this->databaseIdentity($connection, $connectionName) !== $this->dataDatabaseIdentity
            || $this->databaseIdentity($lockConnection, $lockConnectionName) !== $this->lockDatabaseIdentity
            || $this->resolvedStore->getConnection() !== $connection
            || $this->resolvedStore->getLockConnection() !== $lockConnection
            || $this->databaseManager->connection($connectionName) !== $connection
            || $this->databaseManager->connection($lockConnectionName) !== $lockConnection
            || $this->stableWritePdo($connection) !== $this->dataPdo
            || $this->stableWritePdo($lockConnection) !== $this->lockPdo
            || $this->operationStore->getConnection() !== $this->dataOperationConnection
            || $this->operationStore->getLockConnection() !== $this->lockOperationConnection
            || $this->stableWritePdo($this->dataOperationConnection) !== $this->dataPdo
            || $this->stableWritePdo($this->lockOperationConnection) !== $this->lockPdo) {
            $this->rejectConfigurationMutation();
        }

        $dataTable = $this->tableIdentity(
            $connection,
            $this->dataDatabaseIdentity,
            (string) $this->property($this->resolvedStore, 'table'),
        );
        $lockTable = $this->tableIdentity(
            $lockConnection,
            $this->lockDatabaseIdentity,
            (string) $this->property($this->resolvedStore, 'lockTable'),
        );

        if ($dataTable !== $this->dataTableIdentity
            || $lockTable !== $this->lockTableIdentity) {
            $this->rejectConfigurationMutation();
        }

        if ($this->dataTableIdentity === $this->lockTableIdentity) {
            throw new InvalidArgumentException(
                'Aura media security requires dedicated, distinct physical database tables for cache data and locks.',
            );
        }

        $this->assertNoDefaultTableAlias([$this->dataTableIdentity, $this->lockTableIdentity]);

        if ($this->resolvedStore->getConnection() !== $connection
            || $this->resolvedStore->getLockConnection() !== $lockConnection
            || $this->property($this->resolvedStore, 'table') !== ($storeConfig['table'] ?? null)
            || $this->property($this->resolvedStore, 'lockTable') !== ($storeConfig['lock_table'] ?? 'cache_locks')
            || $this->stableWritePdo($connection) !== $this->dataPdo
            || $this->stableWritePdo($lockConnection) !== $this->lockPdo) {
            $this->rejectConfigurationMutation();
        }
    }

    /** @param list<string> $securityTables */
    private function assertNoDefaultTableAlias(array $securityTables): void
    {
        $defaultName = $this->config->get('cache.default');
        if (! is_string($defaultName)) {
            return;
        }

        $defaultTables = [];

        foreach ($this->defaultDatabaseStores($defaultName) as [$defaultStore, $defaultConfig]) {
            $defaultConnectionName = $this->connectionName($defaultConfig, 'connection');
            $defaultLockConnectionName = $this->connectionName($defaultConfig, 'lock_connection');
            $defaultConnection = $defaultStore->getConnection();
            $defaultLockConnection = $defaultStore->getLockConnection();
            $defaultTable = $this->property($defaultStore, 'table');
            $defaultLockTable = $this->property($defaultStore, 'lockTable');

            if ($defaultConnection !== $this->databaseManager->connection($defaultConnectionName)
                || $defaultLockConnection !== $this->databaseManager->connection($defaultLockConnectionName)
                || ! is_string($defaultTable)
                || ! is_string($defaultLockTable)) {
                $this->rejectConfigurationMutation();
            }

            $defaultDatabaseIdentity = $this->databaseIdentity($defaultConnection, $defaultConnectionName);
            $defaultLockDatabaseIdentity = $this->databaseIdentity($defaultLockConnection, $defaultLockConnectionName);
            $defaultPdo = $this->stableWritePdo($defaultConnection);
            $defaultLockPdo = $this->stableWritePdo($defaultLockConnection);

            $defaultTables[] = $this->tableIdentity(
                $defaultConnection,
                $defaultDatabaseIdentity,
                $defaultTable,
            );
            $defaultTables[] = $this->tableIdentity(
                $defaultLockConnection,
                $defaultLockDatabaseIdentity,
                $defaultLockTable,
            );

            if ($defaultStore->getConnection() !== $defaultConnection
                || $defaultStore->getLockConnection() !== $defaultLockConnection
                || $this->databaseManager->connection($defaultConnectionName) !== $defaultConnection
                || $this->databaseManager->connection($defaultLockConnectionName) !== $defaultLockConnection
                || $this->property($defaultStore, 'table') !== $defaultTable
                || $this->property($defaultStore, 'lockTable') !== $defaultLockTable
                || $this->stableWritePdo($defaultConnection) !== $defaultPdo
                || $this->stableWritePdo($defaultLockConnection) !== $defaultLockPdo) {
                $this->rejectConfigurationMutation();
            }
        }

        if (array_intersect($securityTables, $defaultTables) !== []) {
            throw new InvalidArgumentException(
                'Aura media security requires dedicated physical database tables that do not alias Laravel\'s default cache data or lock tables.',
            );
        }
    }

    private function assertSafeBoundary(): void
    {
        if ($this->application->make('cache') !== $this->cacheManager
            || $this->application->make('db') !== $this->databaseManager
            || $this->application->make('config') !== $this->config
            || get_class($this->cacheManager) !== CacheManager::class
            || get_class($this->databaseManager) !== DatabaseManager::class
            || get_class($this->config) !== ConfigurationRepository::class
            || get_class($this->application->make('db.factory')) !== ConnectionFactory::class
            || $this->property($this->cacheManager, 'app') !== $this->application
            || $this->property($this->databaseManager, 'app') !== $this->application
            || $this->property($this->databaseManager, 'factory') !== $this->application->make('db.factory')
            || array_key_exists('database', $this->property($this->cacheManager, 'customCreators'))
            || $this->config->get('aura.media.security.cache_store') !== $this->storeName
            || $this->config->get('cache.default') === $this->storeName
            || $this->config->get('cache.serializable_classes') !== false
            || $this->cacheManager->store($this->storeName) !== $this->repository
            || get_class($this->repository) !== CacheRepository::class
            || $this->repository->getStore() !== $this->resolvedStore
            || get_class($this->resolvedStore) !== DatabaseStore::class
            || $this->property($this->resolvedStore, 'serializableClasses') !== false
            || ! hash_equals($this->configurationFingerprint, $this->currentConfigurationFingerprint())) {
            $this->rejectConfigurationMutation();
        }

        $this->assertDatabaseConfiguration($this->storeConfiguration());
    }

    private function connectionName(array $storeConfig, string $key): string
    {
        $connection = $storeConfig[$key] ?? null;

        if ($key === 'lock_connection' && $connection === null) {
            $connection = $storeConfig['connection'] ?? null;
        }

        $connection ??= $this->config->get('database.default');

        if (! is_string($connection) || $connection === '') {
            $this->rejectConfigurationMutation();
        }

        return $connection;
    }

    private function currentConfigurationFingerprint(): string
    {
        try {
            $store = $this->config->get('cache.stores.'.$this->storeName);
            $configuration = [
                'security_store' => $this->config->get('aura.media.security.cache_store'),
                'default_store' => $this->config->get('cache.default'),
                'default_store_config' => $this->config->get('cache.stores.'.$this->config->get('cache.default')),
                'serializable_classes' => $this->config->get('cache.serializable_classes'),
                'store' => $store,
            ];

            if (is_array($store) && ($store['driver'] ?? null) === 'database') {
                $connection = $this->connectionName($store, 'connection');
                $lockConnection = $this->connectionName($store, 'lock_connection');
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

    private function databaseIdentity(object $connection, string $connectionName): string
    {
        if ($connection !== $this->databaseManager->connection($connectionName)
            || ! in_array(get_class($connection), [
                MariaDbConnection::class,
                MySqlConnection::class,
                PostgresConnection::class,
                SQLiteConnection::class,
                SqlServerConnection::class,
            ], true)
            || ! method_exists($connection, 'selectFromWriteConnection')
            || ! method_exists($connection, 'getConfig')) {
            $this->rejectConfigurationMutation();
        }

        $pdo = $connection->getPdo();

        if (! $pdo instanceof PDO || $this->stableWritePdo($connection) !== $pdo) {
            $this->rejectConfigurationMutation();
        }

        try {
            if ($connection instanceof SQLiteConnection) {
                $identity = $this->sqliteIdentity($connection);
            } else {
                $query = match (get_class($connection)) {
                    MariaDbConnection::class, MySqlConnection::class => 'SELECT DATABASE() AS database_name, @@hostname AS server_host, @@port AS server_port',
                    PostgresConnection::class => 'SELECT current_database() AS database_name, inet_server_addr()::text AS server_host, inet_server_port() AS server_port',
                    SqlServerConnection::class => "SELECT DB_NAME() AS database_name, CONVERT(nvarchar(128), SERVERPROPERTY('MachineName')) AS server_host, CONVERT(nvarchar(128), SERVERPROPERTY('InstanceName')) AS server_instance",
                    default => $this->rejectConfigurationMutation(),
                };
                $row = $connection->selectFromWriteConnection($query)[0] ?? null;
                $database = is_object($row) ? ($row->database_name ?? null) : null;

                if (! is_string($database) || $database !== $connection->getConfig('database')) {
                    $this->rejectConfigurationMutation();
                }

                $family = match (get_class($connection)) {
                    MariaDbConnection::class, MySqlConnection::class => 'mysql',
                    PostgresConnection::class => 'pgsql',
                    SqlServerConnection::class => 'sqlsrv',
                    default => $this->rejectConfigurationMutation(),
                };
                $identity = hash('sha256', $family."\0".$database);
            }

            if ($this->stableWritePdo($connection) !== $pdo) {
                $this->rejectConfigurationMutation();
            }

            return $identity;
        } catch (InvalidArgumentException $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                'Aura media security could not verify the physical database identity.',
                previous: $exception,
            );
        }
    }

    /**
     * @param  list<string>  $ancestors
     * @return list<array{DatabaseStore, array<string, mixed>}>
     */
    private function defaultDatabaseStores(string $storeName, array $ancestors = []): array
    {
        if (in_array($storeName, $ancestors, true)) {
            $this->rejectConfigurationMutation();
        }

        $config = $this->config->get('cache.stores.'.$storeName);

        if (! is_array($config) || ! is_string($config['driver'] ?? null)) {
            $this->rejectConfigurationMutation();
        }

        $repository = $this->cacheManager->store($storeName);
        $store = $repository instanceof CacheRepository ? $repository->getStore() : null;

        if (! $repository instanceof CacheRepository || get_class($repository) !== CacheRepository::class) {
            $this->rejectConfigurationMutation();
        }

        if ($store instanceof DatabaseStore) {
            if (get_class($store) !== DatabaseStore::class) {
                $this->rejectConfigurationMutation();
            }

            if ($config['driver'] === 'database') {
                return [[$store, $config]];
            }

            $connection = $store->getConnection();
            $lockConnection = $store->getLockConnection();

            if (! $connection instanceof Connection || ! $lockConnection instanceof Connection) {
                $this->rejectConfigurationMutation();
            }

            $connectionName = $connection->getName();
            $lockConnectionName = $lockConnection->getName();

            if (! is_string($connectionName) || ! is_string($lockConnectionName)) {
                $this->rejectConfigurationMutation();
            }

            return [[$store, [
                'driver' => 'database',
                'connection' => $connectionName,
                'table' => $this->property($store, 'table'),
                'lock_connection' => $lockConnectionName,
                'lock_table' => $this->property($store, 'lockTable'),
            ]]];
        }

        if ($config['driver'] !== 'failover') {
            return [];
        }

        $children = $config['stores'] ?? null;

        if (! $store instanceof FailoverStore
            || get_class($store) !== FailoverStore::class
            || ! is_array($children)
            || $children === []
            || $this->property($store, 'stores') !== $children) {
            $this->rejectConfigurationMutation();
        }

        $databaseStores = [];

        foreach ($children as $child) {
            if (! is_string($child) || $child === '') {
                $this->rejectConfigurationMutation();
            }

            array_push($databaseStores, ...$this->defaultDatabaseStores($child, [...$ancestors, $storeName]));
        }

        return $databaseStores;
    }

    private function pathContainsSymlink(string $path): bool
    {
        if (! str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return true;
        }

        $current = DIRECTORY_SEPARATOR;

        foreach (array_filter(explode(DIRECTORY_SEPARATOR, $path)) as $component) {
            $current = rtrim($current, DIRECTORY_SEPARATOR).DIRECTORY_SEPARATOR.$component;

            if (is_link($current)) {
                return true;
            }
        }

        return false;
    }

    private function physicalRelationIdentity(Connection $connection, string $table): string
    {
        $row = match (get_class($connection)) {
            SQLiteConnection::class => $connection->selectFromWriteConnection(
                'SELECT name AS relation_name, type AS relation_type, CAST(rootpage AS TEXT) AS relation_id FROM main.sqlite_schema WHERE name = ? LIMIT 1',
                [$table],
            )[0] ?? null,
            MariaDbConnection::class, MySqlConnection::class => $connection->selectFromWriteConnection(
                'SELECT TABLE_SCHEMA AS table_schema, TABLE_NAME AS relation_name, TABLE_TYPE AS relation_type FROM information_schema.tables WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = ? LIMIT 1',
                [$table],
            )[0] ?? null,
            PostgresConnection::class => $connection->selectFromWriteConnection(
                'SELECT c.oid::text AS relation_id, n.nspname AS table_schema, c.relname AS relation_name, c.relkind AS relation_type FROM pg_class c INNER JOIN pg_namespace n ON n.oid = c.relnamespace WHERE c.oid = to_regclass(?)',
                [$table],
            )[0] ?? null,
            SqlServerConnection::class => $connection->selectFromWriteConnection(
                "SELECT CONVERT(nvarchar(32), o.object_id) AS relation_id, SCHEMA_NAME(o.schema_id) AS table_schema, o.name AS relation_name, o.type AS relation_type FROM sys.objects o WHERE o.object_id = OBJECT_ID(?) AND o.type = 'U'",
                [$table],
            )[0] ?? null,
            default => $this->rejectConfigurationMutation(),
        };
        $relationName = is_object($row) ? ($row->relation_name ?? null) : null;
        $relationType = is_object($row) ? ($row->relation_type ?? null) : null;
        $allowedType = match (get_class($connection)) {
            SQLiteConnection::class => 'table',
            MariaDbConnection::class, MySqlConnection::class => 'BASE TABLE',
            PostgresConnection::class => ['r', 'p'],
            SqlServerConnection::class => 'U',
            default => $this->rejectConfigurationMutation(),
        };

        if (! is_string($relationName)
            || $relationName !== $table
            || (is_array($allowedType)
                ? ! in_array($relationType, $allowedType, true)
                : $relationType !== $allowedType)) {
            throw new InvalidArgumentException(
                'Aura media security cache and lock relations must resolve to base database tables.',
            );
        }

        $schema = is_object($row) ? ($row->table_schema ?? 'main') : null;
        $relationId = is_object($row) ? ($row->relation_id ?? $relationName) : null;

        if (! is_string($schema) || ! is_string($relationId)) {
            $this->rejectConfigurationMutation();
        }

        if ($connection instanceof SQLiteConnection
            && $connection->selectFromWriteConnection(
                'SELECT name FROM sqlite_temp_master WHERE type = ? AND lower(name) = lower(?) LIMIT 1',
                ['table', $table],
            ) !== []) {
            $this->rejectConfigurationMutation();
        }

        return $schema."\0".$relationId;
    }

    private function pinnedConnection(Connection $connection, PDO $pdo): Connection
    {
        $pinned = clone $connection;
        $pinned->setPdo($pdo);
        $pinned->setReadPdo($pdo);

        if (method_exists($pinned, 'setDirectPdo')) {
            $pinned->setDirectPdo($pdo);
        }

        $pinned->setReconnector(fn (Connection $_connection): never => $this->rejectConfigurationMutation());

        return $pinned;
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

    private function sqliteIdentity(SQLiteConnection $connection): string
    {
        $rows = $connection->selectFromWriteConnection('PRAGMA database_list');
        $main = collect($rows)->first(fn (object $row): bool => ($row->name ?? null) === 'main');
        $database = is_object($main) ? ($main->file ?? null) : null;
        $configured = $connection->getConfig('database');

        if (! is_string($configured)
            || ! is_string($database)
            || $database === ''
            || $this->pathContainsSymlink($configured)) {
            $this->rejectConfigurationMutation();
        }

        $configuredPath = realpath($configured);
        $databasePath = realpath($database);
        $stat = $databasePath !== false ? stat($databasePath) : false;

        if ($configuredPath === false
            || $databasePath === false
            || $configuredPath !== $databasePath
            || ! is_array($stat)) {
            $this->rejectConfigurationMutation();
        }

        return sprintf('sqlite:%s:%s', (string) $stat['dev'], (string) $stat['ino']);
    }

    private function stableWritePdo(Connection $connection): PDO
    {
        $pdo = $connection->getRawPdo();
        $readPdo = $connection->getRawReadPdo();
        $directPdo = method_exists($connection, 'getRawDirectPdo')
            ? $connection->getRawDirectPdo()
            : null;

        if (! $pdo instanceof PDO
            || ($readPdo !== null && $readPdo !== $pdo)
            || ($directPdo !== null && $directPdo !== $pdo)) {
            $this->rejectConfigurationMutation();
        }

        return $pdo;
    }

    /** @return array<string, mixed> */
    private function storeConfiguration(): array
    {
        $storeConfig = $this->config->get('cache.stores.'.$this->storeName);

        if (! is_array($storeConfig) || ($storeConfig['driver'] ?? null) !== 'database') {
            $this->rejectConfigurationMutation();
        }

        return $storeConfig;
    }

    private function tableIdentity(Connection $connection, string $databaseIdentity, string $table): string
    {
        $pdo = $this->stableWritePdo($connection);
        $physicalTable = $connection->getTablePrefix().$table;

        if (preg_match('/\A[a-z][a-z0-9_]*\z/D', $physicalTable) !== 1) {
            throw new InvalidArgumentException(
                'Aura media security database tables must use canonical unqualified lowercase identifiers.',
            );
        }

        $relationIdentity = $this->physicalRelationIdentity($connection, $physicalTable);

        if ($this->stableWritePdo($connection) !== $pdo) {
            $this->rejectConfigurationMutation();
        }

        return hash('sha256', $databaseIdentity."\0".$relationIdentity);
    }
}
