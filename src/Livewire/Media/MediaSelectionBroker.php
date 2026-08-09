<?php

namespace Aura\Base\Livewire\Media;

use Closure;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use InvalidArgumentException;
use Throwable;

class MediaSelectionBroker
{
    private const CACHE_PREFIX = 'aura:media-selection:v1:';

    private readonly CacheRepository $cache;

    private readonly LockProvider $locks;

    public function __construct(
        CacheFactory $cache,
        private readonly ConfigRepository $config,
        private readonly MediaOwnerTokenBroker $owners,
    ) {
        $store = $cache->store($this->config->get('aura.media.security.cache_store'));

        if (! $store instanceof CacheRepository || ! $store->getStore() instanceof LockProvider) {
            throw new InvalidArgumentException('Aura media security requires a cache store with atomic lock support.');
        }

        $this->cache = $store;
        $this->locks = $store->getStore();
    }

    /** @param list<int|string> $value */
    public function begin(
        string $ownerToken,
        string $managerComponentId,
        array $value,
        Authenticatable $actor,
    ): MediaSelectionRequest {
        if ($managerComponentId === '' || strlen($managerComponentId) > 255) {
            throw new InvalidArgumentException('Media manager component ID must be non-empty and at most 255 bytes.');
        }

        $normalized = $this->normalizeValue($value);
        $owner = $this->owners->resolve($ownerToken, $actor);
        $issuedAt = now()->getTimestamp();
        $deadline = $issuedAt + $this->ttl();
        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $digest = $this->digest($token);
        $record = new MediaSelectionRecord(
            requestDigest: $digest,
            ownerTokenDigest: $this->owners->digest($ownerToken),
            managerComponentId: $managerComponentId,
            ownerComponentId: $owner->ownerComponentId,
            actorId: $owner->actorId,
            teamId: $owner->teamId,
            slug: $owner->slug,
            valueDigest: $this->valueDigest($normalized),
            issuedAt: $issuedAt,
            deadline: $deadline,
            state: 'pending',
            errorCode: null,
        );

        if (! $this->cache->add($this->recordKey($token), $record->toArray(), $this->ttl() + $this->retention())) {
            throw new InvalidMediaSelectionRequest('Unable to create a unique media selection request.');
        }

        return new MediaSelectionRequest($token, $digest, $record);
    }

    public function expireForManager(
        string $requestToken,
        string $ownerToken,
        string $managerComponentId,
        Authenticatable $actor,
    ): MediaSelectionRecord {
        return $this->withLock($requestToken, function () use ($requestToken, $ownerToken, $managerComponentId, $actor): MediaSelectionRecord {
            $record = $this->validatedForManager($requestToken, $ownerToken, $managerComponentId, $actor);

            if (in_array($record->state, ['succeeded', 'failed', 'expired'], true)
                || now()->getTimestamp() < $record->deadline) {
                return $record;
            }

            $expired = $record->withState('expired', 'selection_timeout');
            $this->store($requestToken, $expired);

            return $expired;
        });
    }

    public function forManager(
        string $requestToken,
        string $ownerToken,
        string $managerComponentId,
        Authenticatable $actor,
    ): MediaSelectionRecord {
        return $this->validatedForManager($requestToken, $ownerToken, $managerComponentId, $actor);
    }

    /**
     * @param  list<int|string>  $value
     * @param  Closure(): void  $mutation
     */
    public function processForOwner(
        string $requestToken,
        string $ownerToken,
        string $ownerComponentId,
        string $slug,
        array $value,
        Authenticatable $actor,
        Closure $mutation,
    ): MediaSelectionRecord {
        return $this->withLock($requestToken, function () use (
            $requestToken,
            $ownerToken,
            $ownerComponentId,
            $slug,
            $value,
            $actor,
            $mutation,
        ): MediaSelectionRecord {
            $record = $this->validatedForOwner(
                $requestToken,
                $ownerToken,
                $ownerComponentId,
                $slug,
                $value,
                $actor,
            );

            if (in_array($record->state, ['succeeded', 'failed', 'expired'], true)) {
                return $record;
            }

            if (now()->getTimestamp() >= $record->deadline) {
                $expired = $record->withState('expired', 'selection_timeout');
                $this->store($requestToken, $expired);

                return $expired;
            }

            if ($record->state !== 'pending') {
                throw new InvalidMediaSelectionRequest('The media selection request is not claimable.');
            }

            $processing = $record->withState('processing');
            $this->store($requestToken, $processing);

            try {
                $mutation();
                $settled = $processing->withState('succeeded');
            } catch (MediaSelectionRejected $exception) {
                $settled = $processing->withState('failed', $this->errorCode($exception->errorCode));
            } catch (Throwable) {
                $settled = $processing->withState('failed', 'processing_failed');
            }

            $this->store($requestToken, $settled);

            return $settled;
        });
    }

    private function digest(string $token): string
    {
        return hash('sha256', $token);
    }

    private function errorCode(string $errorCode): string
    {
        return preg_match('/^[a-z][a-z0-9_]{0,63}$/', $errorCode) === 1
            ? $errorCode
            : 'selection_rejected';
    }

    /**
     * @param  list<int|string>  $value
     * @return list<string>
     */
    private function normalizeValue(array $value): array
    {
        if (! array_is_list($value)) {
            throw new InvalidArgumentException('Media selection values must be a list.');
        }

        $normalized = [];

        foreach ($value as $id) {
            if ((! is_int($id) && ! is_string($id)) || (string) $id === '') {
                throw new InvalidArgumentException('Media selection values must contain non-empty integer or string IDs.');
            }

            $normalized[] = (string) $id;
        }

        if (count($normalized) !== count(array_unique($normalized, SORT_STRING))) {
            throw new InvalidArgumentException('Media selection values must not contain duplicate IDs.');
        }

        return $normalized;
    }

    private function read(string $requestToken): MediaSelectionRecord
    {
        if (preg_match('/^[A-Za-z0-9_-]{43}$/', $requestToken) !== 1) {
            throw new InvalidMediaSelectionRequest('The media selection request is invalid.');
        }

        $stored = $this->cache->get($this->recordKey($requestToken));

        if (! is_array($stored)) {
            throw new InvalidMediaSelectionRequest('The media selection request is invalid.');
        }

        try {
            $record = MediaSelectionRecord::fromArray($stored);
        } catch (Throwable) {
            throw new InvalidMediaSelectionRequest('The media selection request is invalid.');
        }

        if (! hash_equals($record->requestDigest, $this->digest($requestToken))) {
            throw new InvalidMediaSelectionRequest('The media selection request is invalid.');
        }

        return $record;
    }

    private function recordKey(string $requestToken): string
    {
        return self::CACHE_PREFIX.'request:'.$this->digest($requestToken);
    }

    private function resolveOwner(string $ownerToken, Authenticatable $actor): MediaOwnerContext
    {
        try {
            return $this->owners->resolve($ownerToken, $actor);
        } catch (InvalidMediaOwnerToken) {
            throw new InvalidMediaSelectionRequest('The media selection owner token is invalid.');
        }
    }

    private function retention(): int
    {
        $retention = $this->config->get('aura.media.security.selection_retention', 60);

        if (! is_int($retention) || $retention < 1 || $retention > 3600) {
            throw new InvalidArgumentException('Aura media selection retention must be an integer from 1 through 3600 seconds.');
        }

        return $retention;
    }

    private function store(string $requestToken, MediaSelectionRecord $record): void
    {
        $seconds = max(1, $record->deadline - now()->getTimestamp() + $this->retention());
        $this->cache->put($this->recordKey($requestToken), $record->toArray(), $seconds);
    }

    private function ttl(): int
    {
        $ttl = $this->config->get('aura.media.security.selection_ttl', 15);

        if (! is_int($ttl) || $ttl < 1 || $ttl > 300) {
            throw new InvalidArgumentException('Aura media selection TTL must be an integer from 1 through 300 seconds.');
        }

        return $ttl;
    }

    private function validatedForManager(
        string $requestToken,
        string $ownerToken,
        string $managerComponentId,
        Authenticatable $actor,
    ): MediaSelectionRecord {
        $record = $this->read($requestToken);
        $owner = $this->resolveOwner($ownerToken, $actor);

        if (! hash_equals($record->ownerTokenDigest, $this->owners->digest($ownerToken))
            || ! hash_equals($record->managerComponentId, $managerComponentId)
            || ! hash_equals($record->actorId, $owner->actorId)
            || $record->teamId !== $owner->teamId) {
            throw new InvalidMediaSelectionRequest('The media selection request context does not match.');
        }

        return $record;
    }

    /** @param list<int|string> $value */
    private function validatedForOwner(
        string $requestToken,
        string $ownerToken,
        string $ownerComponentId,
        string $slug,
        array $value,
        Authenticatable $actor,
    ): MediaSelectionRecord {
        $record = $this->read($requestToken);
        $owner = $this->resolveOwner($ownerToken, $actor);

        if (! hash_equals($record->ownerTokenDigest, $this->owners->digest($ownerToken))
            || ! hash_equals($record->ownerComponentId, $ownerComponentId)
            || ! hash_equals($record->ownerComponentId, $owner->ownerComponentId)
            || ! hash_equals($record->slug, $slug)
            || ! hash_equals($record->slug, $owner->slug)
            || ! hash_equals($record->actorId, $owner->actorId)
            || $record->teamId !== $owner->teamId
            || ! hash_equals($record->valueDigest, $this->valueDigest($this->normalizeValue($value)))) {
            throw new InvalidMediaSelectionRequest('The media selection request context does not match.');
        }

        return $record;
    }

    /** @param list<string> $value */
    private function valueDigest(array $value): string
    {
        return hash('sha256', json_encode($value, JSON_THROW_ON_ERROR));
    }

    /** @param Closure(): MediaSelectionRecord $callback */
    private function withLock(string $requestToken, Closure $callback): MediaSelectionRecord
    {
        return $this->locks->lock(self::CACHE_PREFIX.'lock:'.$this->digest($requestToken), 10)->block(5, $callback);
    }
}
