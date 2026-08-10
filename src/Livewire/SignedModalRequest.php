<?php

namespace Aura\Base\Livewire;

use Aura\Base\Livewire\Table\TableMutationDispatcher;
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Contracts\Encryption\Encrypter;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

final class SignedModalRequest
{
    private const PREFIX = 'aura-modal:';

    public function __construct(
        private readonly CacheManager $cacheManager,
        private readonly Encrypter $encrypter,
        private readonly TableMutationDispatcher $mutations,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function issue(array $context): string
    {
        $ttl = $this->ttl();
        $nonce = Str::random(64);
        $expiresAt = now()->addSeconds($ttl)->getTimestamp();
        $cache = $this->cache();

        if (! $cache->put($this->contextKey($nonce), $context, $ttl)) {
            abort(503, 'The modal request could not be stored securely.');
        }

        return self::PREFIX.$this->encrypter->encrypt([
            'expires_at' => $expiresAt,
            'nonce' => $nonce,
            'team_id' => data_get(Auth::user(), 'current_team_id'),
            'user_id' => Auth::id(),
        ]);
    }

    /**
     * @return array{arguments: array<string, mixed>, component: string, modalAttributes: array<string, mixed>}
     */
    public function resolve(string $request): array
    {
        if (! str_starts_with($request, self::PREFIX)) {
            abort(422, 'The modal request is invalid.');
        }

        try {
            $payload = $this->encrypter->decrypt(substr($request, strlen(self::PREFIX)));
        } catch (DecryptException) {
            abort(422, 'The modal request is invalid.');
        }

        if (
            ! is_array($payload)
            || array_keys($payload) !== [
                'expires_at',
                'nonce',
                'team_id',
                'user_id',
            ]
            || ! is_int($payload['expires_at'])
            || ! is_string($payload['nonce'])
            || strlen($payload['nonce']) !== 64
            || $payload['expires_at'] <= now()->getTimestamp()
            || (string) $payload['user_id'] !== (string) Auth::id()
            || (string) $payload['team_id'] !== (string) data_get(Auth::user(), 'current_team_id')
        ) {
            abort(422, 'The modal request is invalid.');
        }

        $cache = $this->cache();
        $remainingLifetime = max(1, $payload['expires_at'] - now()->getTimestamp());

        if (! $cache->add($this->consumedKey($payload['nonce']), true, $remainingLifetime)) {
            abort(422, 'The modal request was already consumed.');
        }

        $context = $cache->pull($this->contextKey($payload['nonce']));

        if (! is_array($context)) {
            abort(422, 'The modal request is invalid or expired.');
        }

        return $this->mutations->redeemBulkModal($context);
    }

    public function supports(string $request): bool
    {
        return str_starts_with($request, self::PREFIX);
    }

    private function cache(): CacheRepository
    {
        $storeName = config('aura.security.modal_requests.cache_store');

        if (! is_string($storeName) || $storeName === '') {
            abort(503, 'A shared modal request cache store is required.');
        }

        $cache = $this->cacheManager->store($storeName);

        if (! $cache instanceof Repository) {
            abort(503, 'The configured modal request cache repository is invalid.');
        }

        $store = $cache->getStore();

        if ($store instanceof ArrayStore || $store instanceof NullStore) {
            abort(503, 'The configured modal request cache store is not shared.');
        }

        return $cache;
    }

    private function consumedKey(string $nonce): string
    {
        return 'aura:modal-request:consumed:'.$nonce;
    }

    private function contextKey(string $nonce): string
    {
        return 'aura:modal-request:context:'.$nonce;
    }

    private function ttl(): int
    {
        $ttl = config('aura.security.modal_requests.ttl_seconds', 120);

        if (! is_int($ttl) || $ttl < 1 || $ttl > 600) {
            abort(503, 'The modal request lifetime is invalid.');
        }

        return $ttl;
    }
}
