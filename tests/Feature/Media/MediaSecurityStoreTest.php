<?php

use Aura\Base\Fields\Image;
use Aura\Base\Livewire\Media\InvalidMediaSelectionRequest;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\Media\MediaSecurityStore;
use Aura\Base\Livewire\Media\MediaSelectionBroker;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\DynamoDbStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Database\Connectors\ConnectionFactory;
use Illuminate\Database\DatabaseManager;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\SQLiteConnection;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

final class Core20CacheDeserializationProbe
{
    public static int $destructed = 0;

    public static ?string $marker = null;

    public static int $woken = 0;

    public function __destruct()
    {
        self::$destructed++;
        self::mark('destruct');
    }

    public function __wakeup()
    {
        self::$woken++;
        self::mark('wakeup');
    }

    private static function mark(string $event): void
    {
        if (self::$marker !== null) {
            file_put_contents(self::$marker, $event.PHP_EOL, FILE_APPEND);
        }
    }
}

final class Core20ProxiedFileStore extends FileStore {}

final class Core20ProxiedCacheRepository extends CacheRepository {}

final readonly class Core20FixedMediaCacheFactory implements CacheFactory
{
    public function __construct(private CacheRepository $repository) {}

    public function store($name = null): CacheRepository
    {
        return $this->repository;
    }
}

function installCore20MediaRepository(CacheRepository $repository): void
{
    $manager = app('cache');
    $stores = (new ReflectionProperty($manager, 'stores'))->getValue($manager);
    $stores['aura-media-security'] = $repository;
    (new ReflectionProperty($manager, 'stores'))->setValue($manager, $stores);
}

/** @param array<string, mixed> $connectionConfig */
function verifyCore20NativeSecurityDatabase(array $connectionConfig): void
{
    config()->set('database.connections.core20-native-security', $connectionConfig);
    config()->set('cache.stores.aura-media-security.connection', 'core20-native-security');
    config()->set('cache.stores.aura-media-security.table', 'core20_security_cache');
    config()->set('cache.stores.aura-media-security.lock_connection', 'core20-native-security');
    config()->set('cache.stores.aura-media-security.lock_table', 'core20_security_locks');
    app('db')->purge('core20-native-security');
    app('cache')->forgetDriver('aura-media-security');
    $usesPostgresSearchPath = ($connectionConfig['driver'] ?? null) === 'pgsql';

    if ($usesPostgresSearchPath) {
        DB::connection('core20-native-security')->statement('CREATE SCHEMA IF NOT EXISTS core20_security_scope');
        DB::connection('core20-native-security')->statement('SET search_path TO core20_security_scope');
    }

    $schema = Schema::connection('core20-native-security');
    $schema->dropIfExists('core20_security_locks');
    $schema->dropIfExists('core20_security_cache');
    $schema->create('core20_security_cache', function ($table) {
        $table->string('key')->primary();
        $table->mediumText('value');
        $table->integer('expiration');
    });
    $schema->create('core20_security_locks', function ($table) {
        $table->string('key')->primary();
        $table->string('owner');
        $table->integer('expiration');
    });

    try {
        $security = app(MediaSecurityStore::class);

        expect($security->put('native-relation', 'verified', 60))->toBeTrue()
            ->and($security->get('native-relation'))->toBe('verified');
    } finally {
        app()->forgetInstance(MediaSecurityStore::class);
        app('cache')->forgetDriver('aura-media-security');
        $schema->dropIfExists('core20_security_locks');
        $schema->dropIfExists('core20_security_cache');

        if ($usesPostgresSearchPath) {
            DB::connection('core20-native-security')->statement('SET search_path TO public');
            DB::connection('core20-native-security')->statement('DROP SCHEMA IF EXISTS core20_security_scope');
        }

        app('db')->purge('core20-native-security');
    }
}

beforeEach(function () {
    $this->actor = createSuperAdmin();
    app('aura')::registerResources([Post::class]);
});

test('security cache requires object-free Laravel unserialization', function () {
    config()->set('cache.serializable_classes', null);
    app('cache')->forgetDriver('aura-media-security');

    expect(fn () => app(MediaSecurityStore::class))
        ->toThrow(InvalidArgumentException::class, 'serializable_classes');
});

test('security cache requires a non-default dedicated store', function () {
    config()->set('cache.default', 'aura-media-security');

    expect(fn () => app(MediaSecurityStore::class))
        ->toThrow(InvalidArgumentException::class, 'dedicated store');
});

test('security cache verifies object-free mode on the resolved store', function () {
    $connection = app('db')->connection('testing');
    $store = (new DatabaseStore(
        $connection,
        'media_security_cache',
        '',
        'media_security_cache_locks',
    ))->setLockConnection($connection);
    installCore20MediaRepository(new CacheRepository($store));

    expect(fn () => app(MediaSecurityStore::class))
        ->toThrow(InvalidArgumentException::class, 'object-free reads');
});

test('security cache rejects file stores because path identity cannot close retarget races', function () {
    $defaultPath = storage_path('framework/cache/data/default-file-'.bin2hex(random_bytes(4)));
    $securityPath = storage_path('framework/cache/data/security-file-'.bin2hex(random_bytes(4)));
    mkdir($defaultPath, 0777, true);
    symlink($defaultPath, $securityPath);
    config()->set('cache.stores.aura-media-security', [
        'driver' => 'file',
        'path' => $securityPath,
        'lock_path' => $securityPath,
    ]);
    app('cache')->forgetDriver('aura-media-security');

    try {
        expect(fn () => app(MediaSecurityStore::class))
            ->toThrow(InvalidArgumentException::class, 'database cache store');
    } finally {
        unlink($securityPath);
        rmdir($defaultPath);
    }
});

test('security cache rejects file stores before cache and lock symlinks can be retargeted', function () {
    $first = storage_path('framework/cache/data/security-first-'.bin2hex(random_bytes(4)));
    $second = storage_path('framework/cache/data/security-second-'.bin2hex(random_bytes(4)));
    $cachePath = storage_path('framework/cache/data/security-cache-link-'.bin2hex(random_bytes(4)));
    $lockPath = storage_path('framework/cache/data/security-lock-link-'.bin2hex(random_bytes(4)));
    mkdir($first, 0777, true);
    mkdir($second, 0777, true);
    symlink($first, $cachePath);
    symlink($first, $lockPath);
    config()->set('cache.stores.aura-media-security', [
        'driver' => 'file',
        'path' => $cachePath,
        'lock_path' => $lockPath,
    ]);
    app('cache')->forgetDriver('aura-media-security');

    try {
        expect(fn () => app(MediaSecurityStore::class))
            ->toThrow(InvalidArgumentException::class, 'database cache store');
    } finally {
        unlink($cachePath);
        unlink($lockPath);
        rmdir($first);
        rmdir($second);
    }
});

test('security cache rejects a swapped cache factory even when it returns an exact database store', function () {
    $connection = new SQLiteConnection(new PDO('sqlite::memory:'), ':memory:', '', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'name' => 'media-security-testing',
    ]);
    $store = (new DatabaseStore($connection, 'media_security_cache', '', 'media_security_cache_locks', [2, 100], 86400, false))
        ->setLockConnection($connection);

    expect(fn () => new MediaSecurityStore(
        new Core20FixedMediaCacheFactory(new CacheRepository($store)),
        app('config'),
        app('db'),
        app(),
    ))->toThrow(InvalidArgumentException::class, 'CacheManager');
});

test('security cache rejects a default store physical table alias', function () {
    config()->set('cache.default', 'database-default-alias');
    config()->set('cache.stores.database-default-alias', [
        'driver' => 'database',
        'connection' => 'media-security-testing',
        'table' => 'media_security_cache',
        'lock_connection' => 'media-security-testing',
        'lock_table' => 'media_security_cache_locks',
    ]);
    app('cache')->forgetDriver('database-default-alias');
    app('cache')->forgetDriver('aura-media-security');

    expect(fn () => app(MediaSecurityStore::class))
        ->toThrow(InvalidArgumentException::class, 'physical database tables');
});

test('security cache rejects a default store physical lock table alias', function () {
    config()->set('cache.default', 'database-default-lock-alias');
    config()->set('cache.stores.database-default-lock-alias', [
        'driver' => 'database',
        'connection' => 'media-security-testing',
        'table' => 'ordinary_default_cache',
        'lock_connection' => 'media-security-testing',
        'lock_table' => 'media_security_cache_locks',
    ]);
    app('cache')->forgetDriver('database-default-lock-alias');
    app('cache')->forgetDriver('aura-media-security');

    expect(fn () => app(MediaSecurityStore::class))
        ->toThrow(InvalidArgumentException::class, 'physical database tables');
});

test('security cache rejects case aliases to a default SQLite table', function () {
    config()->set('cache.default', 'database-default-case-alias');
    config()->set('cache.stores.database-default-case-alias', [
        'driver' => 'database',
        'connection' => 'media-security-testing',
        'table' => 'MEDIA_SECURITY_CACHE',
        'lock_connection' => 'media-security-testing',
        'lock_table' => 'ordinary_default_locks',
    ]);
    app('cache')->forgetDriver('database-default-case-alias');
    app('cache')->forgetDriver('aura-media-security');

    expect(fn () => app(MediaSecurityStore::class))
        ->toThrow(InvalidArgumentException::class, 'canonical unqualified');
});

test('security cache rejects default database views instead of hashing their alias name', function () {
    DB::connection('media-security-testing')->statement(
        'CREATE VIEW media_security_cache_view AS SELECT key, value, expiration FROM media_security_cache',
    );
    config()->set('cache.default', 'database-default-view');
    config()->set('cache.stores.database-default-view', [
        'driver' => 'database',
        'connection' => 'media-security-testing',
        'table' => 'media_security_cache_view',
        'lock_connection' => 'media-security-testing',
        'lock_table' => 'ordinary_default_locks',
    ]);
    app('cache')->forgetDriver('database-default-view');
    app('cache')->forgetDriver('aura-media-security');

    try {
        expect(fn () => app(MediaSecurityStore::class))
            ->toThrow(InvalidArgumentException::class, 'base database tables');
    } finally {
        DB::connection('media-security-testing')->statement('DROP VIEW media_security_cache_view');
    }
});

test('security cache rejects a differently named database connection alias to default tables', function () {
    $database = config('database.connections.media-security-testing.database');
    config()->set('database.connections.default-physical-alias', [
        'driver' => 'sqlite',
        'database' => $database,
        'prefix' => '',
    ]);
    config()->set('cache.default', 'database-default-connection-alias');
    config()->set('cache.stores.database-default-connection-alias', [
        'driver' => 'database',
        'connection' => 'default-physical-alias',
        'table' => 'media_security_cache',
        'lock_connection' => 'default-physical-alias',
        'lock_table' => 'media_security_cache_locks',
    ]);
    app('cache')->forgetDriver('database-default-connection-alias');
    app('cache')->forgetDriver('aura-media-security');

    expect(fn () => app(MediaSecurityStore::class))
        ->toThrow(InvalidArgumentException::class, 'physical database tables');
});

test('security cache requires distinct physical cache and lock tables', function () {
    config()->set('cache.stores.aura-media-security.lock_table', 'media_security_cache');
    app('cache')->forgetDriver('aura-media-security');

    expect(fn () => app(MediaSecurityStore::class))
        ->toThrow(InvalidArgumentException::class, 'distinct physical database tables');
});

test('security cache includes database connection prefixes in physical table identity', function () {
    $database = config('database.connections.media-security-testing.database');
    config()->set('database.connections.security-prefixed', [
        'driver' => 'sqlite',
        'database' => $database,
        'prefix' => 'media_',
    ]);
    config()->set('cache.stores.aura-media-security.connection', 'security-prefixed');
    config()->set('cache.stores.aura-media-security.table', 'security_cache');
    config()->set('cache.stores.aura-media-security.lock_connection', 'media-security-testing');
    config()->set('cache.stores.aura-media-security.lock_table', 'media_security_cache');
    app('cache')->forgetDriver('aura-media-security');

    expect(fn () => app(MediaSecurityStore::class))
        ->toThrow(InvalidArgumentException::class, 'distinct physical database tables');
});

test('security cache treats hard-linked SQLite paths as one physical database', function () {
    $database = config('database.connections.media-security-testing.database');
    $hardLink = dirname($database).'/media-security-hard-link-'.bin2hex(random_bytes(4)).'.sqlite';
    link($database, $hardLink);
    config()->set('database.connections.default-hard-link', [
        'driver' => 'sqlite',
        'database' => $hardLink,
        'prefix' => '',
    ]);
    config()->set('cache.default', 'database-default-hard-link');
    config()->set('cache.stores.database-default-hard-link', [
        'driver' => 'database',
        'connection' => 'default-hard-link',
        'table' => 'media_security_cache',
        'lock_connection' => 'default-hard-link',
        'lock_table' => 'media_security_cache_locks',
    ]);
    app('cache')->forgetDriver('database-default-hard-link');
    app('cache')->forgetDriver('aura-media-security');

    try {
        expect(fn () => app(MediaSecurityStore::class))
            ->toThrow(InvalidArgumentException::class, 'physical database tables');
    } finally {
        app('db')->purge('default-hard-link');
        unlink($hardLink);
    }
});

test('security cache rejects aliases reached through the default failover store', function () {
    config()->set('cache.default', 'default-failover');
    config()->set('cache.stores.default-database-alias', [
        'driver' => 'database',
        'connection' => 'media-security-testing',
        'table' => 'media_security_cache',
        'lock_connection' => 'media-security-testing',
        'lock_table' => 'media_security_cache_locks',
    ]);
    config()->set('cache.stores.default-array-fallback', [
        'driver' => 'array',
    ]);
    config()->set('cache.stores.default-failover', [
        'driver' => 'failover',
        'stores' => ['default-database-alias', 'default-array-fallback'],
    ]);
    app('cache')->forgetDriver('default-database-alias');
    app('cache')->forgetDriver('default-array-fallback');
    app('cache')->forgetDriver('default-failover');
    app('cache')->forgetDriver('aura-media-security');

    expect(fn () => app(MediaSecurityStore::class))
        ->toThrow(InvalidArgumentException::class, 'physical database tables');
});

test('security cache inspects a custom default driver that resolves to an exact database store', function () {
    $connection = app('db')->connection('media-security-testing');
    $databaseStore = (new DatabaseStore(
        $connection,
        'media_security_cache',
        '',
        'media_security_cache_locks',
        [2, 100],
        86400,
        false,
    ))->setLockConnection($connection);
    app('cache')->extend('core20-default-database', fn () => new CacheRepository($databaseStore));
    config()->set('cache.default', 'custom-default-database');
    config()->set('cache.stores.custom-default-database', [
        'driver' => 'core20-default-database',
    ]);
    app('cache')->forgetDriver('custom-default-database');
    app('cache')->forgetDriver('aura-media-security');

    expect(fn () => app(MediaSecurityStore::class))
        ->toThrow(InvalidArgumentException::class, 'physical database tables');
});

test('security cache rejects cache manager replacement before every operation', function () {
    $store = app(MediaSecurityStore::class);
    app()->instance('cache', new CacheManager(app()));

    expect(fn () => $store->get('manager-replacement'))
        ->toThrow(InvalidArgumentException::class, 'object-free reads');
});

test('security cache rejects database manager replacement before every operation', function () {
    $store = app(MediaSecurityStore::class);
    $manager = new DatabaseManager(
        app(),
        app(ConnectionFactory::class),
    );
    app()->instance('db', $manager);

    expect(fn () => $store->get('database-manager-replacement'))
        ->toThrow(InvalidArgumentException::class, 'object-free reads');
});

test('security cache rejects a rogue lock connection with the configured name', function () {
    $repository = app('cache')->store('aura-media-security');
    $store = $repository->getStore();
    $rogue = new SQLiteConnection(new PDO('sqlite::memory:'), ':memory:', '', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'name' => 'media-security-testing',
    ]);
    $store->setLockConnection($rogue);

    expect(fn () => app(MediaSecurityStore::class))
        ->toThrow(InvalidArgumentException::class, 'object-free reads');
});

test('security cache rejects a rogue data connection with the configured name', function () {
    $repository = app('cache')->store('aura-media-security');
    $store = $repository->getStore();
    $rogue = new SQLiteConnection(new PDO('sqlite::memory:'), ':memory:', '', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'name' => 'media-security-testing',
    ]);
    $store->setConnection($rogue);

    expect(fn () => app(MediaSecurityStore::class))
        ->toThrow(InvalidArgumentException::class, 'object-free reads');
});

test('security cache rejects a data connection swapped during boundary identity validation', function () {
    $security = app(MediaSecurityStore::class);
    $store = app('cache')->store('aura-media-security')->getStore();
    $rogue = new SQLiteConnection(new PDO('sqlite::memory:'), ':memory:', '', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'name' => 'media-security-testing',
    ]);
    $swapped = false;
    app('events')->listen(QueryExecuted::class, function (QueryExecuted $event) use ($store, $rogue, &$swapped): void {
        if (! $swapped && $event->sql === 'PRAGMA database_list') {
            $swapped = true;
            $store->setConnection($rogue);
        }
    });

    expect(fn () => $security->get('mid-validation-swap'))
        ->toThrow(InvalidArgumentException::class, 'object-free reads')
        ->and($swapped)->toBeTrue();
});

test('security cache rejects a PDO retarget after the last boundary identity query', function () {
    $security = app(MediaSecurityStore::class);
    $connection = app('db')->connection('media-security-testing');
    $identityQueries = 0;
    app('events')->listen(QueryExecuted::class, function (QueryExecuted $event) use ($connection, &$identityQueries): void {
        if ($event->sql !== 'PRAGMA database_list') {
            return;
        }

        $identityQueries++;

        if ($identityQueries === 2) {
            $connection->setPdo(new PDO('sqlite::memory:'));
        }
    });

    expect(fn () => $security->get('mid-validation-pdo-retarget'))
        ->toThrow(InvalidArgumentException::class, 'object-free reads')
        ->and($identityQueries)->toBe(2);
});

test('security cache rejects a default store connection swapped during alias validation', function () {
    config()->set('cache.default', 'database-default-retarget');
    config()->set('cache.stores.database-default-retarget', [
        'driver' => 'database',
        'connection' => 'media-security-testing',
        'table' => 'ordinary_default_cache',
        'lock_connection' => 'media-security-testing',
        'lock_table' => 'ordinary_default_locks',
    ]);
    app('cache')->forgetDriver('database-default-retarget');
    app('cache')->forgetDriver('aura-media-security');
    $security = app(MediaSecurityStore::class);
    $defaultStore = app('cache')->store('database-default-retarget')->getStore();
    $rogue = new SQLiteConnection(new PDO('sqlite::memory:'), ':memory:', '', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'name' => 'media-security-testing',
    ]);
    $retargeted = false;
    app('events')->listen(QueryExecuted::class, function (QueryExecuted $event) use ($defaultStore, $rogue, &$retargeted): void {
        if (! $retargeted
            && $event->connection === app('db')->connection('media-security-testing')
            && str_contains($event->sql, 'sqlite_schema')) {
            $retargeted = true;
            $defaultStore->setConnection($rogue);
        }
    });

    expect(fn () => $security->get('default-mid-validation-retarget'))
        ->toThrow(InvalidArgumentException::class, 'object-free reads')
        ->and($retargeted)->toBeTrue();
});

test('security cache rejects a SQLite database beneath a symlinked path component', function () {
    $root = realpath(storage_path()).'/framework/cache/sqlite-'.bin2hex(random_bytes(4));
    $realDirectory = $root.'/real';
    $linkedDirectory = $root.'/linked';
    mkdir($realDirectory, 0777, true);
    symlink($realDirectory, $linkedDirectory);
    $database = $linkedDirectory.'/security.sqlite';
    $pdo = new PDO('sqlite:'.$database);
    $pdo->exec('CREATE TABLE media_security_cache (key TEXT PRIMARY KEY, value TEXT NOT NULL, expiration INTEGER NOT NULL)');
    $pdo->exec('CREATE TABLE media_security_cache_locks (key TEXT PRIMARY KEY, owner TEXT NOT NULL, expiration INTEGER NOT NULL)');
    config()->set('database.connections.security-symlink', [
        'driver' => 'sqlite',
        'database' => $database,
        'prefix' => '',
    ]);
    config()->set('cache.stores.aura-media-security.connection', 'security-symlink');
    config()->set('cache.stores.aura-media-security.lock_connection', 'security-symlink');
    app('cache')->forgetDriver('aura-media-security');

    try {
        expect(fn () => app(MediaSecurityStore::class))
            ->toThrow(InvalidArgumentException::class, 'object-free reads');
    } finally {
        app('db')->purge('security-symlink');
        unlink($database);
        unlink($linkedDirectory);
        rmdir($realDirectory);
        rmdir($root);
    }
});

test('security cache revalidates SQLite device and inode before every operation', function () {
    $directory = realpath(storage_path()).'/framework/cache/sqlite-'.bin2hex(random_bytes(4));
    $database = $directory.'/security.sqlite';
    $movedDatabase = $directory.'/security-original.sqlite';
    mkdir($directory, 0777, true);
    $pdo = new PDO('sqlite:'.$database);
    $pdo->exec('CREATE TABLE media_security_cache (key TEXT PRIMARY KEY, value TEXT NOT NULL, expiration INTEGER NOT NULL)');
    $pdo->exec('CREATE TABLE media_security_cache_locks (key TEXT PRIMARY KEY, owner TEXT NOT NULL, expiration INTEGER NOT NULL)');
    config()->set('database.connections.security-physical', [
        'driver' => 'sqlite',
        'database' => $database,
        'prefix' => '',
    ]);
    config()->set('cache.stores.aura-media-security.connection', 'security-physical');
    config()->set('cache.stores.aura-media-security.lock_connection', 'security-physical');
    app('cache')->forgetDriver('aura-media-security');
    $store = app(MediaSecurityStore::class);
    rename($database, $movedDatabase);
    new PDO('sqlite:'.$database);

    try {
        expect(fn () => $store->get('retargeted-database'))
            ->toThrow(InvalidArgumentException::class, 'object-free reads');
    } finally {
        app('db')->purge('security-physical');
        unlink($database);
        unlink($movedDatabase);
        rmdir($directory);
    }
});

test('security cache rejects resolved store replacement before every operation', function () {
    $store = app(MediaSecurityStore::class);
    app('cache')->forgetDriver('aura-media-security');

    expect(fn () => $store->get('store-replacement'))
        ->toThrow(InvalidArgumentException::class, 'object-free reads');
});

test('security cache rejects every unsupported native driver before reading', function (string $storeClass, string $driver) {
    config()->set('cache.stores.aura-media-security.driver', $driver);
    $store = (new ReflectionClass($storeClass))->newInstanceWithoutConstructor();
    installCore20MediaRepository(new CacheRepository($store));

    expect(fn () => app(MediaSecurityStore::class))
        ->toThrow(InvalidArgumentException::class, 'pre-read boundary is not proven');
})->with([
    'Redis including native serializers and compression' => [RedisStore::class, 'redis'],
    'DynamoDB' => [DynamoDbStore::class, 'dynamodb'],
    'Memcached native deserialization' => [MemcachedStore::class, 'memcached'],
]);

test('security cache rejects proxied stores and repositories', function (bool $proxyRepository) {
    $directory = storage_path('framework/cache/data/media-proxy-'.bin2hex(random_bytes(4)));
    config()->set('cache.stores.aura-media-security.path', $directory);
    config()->set('cache.stores.aura-media-security.lock_path', $directory.'-locks');
    $store = $proxyRepository
        ? (new FileStore(app(Filesystem::class), $directory, null, false))->setLockDirectory($directory.'-locks')
        : (new Core20ProxiedFileStore(app(Filesystem::class), $directory, null, false))->setLockDirectory($directory.'-locks');
    $repository = $proxyRepository
        ? new Core20ProxiedCacheRepository($store)
        : new CacheRepository($store);
    installCore20MediaRepository($repository);

    expect(fn () => app(MediaSecurityStore::class))
        ->toThrow(InvalidArgumentException::class, 'proxied stores');
})->with([
    'proxied store' => false,
    'proxied repository' => true,
]);

test('security cache revalidates object-free mode before every operation', function (string $operation) {
    $store = app(MediaSecurityStore::class);
    config()->set('cache.serializable_classes', null);

    expect(fn () => match ($operation) {
        'add' => $store->add('boundary-add', 'value', 60),
        'forget' => $store->forget('boundary-forget'),
        'get' => $store->get('boundary-get'),
        'lock' => $store->lock('boundary-lock', 5),
        'put' => $store->put('boundary-put', 'value', 60),
    })->toThrow(InvalidArgumentException::class, 'object-free reads');
})->with(['add', 'forget', 'get', 'lock', 'put']);

test('security cache revalidates its boundary before an acquired lock operation', function () {
    $lock = app(MediaSecurityStore::class)->lock('boundary-lock', 5);
    config()->set('cache.serializable_classes', null);

    expect(fn () => $lock->get())
        ->toThrow(InvalidArgumentException::class, 'object-free reads');
});

test('security cache rejects configuration mutation before its next read', function () {
    config()->set('cache.serializable_classes', false);
    app('cache')->forgetDriver('aura-media-security');
    $owners = app(MediaOwnerTokenBroker::class);
    $selections = app(MediaSelectionBroker::class);
    $ownerToken = $owners->issue(
        ownerComponentId: 'configuration-owner',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'image',
        fieldType: Image::class,
        actor: $this->actor,
    );
    $request = $selections->begin($ownerToken, 'configuration-manager', ['9'], $this->actor);
    config()->set('cache.stores.aura-media-security.path', storage_path('framework/cache/data/mutated'));

    expect(fn () => $selections->forManager(
        $request->token,
        $ownerToken,
        'configuration-manager',
        $this->actor,
    ))->toThrow(InvalidMediaSelectionRequest::class);
});

test('database cache rejects serialized gadget rows before object lifecycle hooks run', function () {
    config()->set('cache.stores.aura-media-security', [
        'driver' => 'database',
        'connection' => 'media-security-testing',
        'table' => 'media_security_cache',
        'lock_connection' => 'media-security-testing',
        'lock_table' => 'media_security_cache_locks',
    ]);
    app('cache')->forgetDriver('aura-media-security');
    $ownerToken = app(MediaOwnerTokenBroker::class)->issue(
        ownerComponentId: 'database-deserialization-owner',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'image',
        fieldType: Image::class,
        actor: $this->actor,
    );
    $request = app(MediaSelectionBroker::class)->begin(
        $ownerToken,
        'database-deserialization-manager',
        ['9'],
        $this->actor,
    );
    $repository = app('cache')->store('aura-media-security');
    $store = $repository->getStore();

    expect($store)->toBeInstanceOf(DatabaseStore::class);

    $key = $store->getPrefix().'aura:media-selection:v1:request:'.hash('sha256', $request->token);
    $class = Core20CacheDeserializationProbe::class;
    $serializedGadget = sprintf('O:%d:"%s":0:{}', strlen($class), $class);
    $marker = storage_path('framework/cache/data/database-deserialization-probe-'.bin2hex(random_bytes(8)));
    Core20CacheDeserializationProbe::$marker = $marker;
    Core20CacheDeserializationProbe::$destructed = 0;
    Core20CacheDeserializationProbe::$woken = 0;

    try {
        DB::connection('media-security-testing')->table('media_security_cache')->where('key', $key)->update([
            'value' => $serializedGadget,
            'expiration' => time() + 120,
        ]);

        expect(fn () => app(MediaSelectionBroker::class)->forManager(
            $request->token,
            $ownerToken,
            'database-deserialization-manager',
            $this->actor,
        ))->toThrow(InvalidMediaSelectionRequest::class)
            ->and(Core20CacheDeserializationProbe::$woken)->toBe(0)
            ->and(Core20CacheDeserializationProbe::$destructed)->toBe(0)
            ->and(file_exists($marker))->toBeFalse();
    } finally {
        if (file_exists($marker)) {
            unlink($marker);
        }

        Core20CacheDeserializationProbe::$marker = null;
    }
});

test('media security configuration and docs publish the database-only boundary', function () {
    $configuration = file_get_contents(__DIR__.'/../../../config/aura.php');
    $componentDocs = file_get_contents(__DIR__.'/../../../docs/livewire-components.md');
    $managerDocs = file_get_contents(__DIR__.'/../../../docs/media-manager.md');

    expect($configuration)
        ->toContain('non-default Laravel database store')
        ->toContain('database children')
        ->toContain('unqualified lowercase')
        ->toContain('views, temporary tables, and synonyms fail closed')
        ->toContain('Multi-node deployments need one shared network database')
        ->and($componentDocs)
        ->toContain('exact built-in database store')
        ->toContain('connection table prefixes')
        ->toContain('same SQLite inode')
        ->toContain('validated write PDO instances')
        ->toContain('views, temporary tables, and synonyms fail closed')
        ->toContain('node-local SQLite')
        ->not->toContain('one shared file, database, Redis')
        ->and($managerDocs)
        ->toContain('exact built-in database store')
        ->toContain('connection table prefixes')
        ->toContain('same SQLite inode')
        ->toContain('validated write PDO instances')
        ->toContain('node-local SQLite')
        ->not->toContain('shared file, database, Redis');
});

test('native MySQL physical relation metadata is verified when configured', function () {
    if (getenv('CORE20_NATIVE_MYSQL_ENABLED') !== '1') {
        $this->markTestSkipped('CORE20 native MySQL is not configured.');
    }

    $password = getenv('CORE20_NATIVE_MYSQL_PASSWORD');

    verifyCore20NativeSecurityDatabase([
        'driver' => 'mysql',
        'host' => getenv('CORE20_NATIVE_MYSQL_HOST') ?: '127.0.0.1',
        'port' => getenv('CORE20_NATIVE_MYSQL_PORT') ?: '3306',
        'database' => getenv('CORE20_NATIVE_MYSQL_DATABASE') ?: 'aura_core20_security_test',
        'username' => getenv('CORE20_NATIVE_MYSQL_USERNAME') ?: 'root',
        'password' => is_string($password) ? $password : '',
        'prefix' => '',
    ]);
});

test('native MariaDB physical relation metadata is verified when configured', function () {
    if (getenv('CORE20_NATIVE_MARIADB_ENABLED') !== '1') {
        $this->markTestSkipped('CORE20 native MariaDB is not configured.');
    }

    $password = getenv('CORE20_NATIVE_MARIADB_PASSWORD');

    verifyCore20NativeSecurityDatabase([
        'driver' => 'mariadb',
        'host' => getenv('CORE20_NATIVE_MARIADB_HOST') ?: '127.0.0.1',
        'port' => getenv('CORE20_NATIVE_MARIADB_PORT') ?: '3306',
        'database' => getenv('CORE20_NATIVE_MARIADB_DATABASE') ?: 'aura_core20_security_test',
        'username' => getenv('CORE20_NATIVE_MARIADB_USERNAME') ?: 'root',
        'password' => is_string($password) ? $password : '',
        'prefix' => '',
    ]);
});

test('native PostgreSQL physical relation metadata is verified when configured', function () {
    if (getenv('CORE20_NATIVE_POSTGRES_ENABLED') !== '1') {
        $this->markTestSkipped('CORE20 native PostgreSQL is not configured.');
    }

    $password = getenv('CORE20_NATIVE_POSTGRES_PASSWORD');

    verifyCore20NativeSecurityDatabase([
        'driver' => 'pgsql',
        'host' => getenv('CORE20_NATIVE_POSTGRES_HOST') ?: '127.0.0.1',
        'port' => getenv('CORE20_NATIVE_POSTGRES_PORT') ?: '5432',
        'database' => getenv('CORE20_NATIVE_POSTGRES_DATABASE') ?: 'aura_core20_security_test',
        'username' => getenv('CORE20_NATIVE_POSTGRES_USERNAME') ?: 'postgres',
        'password' => is_string($password) ? $password : '',
        'prefix' => '',
    ]);
});
