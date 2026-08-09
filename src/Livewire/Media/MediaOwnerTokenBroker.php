<?php

namespace Aura\Base\Livewire\Media;

use Aura\Base\Resource;
use Illuminate\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\Factory as CacheFactory;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Encryption\StringEncrypter;
use InvalidArgumentException;
use JsonException;
use ReflectionClass;
use Throwable;

class MediaOwnerTokenBroker
{
    private const CACHE_PREFIX = 'aura:media-owner:v1:';

    private readonly CacheRepository $cache;

    private readonly LockProvider $locks;

    public function __construct(
        CacheFactory $cache,
        private readonly ConfigRepository $config,
        private readonly StringEncrypter $encrypter,
    ) {
        $store = $cache->store($this->config->get('aura.media.security.cache_store'));

        if (! $store instanceof CacheRepository || ! $store->getStore() instanceof LockProvider) {
            throw new InvalidArgumentException('Aura media security requires a cache store with atomic lock support.');
        }

        $this->cache = $store;
        $this->locks = $store->getStore();
    }

    public function digest(string $token): string
    {
        return hash('sha256', $token);
    }

    /**
     * @param  class-string<resource>  $modelClass
     */
    public function issue(
        string $ownerComponentId,
        string $modelClass,
        ?string $modelKey,
        string $action,
        string $slug,
        Authenticatable $actor,
    ): string {
        $actorId = $this->actorId($actor);
        $teamId = $this->teamId($actor);
        $this->validateIssueContext($ownerComponentId, $modelClass, $modelKey, $action, $slug);

        $fingerprint = hash('sha256', json_encode([
            $ownerComponentId,
            $modelClass,
            $modelKey,
            $action,
            $slug,
            $actorId,
            $teamId,
        ], JSON_THROW_ON_ERROR));
        $indexKey = self::CACHE_PREFIX.'index:'.$fingerprint;

        return $this->locks->lock($indexKey.':lock', 5)->block(5, function () use (
            $indexKey,
            $ownerComponentId,
            $modelClass,
            $modelKey,
            $action,
            $slug,
            $actor,
            $actorId,
            $teamId,
        ): string {
            $existing = $this->cache->get($indexKey);

            if (is_string($existing)) {
                try {
                    $this->resolve($existing, $actor);

                    return $existing;
                } catch (InvalidMediaOwnerToken) {
                    $this->cache->forget($indexKey);
                }
            }

            $issuedAt = now()->getTimestamp();
            $ttl = $this->ttl();
            $context = new MediaOwnerContext(
                ownerComponentId: $ownerComponentId,
                modelClass: $modelClass,
                modelKey: $modelKey,
                action: $action,
                slug: $slug,
                actorId: $actorId,
                teamId: $teamId,
                nonce: bin2hex(random_bytes(32)),
                issuedAt: $issuedAt,
                deadline: $issuedAt + $ttl,
            );
            $token = $this->encode($this->encrypter->encryptString(json_encode($context->toArray(), JSON_THROW_ON_ERROR)));

            $this->cache->put($this->tokenKey($token), $context->toArray(), $ttl);
            $this->cache->put($indexKey, $token, $ttl);

            return $token;
        });
    }

    public function resolve(string $token, Authenticatable $actor): MediaOwnerContext
    {
        try {
            $json = $this->encrypter->decryptString($this->decode($token));
            $payload = json_decode($json, true, flags: JSON_THROW_ON_ERROR);
        } catch (Throwable) {
            throw new InvalidMediaOwnerToken('The media owner token is invalid.');
        }

        if (! is_array($payload) || ! $this->validPayloadShape($payload)) {
            throw new InvalidMediaOwnerToken('The media owner token is invalid.');
        }

        $cached = $this->cache->get($this->tokenKey($token));

        if (! is_array($cached)
            || ! hash_equals($this->payloadDigest($payload), $this->payloadDigest($cached))
            || $payload['deadline'] < now()->getTimestamp()
            || ! hash_equals($payload['actor_id'], $this->actorId($actor))
            || $payload['team_id'] !== $this->teamId($actor)) {
            throw new InvalidMediaOwnerToken('The media owner token is invalid.');
        }

        return MediaOwnerContext::fromArray($payload);
    }

    private function actorId(Authenticatable $actor): string
    {
        $identifier = $actor->getAuthIdentifier();

        if (! is_int($identifier) && ! is_string($identifier)) {
            throw new InvalidArgumentException('Media owner tokens require a scalar authenticated actor identifier.');
        }

        $identifier = (string) $identifier;

        if ($identifier === '') {
            throw new InvalidArgumentException('Media owner tokens require an authenticated actor identifier.');
        }

        return $identifier;
    }

    private function decode(string $token): string
    {
        if ($token === '' || preg_match('/^[A-Za-z0-9_-]+$/', $token) !== 1) {
            throw new InvalidMediaOwnerToken('The media owner token is invalid.');
        }

        $decoded = base64_decode(strtr($token, '-_', '+/').str_repeat('=', (4 - strlen($token) % 4) % 4), true);

        if (! is_string($decoded)) {
            throw new InvalidMediaOwnerToken('The media owner token is invalid.');
        }

        return $decoded;
    }

    private function encode(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }

    /** @param array<string, mixed> $payload */
    private function payloadDigest(array $payload): string
    {
        try {
            return hash('sha256', json_encode($payload, JSON_THROW_ON_ERROR));
        } catch (JsonException) {
            throw new InvalidMediaOwnerToken('The media owner token is invalid.');
        }
    }

    private function teamId(Authenticatable $actor): ?string
    {
        if (! $this->config->get('aura.teams', true)) {
            return null;
        }

        $teamId = data_get($actor, 'current_team_id');

        if (! is_int($teamId) && ! is_string($teamId)) {
            throw new InvalidArgumentException('Media owner tokens require a current team when teams are enabled.');
        }

        $teamId = (string) $teamId;

        if ($teamId === '') {
            throw new InvalidArgumentException('Media owner tokens require a current team when teams are enabled.');
        }

        return $teamId;
    }

    private function tokenKey(string $token): string
    {
        return self::CACHE_PREFIX.'token:'.$this->digest($token);
    }

    private function ttl(): int
    {
        $ttl = $this->config->get('aura.media.security.owner_token_ttl', 900);

        if (! is_int($ttl) || $ttl < 1 || $ttl > 3600) {
            throw new InvalidArgumentException('Aura media owner token TTL must be an integer from 1 through 3600 seconds.');
        }

        return $ttl;
    }

    /**
     * @param  class-string<resource>  $modelClass
     */
    private function validateIssueContext(
        string $ownerComponentId,
        string $modelClass,
        ?string $modelKey,
        string $action,
        string $slug,
    ): void {
        if ($ownerComponentId === '' || strlen($ownerComponentId) > 255) {
            throw new InvalidArgumentException('Media owner component ID must be non-empty and at most 255 bytes.');
        }

        if (! class_exists($modelClass) || ! is_subclass_of($modelClass, Resource::class)
            || (new ReflectionClass($modelClass))->getName() !== $modelClass) {
            throw new InvalidArgumentException('Media owner model must be a canonical Aura Resource class.');
        }

        if (! in_array($action, ['create', 'update'], true)
            || ($action === 'create' && $modelKey !== null)
            || ($action === 'update' && ($modelKey === null || $modelKey === ''))) {
            throw new InvalidArgumentException('Media owner action and model key are inconsistent.');
        }

        if ($slug === '' || strlen($slug) > 255) {
            throw new InvalidArgumentException('Media owner field slug must be non-empty and at most 255 bytes.');
        }
    }

    /** @param array<string, mixed> $payload */
    private function validPayloadShape(array $payload): bool
    {
        $expected = [
            'owner_component_id',
            'model_class',
            'model_key',
            'action',
            'slug',
            'actor_id',
            'team_id',
            'nonce',
            'issued_at',
            'deadline',
        ];

        if (array_keys($payload) !== $expected) {
            return false;
        }

        return is_string($payload['owner_component_id'])
            && is_string($payload['model_class'])
            && ($payload['model_key'] === null || is_string($payload['model_key']))
            && in_array($payload['action'], ['create', 'update'], true)
            && is_string($payload['slug'])
            && is_string($payload['actor_id'])
            && ($payload['team_id'] === null || is_string($payload['team_id']))
            && is_string($payload['nonce'])
            && strlen($payload['nonce']) === 64
            && is_int($payload['issued_at'])
            && is_int($payload['deadline']);
    }
}
