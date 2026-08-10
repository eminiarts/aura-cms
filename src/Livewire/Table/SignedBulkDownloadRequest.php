<?php

namespace Aura\Base\Livewire\Table;

use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\CacheManager;
use Illuminate\Cache\NullStore;
use Illuminate\Cache\Repository;
use Illuminate\Contracts\Cache\Repository as CacheRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

final class SignedBulkDownloadRequest
{
    public function __construct(
        private readonly CacheManager $cacheManager,
        private readonly TableMutationDispatcher $mutations,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function issue(array $context): string
    {
        $ttl = $this->ttl();
        $token = Str::random(64);
        $stored = [
            'context' => $context,
            'team_id' => data_get(Auth::user(), 'current_team_id'),
            'user_id' => Auth::id(),
        ];

        if (! $this->cache()->put($this->contextKey($token), $stored, $ttl)) {
            abort(503, 'The bulk download request could not be stored securely.');
        }

        return URL::temporarySignedRoute(
            'aura.bulk-download',
            now()->addSeconds($ttl),
            ['token' => $token],
        );
    }

    public function resolve(string $token): StreamedResponse
    {
        if (preg_match('/\A[A-Za-z0-9]{64}\z/', $token) !== 1) {
            abort(422, 'The bulk download request is invalid.');
        }

        $cache = $this->cache();

        if (! $cache->add($this->consumedKey($token), true, $this->ttl())) {
            abort(422, 'The bulk download request was already consumed.');
        }

        $stored = $cache->pull($this->contextKey($token));

        if (
            ! is_array($stored)
            || array_keys($stored) !== ['context', 'team_id', 'user_id']
            || ! is_array($stored['context'])
            || (string) $stored['user_id'] !== (string) Auth::id()
            || (string) $stored['team_id'] !== (string) data_get(Auth::user(), 'current_team_id')
        ) {
            abort(422, 'The bulk download request is invalid or expired.');
        }

        return $this->mutations->streamBulkDownload($stored['context']);
    }

    private function cache(): CacheRepository
    {
        $storeName = config('aura.security.bulk_downloads.cache_store');

        if (! is_string($storeName) || $storeName === '') {
            abort(503, 'A shared bulk download cache store is required.');
        }

        $cache = $this->cacheManager->store($storeName);

        if (! $cache instanceof Repository) {
            abort(503, 'The configured bulk download cache repository is invalid.');
        }

        if ($cache->getStore() instanceof ArrayStore || $cache->getStore() instanceof NullStore) {
            abort(503, 'The configured bulk download cache store is not shared.');
        }

        return $cache;
    }

    private function consumedKey(string $token): string
    {
        return 'aura:bulk-download:consumed:'.$token;
    }

    private function contextKey(string $token): string
    {
        return 'aura:bulk-download:context:'.$token;
    }

    private function ttl(): int
    {
        $ttl = config('aura.security.bulk_downloads.ttl_seconds', 120);

        if (! is_int($ttl) || $ttl < 1 || $ttl > 600) {
            abort(503, 'The bulk download request lifetime is invalid.');
        }

        return $ttl;
    }
}
