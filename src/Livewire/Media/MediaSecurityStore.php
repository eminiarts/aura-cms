<?php

namespace Aura\Base\Livewire\Media;

use Illuminate\Cache\DatabaseStore;
use Illuminate\Cache\DynamoDbStore;
use Illuminate\Cache\FileStore;
use Illuminate\Cache\MemcachedStore;
use Illuminate\Cache\RedisStore;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use InvalidArgumentException;

final class MediaSecurityStore
{
    public readonly CacheRepository $cache;

    public readonly LockProvider $locks;

    public function __construct(CacheFactory $cache, ConfigRepository $config)
    {
        $repository = $cache->store($config->get('aura.media.security.cache_store'));
        $store = $repository instanceof CacheRepository ? $repository->getStore() : null;

        if (! $repository instanceof CacheRepository
            || ! $store instanceof LockProvider
            || ! is_a($store, FileStore::class)
                && ! is_a($store, DatabaseStore::class)
                && ! is_a($store, RedisStore::class)
                && ! is_a($store, MemcachedStore::class)
                && ! is_a($store, DynamoDbStore::class)) {
            throw new InvalidArgumentException(
                'Aura media security requires a shared file, database, Redis, Memcached, or DynamoDB cache store with atomic locks; process-local stores are unsafe.',
            );
        }

        $this->cache = $repository;
        $this->locks = $store;
    }
}
