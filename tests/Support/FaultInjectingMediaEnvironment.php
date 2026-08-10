<?php

namespace Aura\Base\Tests\Support;

use Aura\Base\Livewire\Media\MediaAuthorization;
use Aura\Base\Livewire\Media\MediaDetailsBroker;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\Media\MediaSecurityStore;
use Aura\Base\Livewire\Media\MediaSelectionBroker;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\Lock;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Encryption\StringEncrypter;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Foundation\Application;
use RuntimeException;

final class FaultInjectingMediaSecurityStore extends MediaSecurityStore
{
    /** @var list<array{operation: string, key: string, throws: bool}> */
    private array $failures = [];

    public function add(string $key, mixed $value, int $seconds): bool
    {
        if ($this->fails('add', (string) $key)) {
            return false;
        }

        return parent::add($key, $value, $seconds);
    }

    public function failNext(string $operation, string $key, bool $throws = false): void
    {
        $this->failures[] = compact('operation', 'key', 'throws');
    }

    public function forget(string $key): bool
    {
        if ($this->fails('forget', (string) $key)) {
            return false;
        }

        return parent::forget($key);
    }

    public function get(string $key, mixed $default = null): mixed
    {
        if ($this->fails('get', (string) $key)) {
            return null;
        }

        return parent::get($key, $default);
    }

    public function lock($name, $seconds = 0, $owner = null)
    {
        if ($this->fails('lock', (string) $name)) {
            return false;
        }

        $lock = parent::lock($name, $seconds, $owner);

        foreach ($this->failures as $index => $failure) {
            if ($failure['operation'] !== 'release' || ! str_contains((string) $name, $failure['key'])) {
                continue;
            }

            unset($this->failures[$index]);
            $this->failures = array_values($this->failures);

            return new FaultInjectingMediaLock($lock, $failure['throws']);
        }

        return $lock;
    }

    public function put(string $key, mixed $value, int $seconds): bool
    {
        if ($this->fails('put', (string) $key)) {
            return false;
        }

        return parent::put($key, $value, $seconds);
    }

    private function fails(string $operation, string $key): bool
    {
        foreach ($this->failures as $index => $failure) {
            if ($failure['operation'] !== $operation || ! str_contains($key, $failure['key'])) {
                continue;
            }

            unset($this->failures[$index]);
            $this->failures = array_values($this->failures);

            if ($failure['throws']) {
                throw new RuntimeException("Injected {$operation} failure for [{$key}].");
            }

            return true;
        }

        return false;
    }
}

final readonly class FaultInjectingMediaLock implements Lock
{
    public function __construct(
        private Lock $lock,
        private bool $throws,
    ) {}

    public function block($seconds, $callback = null): mixed
    {
        return $this->lock->block($seconds, $callback);
    }

    public function forceRelease(): void
    {
        $this->lock->forceRelease();
    }

    public function get($callback = null): mixed
    {
        return $this->lock->get($callback);
    }

    public function owner(): string
    {
        return $this->lock->owner();
    }

    public function release(): bool
    {
        $this->lock->release();

        if ($this->throws) {
            throw new RuntimeException('Injected lock release failure.');
        }

        return false;
    }
}

final readonly class FixedMediaCacheFactory implements CacheFactory
{
    public function __construct(private CacheRepository $repository) {}

    public function store($name = null): CacheRepository
    {
        return $this->repository;
    }
}

final readonly class FaultInjectingMediaEnvironment
{
    public function __construct(
        public FaultInjectingMediaSecurityStore $store,
        public MediaOwnerTokenBroker $owners,
        public MediaSelectionBroker $selections,
        public MediaDetailsBroker $details,
    ) {}

    public static function install(Application $app): self
    {
        $directory = $app->storagePath('framework/cache/data/media-faults-'.bin2hex(random_bytes(6)));
        $fileStore = (new FileStore(
            $app->make(Filesystem::class),
            $directory,
            null,
            false,
        ))->setLockDirectory($directory.'-locks');
        $repository = new CacheRepository($fileStore);
        $config = $app->make(ConfigRepository::class);
        $config->set('cache.serializable_classes', false);
        $config->set('cache.stores.aura-media-security.path', $directory);
        $config->set('cache.stores.aura-media-security.lock_path', $directory.'-locks');
        $security = new FaultInjectingMediaSecurityStore(new FixedMediaCacheFactory($repository), $config);
        $owners = new MediaOwnerTokenBroker(
            $security,
            $config,
            $app->make(StringEncrypter::class),
        );

        $app->instance(MediaSecurityStore::class, $security);
        $app->instance(MediaOwnerTokenBroker::class, $owners);
        $app->forgetInstance(MediaAuthorization::class);

        $authorization = $app->make(MediaAuthorization::class);
        $selections = new MediaSelectionBroker(
            $security,
            $config,
            $owners,
            $app->make(StringEncrypter::class),
        );
        $details = new MediaDetailsBroker($security, $config, $owners);

        $app->instance(MediaAuthorization::class, $authorization);
        $app->instance(MediaSelectionBroker::class, $selections);
        $app->instance(MediaDetailsBroker::class, $details);

        return new self($security, $owners, $selections, $details);
    }
}
