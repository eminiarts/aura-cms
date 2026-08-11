<?php

namespace Aura\Base\Livewire\Media;

use Closure;
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
use PDOException;
use ReflectionProperty;
use Throwable;

class MediaSecurityStore implements LockProvider
{
    private const DATA_RELATION_MARKER_KEY = '__aura_media_security_data_relation_v1__';

    private const LOCK_RELATION_MARKER_KEY = '__aura_media_security_lock_relation_v1__';

    public readonly MediaSecurityStore $cache;

    public readonly LockProvider $locks;

    private readonly CacheManager $cacheManager;

    private readonly string $configurationFingerprint;

    private readonly DatabaseManager $databaseManager;

    private readonly string $dataDatabaseIdentity;

    private readonly Connection $dataOperationConnection;

    private readonly PDO $dataPdo;

    private readonly string $dataQualifiedTable;

    private readonly string $dataRelationMarker;

    private readonly string $dataTableIdentity;

    private readonly string $lockDatabaseIdentity;

    private readonly Connection $lockOperationConnection;

    private readonly PDO $lockPdo;

    private readonly string $lockQualifiedTable;

    private readonly string $lockRelationMarker;

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
        $dataTableBoundary = $this->tableBoundary(
            $dataConnection,
            $this->dataDatabaseIdentity,
            (string) $this->property($store, 'table'),
        );
        $lockTableBoundary = $this->tableBoundary(
            $lockConnection,
            $this->lockDatabaseIdentity,
            (string) $this->property($store, 'lockTable'),
        );
        $this->dataTableIdentity = $dataTableBoundary['identity'];
        $this->dataQualifiedTable = $dataTableBoundary['qualified_table'];
        $this->lockTableIdentity = $lockTableBoundary['identity'];
        $this->lockQualifiedTable = $lockTableBoundary['qualified_table'];
        $this->dataOperationConnection = $this->pinnedConnection($dataConnection, $this->dataPdo);
        $this->lockOperationConnection = $lockConnection === $dataConnection
            ? $this->dataOperationConnection
            : $this->pinnedConnection($lockConnection, $this->lockPdo);
        $this->operationStore = clone $store;
        $this->operationStore->setConnection($this->dataOperationConnection);
        $this->operationStore->setLockConnection($this->lockOperationConnection);
        $this->setProperty($this->operationStore, 'table', $this->dataQualifiedTable);
        $this->setProperty($this->operationStore, 'lockTable', $this->lockQualifiedTable);
        $this->operationRepository = new CacheRepository($this->operationStore);
        $this->cache = $this;
        $this->locks = $this;
        $this->assertSafeBoundary();
        $this->dataRelationMarker = $this->ensureRelationMarker(
            $this->dataOperationConnection,
            $this->dataQualifiedTable,
            self::DATA_RELATION_MARKER_KEY,
            'value',
        );
        $this->lockRelationMarker = $this->ensureRelationMarker(
            $this->lockOperationConnection,
            $this->lockQualifiedTable,
            self::LOCK_RELATION_MARKER_KEY,
            'owner',
        );
    }

    public function add(string $key, mixed $value, int $seconds): bool
    {
        $this->assertApplicationKey($key);

        return $this->executeDataOperation(
            fn (): bool => $this->operationRepository->add($key, $value, $seconds),
        );
    }

    public function forget(string $key): bool
    {
        $this->assertApplicationKey($key);

        return $this->executeDataOperation(
            fn (): bool => $this->operationRepository->forget($key),
        );
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $this->assertApplicationKey($key);

        return $this->executeDataOperation(
            fn (): mixed => $this->operationRepository->get($key, $default),
        );
    }

    public function lock($name, $seconds = 0, $owner = null)
    {
        $this->assertApplicationKey((string) $name);
        $this->assertSafeBoundary();

        return new MediaSecurityLock(
            $this->operationStore->lock($name, $seconds, $owner),
            fn (Closure $operation): mixed => $this->executeLockOperation($operation),
        );
    }

    public function put(string $key, mixed $value, int $seconds): bool
    {
        $this->assertApplicationKey($key);

        return $this->executeDataOperation(
            fn (): bool => $this->operationRepository->put($key, $value, $seconds),
        );
    }

    public function restoreLock($name, $owner)
    {
        $this->assertApplicationKey((string) $name);
        $this->assertSafeBoundary();

        return new MediaSecurityLock(
            $this->operationStore->restoreLock($name, $owner),
            fn (Closure $operation): mixed => $this->executeLockOperation($operation),
        );
    }

    private function assertApplicationKey(string $key): void
    {
        $physicalKey = $this->operationStore->getPrefix().$key;

        if (in_array($physicalKey, [self::DATA_RELATION_MARKER_KEY, self::LOCK_RELATION_MARKER_KEY], true)) {
            throw new InvalidArgumentException('Aura media security relation-marker keys are reserved.');
        }
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
            || $this->property($this->operationStore, 'table') !== $this->dataQualifiedTable
            || $this->property($this->operationStore, 'lockTable') !== $this->lockQualifiedTable
            || $this->dataOperationConnection->getTablePrefix() !== ''
            || $this->lockOperationConnection->getTablePrefix() !== ''
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

    private function assertRelationMarker(
        Connection $connection,
        string $qualifiedTable,
        string $markerKey,
        string $markerColumn,
        string $marker,
        bool $lock,
    ): void {
        $actual = $this->relationMarker(
            $connection,
            $qualifiedTable,
            $markerKey,
            $markerColumn,
            $lock,
        );

        if (! is_string($actual) || ! hash_equals($marker, $actual)) {
            $this->rejectConfigurationMutation();
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

    private function ensureRelationMarker(
        Connection $connection,
        string $qualifiedTable,
        string $markerKey,
        string $markerColumn,
    ): string {
        $existing = $this->relationMarker($connection, $qualifiedTable, $markerKey, $markerColumn, false);

        if ($existing === false) {
            $marker = 'aura-v1:'.bin2hex(random_bytes(32));
            $quotedTable = $this->quoteQualifiedTable($connection, $qualifiedTable);
            $quotedKey = $this->quoteIdentifier($connection, 'key');
            $quotedMarker = $this->quoteIdentifier($connection, $markerColumn);
            $quotedExpiration = $this->quoteIdentifier($connection, 'expiration');
            $sql = match (get_class($connection)) {
                SQLiteConnection::class => "INSERT OR IGNORE INTO {$quotedTable} ({$quotedKey}, {$quotedMarker}, {$quotedExpiration}) VALUES (?, ?, ?)",
                MariaDbConnection::class, MySqlConnection::class => "INSERT IGNORE INTO {$quotedTable} ({$quotedKey}, {$quotedMarker}, {$quotedExpiration}) VALUES (?, ?, ?)",
                PostgresConnection::class => "INSERT INTO {$quotedTable} ({$quotedKey}, {$quotedMarker}, {$quotedExpiration}) VALUES (?, ?, ?) ON CONFLICT ({$quotedKey}) DO NOTHING",
                SqlServerConnection::class => "INSERT INTO {$quotedTable} ({$quotedKey}, {$quotedMarker}, {$quotedExpiration}) VALUES (?, ?, ?)",
                default => $this->rejectConfigurationMutation(),
            };

            try {
                $statement = $this->stableWritePdo($connection)->prepare($sql);
                $statement->execute([$markerKey, $marker, 2147483647]);
            } catch (PDOException) {
                // A concurrent process may have installed the marker first.
            }

            $existing = $this->relationMarker($connection, $qualifiedTable, $markerKey, $markerColumn, false);
        }

        if (! is_string($existing) || preg_match('/\Aaura-v1:[a-f0-9]{64}\z/D', $existing) !== 1) {
            $this->rejectConfigurationMutation();
        }

        return $existing;
    }

    /** @param Closure(): mixed $operation */
    private function executeDataOperation(Closure $operation): mixed
    {
        return $this->executeOperation(
            $this->dataOperationConnection,
            $this->dataQualifiedTable,
            self::DATA_RELATION_MARKER_KEY,
            'value',
            $this->dataRelationMarker,
            $operation,
        );
    }

    /** @param Closure(): mixed $operation */
    private function executeLockOperation(Closure $operation): mixed
    {
        return $this->executeOperation(
            $this->lockOperationConnection,
            $this->lockQualifiedTable,
            self::LOCK_RELATION_MARKER_KEY,
            'owner',
            $this->lockRelationMarker,
            $operation,
        );
    }

    /** @param Closure(): mixed $operation */
    private function executeOperation(
        Connection $connection,
        string $qualifiedTable,
        string $markerKey,
        string $markerColumn,
        string $marker,
        Closure $operation,
    ): mixed {
        $this->assertSafeBoundary();
        $pdo = $this->stableWritePdo($connection);

        if ($pdo->inTransaction() || $connection->transactionLevel() !== 0) {
            $this->rejectConfigurationMutation();
        }

        try {
            $beganTransaction = $connection instanceof SQLiteConnection
                ? $pdo->exec('BEGIN IMMEDIATE') !== false
                : $pdo->beginTransaction();

            if (! $beganTransaction) {
                $this->rejectConfigurationMutation();
            }

            $this->assertRelationMarker($connection, $qualifiedTable, $markerKey, $markerColumn, $marker, true);
            $result = $operation();
            $this->assertRelationMarker($connection, $qualifiedTable, $markerKey, $markerColumn, $marker, true);

            if (! $pdo->inTransaction() || ! $pdo->commit()) {
                $this->rejectConfigurationMutation();
            }

            return $result;
        } catch (Throwable $exception) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            throw $exception;
        }
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

    /** @return array{identity: string, qualified_table: string} */
    private function physicalRelationBoundary(Connection $connection, string $table): array
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

        // Schema/database names may include hyphens (e.g. Herd/local DBs like
        // `aura-demo`). Identifiers are always quoted; only a safe charset is
        // accepted. Table names stay restricted to [a-z0-9_] elsewhere.
        if (! is_string($schema)
            || preg_match('/\A[a-z][a-z0-9_-]*\z/D', $schema) !== 1
            || ! is_string($relationId)) {
            $this->rejectConfigurationMutation();
        }

        if ($connection instanceof SQLiteConnection
            && $connection->selectFromWriteConnection(
                'SELECT name FROM sqlite_temp_master WHERE type = ? AND lower(name) = lower(?) LIMIT 1',
                ['table', $table],
            ) !== []) {
            $this->rejectConfigurationMutation();
        }

        return [
            'identity' => $schema."\0".$relationId,
            'qualified_table' => $schema.'.'.$relationName,
        ];
    }

    private function pinnedConnection(Connection $connection, PDO $pdo): Connection
    {
        $pinned = clone $connection;
        $pinned->setTablePrefix('');
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

    private function quoteIdentifier(Connection $connection, string $identifier): string
    {
        // Allow hyphens so schema-qualified names like `aura-demo.media_security_cache`
        // can be quoted safely on MySQL/MariaDB (and other drivers' quoted forms).
        if (preg_match('/\A[a-z][a-z0-9_-]*\z/D', $identifier) !== 1) {
            $this->rejectConfigurationMutation();
        }

        return match (get_class($connection)) {
            MariaDbConnection::class, MySqlConnection::class => '`'.$identifier.'`',
            SQLiteConnection::class, PostgresConnection::class => '"'.$identifier.'"',
            SqlServerConnection::class => '['.$identifier.']',
            default => $this->rejectConfigurationMutation(),
        };
    }

    private function quoteQualifiedTable(Connection $connection, string $qualifiedTable): string
    {
        $parts = explode('.', $qualifiedTable);

        if (count($parts) !== 2) {
            $this->rejectConfigurationMutation();
        }

        return implode('.', array_map(
            fn (string $identifier): string => $this->quoteIdentifier($connection, $identifier),
            $parts,
        ));
    }

    private function rejectConfigurationMutation(): never
    {
        throw new InvalidArgumentException(
            'Aura media security cache configuration changed or no longer guarantees object-free reads.',
        );
    }

    private function relationMarker(
        Connection $connection,
        string $qualifiedTable,
        string $markerKey,
        string $markerColumn,
        bool $lock,
    ): string|false {
        $quotedTable = $this->quoteQualifiedTable($connection, $qualifiedTable);
        $quotedKey = $this->quoteIdentifier($connection, 'key');
        $quotedMarker = $this->quoteIdentifier($connection, $markerColumn);
        $lockClause = match (get_class($connection)) {
            MariaDbConnection::class, MySqlConnection::class => $lock ? ' LOCK IN SHARE MODE' : '',
            PostgresConnection::class => $lock ? ' FOR SHARE' : '',
            SQLiteConnection::class, SqlServerConnection::class => '',
            default => $this->rejectConfigurationMutation(),
        };
        $tableClause = $connection instanceof SqlServerConnection && $lock
            ? $quotedTable.' WITH (HOLDLOCK)'
            : $quotedTable;
        $statement = $this->stableWritePdo($connection)->prepare(
            "SELECT {$quotedMarker} FROM {$tableClause} WHERE {$quotedKey} = ?{$lockClause}",
        );
        $statement->execute([$markerKey]);

        return $statement->fetchColumn();
    }

    private function setProperty(object $object, string $property, mixed $value): void
    {
        try {
            (new ReflectionProperty($object, $property))->setValue($object, $value);
        } catch (Throwable $exception) {
            throw new InvalidArgumentException(
                'Aura media security could not bind the validated cache relation.',
                previous: $exception,
            );
        }
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

    /** @return array{identity: string, qualified_table: string} */
    private function tableBoundary(Connection $connection, string $databaseIdentity, string $table): array
    {
        $pdo = $this->stableWritePdo($connection);
        $physicalTable = $connection->getTablePrefix().$table;

        if (preg_match('/\A[a-z][a-z0-9_]*\z/D', $physicalTable) !== 1) {
            throw new InvalidArgumentException(
                'Aura media security database tables must use canonical unqualified lowercase identifiers.',
            );
        }

        $relationBoundary = $this->physicalRelationBoundary($connection, $physicalTable);

        if ($this->stableWritePdo($connection) !== $pdo) {
            $this->rejectConfigurationMutation();
        }

        return [
            'identity' => hash('sha256', $databaseIdentity."\0".$relationBoundary['identity']),
            'qualified_table' => $relationBoundary['qualified_table'],
        ];
    }

    private function tableIdentity(Connection $connection, string $databaseIdentity, string $table): string
    {
        return $this->tableBoundary($connection, $databaseIdentity, $table)['identity'];
    }
}
