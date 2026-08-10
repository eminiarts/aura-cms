<?php

use Aura\Base\Fields\Image;
use Aura\Base\Livewire\Media\InvalidMediaSelectionRequest;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\Media\MediaSecurityStore;
use Aura\Base\Livewire\Media\MediaSelectionBroker;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\DynamoDbStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
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
    $directory = storage_path('framework/cache/data/media-unsafe-resolved');
    $store = (new FileStore(app(Filesystem::class), $directory, null, null))
        ->setLockDirectory($directory.'-locks');

    expect(fn () => new MediaSecurityStore(
        new Core20FixedMediaCacheFactory(new CacheRepository($store)),
        app('config'),
    ))->toThrow(InvalidArgumentException::class, 'object-free reads');
});

test('security cache rejects every unsupported native driver before reading', function (string $storeClass, string $driver) {
    config()->set('cache.stores.aura-media-security.driver', $driver);
    $store = (new ReflectionClass($storeClass))->newInstanceWithoutConstructor();

    expect(fn () => new MediaSecurityStore(
        new Core20FixedMediaCacheFactory(new CacheRepository($store)),
        app('config'),
    ))->toThrow(InvalidArgumentException::class, 'pre-read boundary is not proven');
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

    expect(fn () => new MediaSecurityStore(
        new Core20FixedMediaCacheFactory($repository),
        app('config'),
    ))->toThrow(InvalidArgumentException::class, 'custom, and proxied stores');
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

test('file cache rejects serialized gadget bytes before object lifecycle hooks run', function () {
    $ownerToken = app(MediaOwnerTokenBroker::class)->issue(
        ownerComponentId: 'deserialization-owner',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'image',
        fieldType: Image::class,
        actor: $this->actor,
    );
    $request = app(MediaSelectionBroker::class)->begin(
        $ownerToken,
        'deserialization-manager',
        ['9'],
        $this->actor,
    );
    $repository = app('cache')->store(config('aura.media.security.cache_store'));
    $store = $repository->getStore();

    expect($store)->toBeInstanceOf(FileStore::class);

    $key = 'aura:media-selection:v1:request:'.hash('sha256', $request->token);
    $class = Core20CacheDeserializationProbe::class;
    $serializedGadget = sprintf('O:%d:"%s":0:{}', strlen($class), $class);
    $marker = storage_path('framework/cache/data/deserialization-probe-'.bin2hex(random_bytes(8)));
    Core20CacheDeserializationProbe::$marker = $marker;
    Core20CacheDeserializationProbe::$destructed = 0;
    Core20CacheDeserializationProbe::$woken = 0;

    try {
        file_put_contents(
            $store->path($key),
            str_pad((string) (time() + 120), 10, '0', STR_PAD_LEFT).$serializedGadget,
        );

        expect(fn () => app(MediaSelectionBroker::class)->forManager(
            $request->token,
            $ownerToken,
            'deserialization-manager',
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

test('database cache rejects serialized gadget rows before object lifecycle hooks run', function () {
    Schema::create('media_security_cache', function ($table) {
        $table->string('key')->primary();
        $table->mediumText('value');
        $table->integer('expiration');
    });
    Schema::create('media_security_cache_locks', function ($table) {
        $table->string('key')->primary();
        $table->string('owner');
        $table->integer('expiration');
    });
    config()->set('cache.stores.aura-media-security', [
        'driver' => 'database',
        'connection' => 'testing',
        'table' => 'media_security_cache',
        'lock_connection' => 'testing',
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
        DB::connection('testing')->table('media_security_cache')->where('key', $key)->update([
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
