<?php

namespace Aura\Base\Livewire\Media;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Cache\LockProvider;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Contracts\Encryption\StringEncrypter;
use InvalidArgumentException;
use Throwable;

class MediaSelectionBroker
{
    private const CACHE_PREFIX = 'aura:media-selection:v1:';

    private const ENVELOPE_PURPOSE = 'aura-media-selection';

    private const ENVELOPE_VERSION = 1;

    private readonly MediaSecurityStore $cache;

    private readonly LockProvider $locks;

    public function __construct(
        MediaSecurityStore $store,
        private readonly ConfigRepository $config,
        private readonly MediaOwnerTokenBroker $owners,
        private readonly StringEncrypter $encrypter,
    ) {
        $this->cache = $store->cache;
        $this->locks = $store->locks;
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
        $owner = $this->resolveOwner($ownerToken, $actor);
        $this->authorizeOwnerSelection($ownerToken, $normalized, $actor);
        $scopeKey = $this->scopeKey($ownerToken, $managerComponentId);

        return $this->withNamedLock($scopeKey.':lock', 10, function () use ($normalized, $owner, $ownerToken, $managerComponentId, $scopeKey): MediaSelectionRequest {
            $activeToken = $this->cache->get($scopeKey);

            if ($activeToken !== null) {
                if (! is_string($activeToken)) {
                    throw new InvalidMediaSelectionRequest('The active media selection scope is invalid.');
                }

                try {
                    $active = $this->read($activeToken);
                } catch (InvalidMediaSelectionRequest) {
                    throw new InvalidMediaSelectionRequest('The active media selection scope is unavailable.');
                }

                if (in_array($active->state, ['pending', 'processing'], true)) {
                    $indexKey = $this->ownerIndexKey($ownerToken);

                    return $this->withNamedLock($indexKey.':lock', 5, function () use (
                        $indexKey,
                        $activeToken,
                        $active,
                        $ownerToken,
                        $managerComponentId,
                        $normalized,
                        $owner,
                    ): MediaSelectionRequest {
                        if ($this->ownerRequestTokens($indexKey) !== [$activeToken]) {
                            throw new InvalidMediaSelectionRequest('The active media selection owner fence is invalid.');
                        }

                        return $this->recoverActiveRequest(
                            $activeToken,
                            $active,
                            $ownerToken,
                            $managerComponentId,
                            $normalized,
                            $owner,
                        );
                    });
                }
            }

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
                generation: 0,
                issuedAt: $issuedAt,
                deadline: $deadline,
                state: 'pending',
                errorCode: null,
                claimId: null,
                claimedAt: null,
                completedAt: null,
            );

            $indexKey = $this->ownerIndexKey($ownerToken);

            return $this->withNamedLock($indexKey.':lock', 5, function () use (
                $indexKey,
                $token,
                $record,
                $scopeKey,
                $digest,
                $activeToken,
                $ownerToken,
                $owner,
            ): MediaSelectionRequest {
                $previousTokens = $this->ownerRequestTokens($indexKey);
                $this->assertOwnerRequestsSettled($previousTokens, $ownerToken, $owner);
                $indexAttempted = false;
                $recordStored = false;
                $scopeAttempted = false;

                try {
                    $indexAttempted = true;
                    $this->putOrFail(
                        $indexKey,
                        [$token],
                        $this->ttl() + $this->retention(),
                    );
                    $this->addOrFail(
                        $this->recordKey($token),
                        $this->sealRecord($token, $record),
                        $this->ttl() + $this->retention(),
                    );
                    $recordStored = true;
                    $scopeAttempted = true;
                    $this->putOrFail($scopeKey, $token, $this->ttl() + $this->retention());
                } catch (Throwable $exception) {
                    $this->rollbackBegin(
                        $indexKey,
                        $previousTokens,
                        $token,
                        $scopeKey,
                        $activeToken,
                        $indexAttempted,
                        $recordStored,
                        $scopeAttempted,
                    );

                    if ($exception instanceof InvalidMediaSelectionRequest) {
                        throw $exception;
                    }

                    throw new InvalidMediaSelectionRequest(
                        'Unable to create a durable media selection request.',
                        previous: $exception,
                    );
                }

                return new MediaSelectionRequest($token, $digest, $record);
            });
        });
    }

    public function expireForManager(
        string $requestToken,
        string $ownerToken,
        string $managerComponentId,
        Authenticatable $actor,
    ): MediaSelectionRecord {
        $previous = null;
        $transitioned = false;

        try {
            return $this->withLock($requestToken, function () use (
                $requestToken,
                $ownerToken,
                $managerComponentId,
                $actor,
                &$previous,
                &$transitioned,
            ): MediaSelectionRecord {
                $record = $this->validatedForManager($requestToken, $ownerToken, $managerComponentId, $actor);

                if (in_array($record->state, ['succeeded', 'failed', 'expired'], true)
                    || now()->getTimestamp() < $record->deadline) {
                    return $record;
                }

                $previous = $record;
                $transitioned = true;
                $expired = $record->withState('expired', 'selection_timeout');
                $this->store($requestToken, $expired);

                return $expired;
            });
        } catch (Throwable $exception) {
            if ($transitioned && $previous instanceof MediaSelectionRecord) {
                $this->restoreActiveRecord($requestToken, $previous, $exception);
            }

            if ($exception instanceof InvalidMediaSelectionRequest) {
                throw $exception;
            }

            throw new InvalidMediaSelectionRequest(
                'Unable to durably expire the media selection request.',
                previous: $exception,
            );
        }
    }

    public function forManager(
        string $requestToken,
        string $ownerToken,
        string $managerComponentId,
        Authenticatable $actor,
    ): MediaSelectionRecord {
        return $this->validatedForManager($requestToken, $ownerToken, $managerComponentId, $actor);
    }

    public function hasActiveRequestForOwner(
        string $ownerToken,
        Authenticatable $actor,
    ): bool {
        $owner = $this->resolveOwner($ownerToken, $actor);
        $indexKey = $this->ownerIndexKey($ownerToken);

        return $this->withNamedLock($indexKey.':lock', 5, function () use ($indexKey, $ownerToken, $owner): bool {
            try {
                $tokens = $this->ownerRequestTokens($indexKey);
            } catch (Throwable) {
                return true;
            }

            foreach ($tokens as $requestToken) {
                try {
                    $record = $this->read($requestToken);
                    $this->assertRequestFences($requestToken, $record);
                } catch (Throwable) {
                    return true;
                }

                if (! hash_equals($record->ownerTokenDigest, $this->owners->digest($ownerToken))
                    || ! hash_equals($record->actorId, $owner->actorId)
                    || $record->teamId !== $owner->teamId) {
                    return true;
                }

                if (in_array($record->state, ['pending', 'processing'], true)) {
                    return true;
                }
            }

            return false;
        }, 0);
    }

    /**
     * The first closure prepares an authorized mutation but must not change
     * authoritative state. Its returned closure is committed only after the
     * broker rechecks the claim fence and deadline. The prepared mutation must
     * provide rollback behavior so a deadline or authorization failure after
     * application cannot leave authoritative state changed.
     *
     * @param  list<int|string>  $value
     * @param  Closure(): MediaSelectionMutation  $mutation
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
        $claimId = bin2hex(random_bytes(32));
        $claimed = $this->withLock($requestToken, function () use (
            $requestToken,
            $ownerToken,
            $ownerComponentId,
            $slug,
            $value,
            $actor,
            $claimId,
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

            $processing = $record->withState('processing', claimId: $claimId);
            $this->store($requestToken, $processing);

            return $processing;
        });

        if ($claimed->state !== 'processing' || ! is_string($claimed->claimId)
            || ! hash_equals($claimed->claimId, $claimId)) {
            return $claimed;
        }

        $application = null;
        $applicationStarted = false;
        $rolledBack = false;

        try {
            $settled = $this->withNamedLock(
                $this->ownerIndexKey($ownerToken).':lock',
                max(10, $this->ttl() + $this->retention()),
                function () use (
                    $requestToken,
                    $ownerToken,
                    $ownerComponentId,
                    $slug,
                    $value,
                    $actor,
                    $claimId,
                    $mutation,
                    &$application,
                    &$applicationStarted,
                    &$rolledBack,
                ): MediaSelectionRecord {
                    return $this->processClaimedForOwner(
                        $requestToken,
                        $ownerToken,
                        $ownerComponentId,
                        $slug,
                        $value,
                        $actor,
                        $claimId,
                        $mutation,
                        $application,
                        $applicationStarted,
                        $rolledBack,
                    );
                },
            );
        } catch (Throwable $exception) {
            $recoveryFailure = null;

            if ($applicationStarted && ! $rolledBack && $application instanceof MediaSelectionMutation) {
                try {
                    $this->rollbackMutation($application);
                } catch (Throwable $rollbackException) {
                    $recoveryFailure = $rollbackException;
                }
            }

            try {
                $this->store($requestToken, $claimed);
            } catch (Throwable $storeException) {
                $recoveryFailure ??= $storeException;
            }

            if ($recoveryFailure instanceof Throwable) {
                throw new InvalidMediaSelectionRequest(
                    'Unable to restore the active media selection fence after failure.',
                    previous: $recoveryFailure,
                );
            }

            if ($exception instanceof InvalidMediaSelectionRequest) {
                throw $exception;
            }

            throw new InvalidMediaSelectionRequest(
                'Unable to finish the media selection request.',
                previous: $exception,
            );
        }

        if ($settled->state === 'succeeded' && $application?->afterCommit instanceof Closure) {
            ($application->afterCommit)();
        }

        return $settled;
    }

    private function addOrFail(string $key, mixed $value, int $seconds): void
    {
        try {
            $stored = $this->cache->add($key, $value, $seconds);
        } catch (Throwable $exception) {
            throw new InvalidMediaSelectionRequest('Unable to create durable media selection state.', previous: $exception);
        }

        if (! $stored) {
            throw new InvalidMediaSelectionRequest('Unable to create durable media selection state.');
        }
    }

    /** @param list<string> $tokens */
    private function assertOwnerRequestsSettled(
        array $tokens,
        string $ownerToken,
        MediaOwnerContext $owner,
    ): void {
        foreach ($tokens as $requestToken) {
            $record = $this->read($requestToken);
            $this->assertRequestFences($requestToken, $record);

            if (! hash_equals($record->ownerTokenDigest, $this->owners->digest($ownerToken))
                || ! hash_equals($record->actorId, $owner->actorId)
                || $record->teamId !== $owner->teamId) {
                throw new InvalidMediaSelectionRequest('The media selection owner index is invalid.');
            }

            if (in_array($record->state, ['pending', 'processing'], true)) {
                throw new InvalidMediaSelectionRequest('A media selection request is already active for this owner.');
            }
        }
    }

    private function assertRequestFences(string $requestToken, MediaSelectionRecord $record): void
    {
        try {
            $ownerTokens = $this->ownerRequestTokens(
                self::CACHE_PREFIX.'owner:'.$record->ownerTokenDigest,
            );
            $scopeToken = $this->cache->get(
                self::CACHE_PREFIX.'scope:'.$record->ownerTokenDigest.':'.$this->digest($record->managerComponentId),
            );
        } catch (Throwable $exception) {
            if ($exception instanceof InvalidMediaSelectionRequest) {
                throw $exception;
            }

            throw new InvalidMediaSelectionRequest('The media selection request fences are unavailable.', previous: $exception);
        }

        if ($ownerTokens !== [$requestToken]
            || ! is_string($scopeToken)
            || ! hash_equals($scopeToken, $requestToken)) {
            throw new InvalidMediaSelectionRequest('The media selection request fences are invalid.');
        }
    }

    /** @param list<int|string> $value */
    private function authorizeOwnerSelection(
        string $ownerToken,
        array $value,
        Authenticatable $actor,
    ): void {
        try {
            app(MediaAuthorization::class)->authorizeOwnerSelection($ownerToken, $value, $actor);
        } catch (InvalidMediaOwnerToken|InvalidMediaOwnerContext) {
            throw new InvalidMediaSelectionRequest('The media selection owner context is invalid.');
        }
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

    private function forgetOrFail(string $key): void
    {
        try {
            $forgotten = $this->cache->forget($key);
        } catch (Throwable $exception) {
            throw new InvalidMediaSelectionRequest('Unable to remove media selection state.', previous: $exception);
        }

        if (! $forgotten) {
            throw new InvalidMediaSelectionRequest('Unable to remove media selection state.');
        }
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

    private function ownerIndexKey(string $ownerToken): string
    {
        return self::CACHE_PREFIX.'owner:'.$this->owners->digest($ownerToken);
    }

    /** @return list<string> */
    private function ownerRequestTokens(string $indexKey): array
    {
        $tokens = $this->cache->get($indexKey, []);

        if (! is_array($tokens) || ! array_is_list($tokens)) {
            throw new InvalidMediaSelectionRequest('The media selection owner index is invalid.');
        }

        foreach ($tokens as $token) {
            if (! is_string($token) || preg_match('/^[A-Za-z0-9_-]{43}$/D', $token) !== 1) {
                throw new InvalidMediaSelectionRequest('The media selection owner index is invalid.');
            }
        }

        if (count($tokens) > 1 || count($tokens) !== count(array_unique($tokens, SORT_STRING))) {
            throw new InvalidMediaSelectionRequest('The media selection owner index is invalid.');
        }

        return $tokens;
    }

    /**
     * @param  list<int|string>  $value
     */
    private function processClaimedForOwner(
        string $requestToken,
        string $ownerToken,
        string $ownerComponentId,
        string $slug,
        array $value,
        Authenticatable $actor,
        string $claimId,
        Closure $mutation,
        ?MediaSelectionMutation &$application,
        bool &$applicationStarted,
        bool &$rolledBack,
    ): MediaSelectionRecord {
        $errorCode = null;

        try {
            $prepared = $mutation();

            if (! $prepared instanceof MediaSelectionMutation) {
                $errorCode = 'processing_failed';
            } else {
                $application = $prepared;
            }
        } catch (MediaSelectionRejected $exception) {
            $errorCode = $this->errorCode($exception->errorCode);
        } catch (Throwable) {
            $errorCode = 'processing_failed';
        }

        $ready = $this->withLock($requestToken, function () use (
            $requestToken,
            $ownerToken,
            $ownerComponentId,
            $slug,
            $value,
            $actor,
            $claimId,
            $application,
            $errorCode,
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

            if ($record->state !== 'processing' || ! is_string($record->claimId)
                || ! hash_equals($record->claimId, $claimId)) {
                throw new InvalidMediaSelectionRequest('The media selection request claim is stale.');
            }

            if (now()->getTimestamp() >= $record->deadline) {
                $expired = $record->withState('expired', 'selection_timeout');
                $this->store($requestToken, $expired);

                return $expired;
            }

            if ($errorCode !== null || ! $application instanceof MediaSelectionMutation) {
                $failed = $record->withState('failed', $errorCode ?? 'processing_failed');
                $this->store($requestToken, $failed);

                return $failed;
            }

            $this->authorizeOwnerSelection($ownerToken, $value, $actor);

            return $record;
        });

        if ($ready->state !== 'processing' || ! is_string($ready->claimId)
            || ! hash_equals($ready->claimId, $claimId)
            || ! $application instanceof MediaSelectionMutation) {
            return $ready;
        }

        $mutationErrorCode = null;
        $applicationStarted = true;

        try {
            ($application->apply)();
        } catch (MediaSelectionRejected $exception) {
            $mutationErrorCode = $this->errorCode($exception->errorCode);
        } catch (Throwable) {
            $mutationErrorCode = 'processing_failed';
        }

        return $this->withLock($requestToken, function () use (
            $requestToken,
            $ownerToken,
            $ownerComponentId,
            $slug,
            $value,
            $actor,
            $claimId,
            $application,
            $mutationErrorCode,
            &$rolledBack,
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
                $this->rollbackMutation($application);
                $rolledBack = true;

                return $record;
            }

            if ($record->state !== 'processing' || ! is_string($record->claimId)
                || ! hash_equals($record->claimId, $claimId)) {
                $this->rollbackMutation($application);
                $rolledBack = true;

                throw new InvalidMediaSelectionRequest('The media selection request apply fence is stale.');
            }

            $authorizationErrorCode = null;

            if ($mutationErrorCode === null) {
                try {
                    $this->authorizeOwnerSelection($ownerToken, $value, $actor);
                } catch (Throwable) {
                    $authorizationErrorCode = 'processing_failed';
                }
            }

            if (now()->getTimestamp() >= $record->deadline) {
                $this->rollbackMutation($application);
                $rolledBack = true;
                $settled = $record->withState('expired', 'selection_timeout');
            } elseif ($mutationErrorCode !== null || $authorizationErrorCode !== null) {
                $this->rollbackMutation($application);
                $rolledBack = true;
                $settled = $record->withState('failed', $mutationErrorCode ?? $authorizationErrorCode);
            } else {
                $settled = $record->withState('succeeded');
            }

            try {
                $this->store($requestToken, $settled);
            } catch (Throwable $exception) {
                if (! $rolledBack) {
                    $this->rollbackMutation($application);
                    $rolledBack = true;
                }

                if ($exception instanceof InvalidMediaSelectionRequest) {
                    throw $exception;
                }

                throw new InvalidMediaSelectionRequest(
                    'Unable to durably settle the media selection request.',
                    previous: $exception,
                );
            }

            return $settled;
        });
    }

    private function putOrFail(string $key, mixed $value, int $seconds): void
    {
        try {
            $stored = $this->cache->put($key, $value, $seconds);
        } catch (Throwable $exception) {
            throw new InvalidMediaSelectionRequest('Unable to persist media selection state.', previous: $exception);
        }

        if (! $stored) {
            throw new InvalidMediaSelectionRequest('Unable to persist media selection state.');
        }
    }

    private function read(string $requestToken): MediaSelectionRecord
    {
        if (preg_match('/^[A-Za-z0-9_-]{43}$/D', $requestToken) !== 1) {
            throw new InvalidMediaSelectionRequest('The media selection request is invalid.');
        }

        try {
            $stored = $this->cache->get($this->recordKey($requestToken));
        } catch (Throwable $exception) {
            throw new InvalidMediaSelectionRequest('The media selection request is unavailable.', previous: $exception);
        }

        if (! is_string($stored) || strlen($stored) > 16384) {
            throw new InvalidMediaSelectionRequest('The media selection request is invalid.');
        }

        try {
            $decoded = json_decode(
                $this->encrypter->decryptString($stored),
                true,
                32,
                JSON_THROW_ON_ERROR,
            );
        } catch (Throwable) {
            throw new InvalidMediaSelectionRequest('The media selection request is invalid.');
        }

        if (! is_array($decoded)) {
            throw new InvalidMediaSelectionRequest('The media selection request is invalid.');
        }

        $keys = array_keys($decoded);
        sort($keys);

        if ($keys !== ['purpose', 'record', 'request_token', 'version']
            || $decoded['purpose'] !== self::ENVELOPE_PURPOSE
            || $decoded['version'] !== self::ENVELOPE_VERSION
            || ! is_string($decoded['request_token'])
            || ! hash_equals($decoded['request_token'], $requestToken)
            || ! is_array($decoded['record'])) {
            throw new InvalidMediaSelectionRequest('The media selection request is invalid.');
        }

        try {
            $record = MediaSelectionRecord::fromArray($decoded['record']);
        } catch (Throwable) {
            throw new InvalidMediaSelectionRequest('The media selection request is invalid.');
        }

        $now = now()->getTimestamp();

        if (! hash_equals($record->requestDigest, $this->digest($requestToken))
            || $record->issuedAt > $now
            || $now > $record->deadline + $this->retention()) {
            throw new InvalidMediaSelectionRequest('The media selection request is invalid.');
        }

        return $record;
    }

    private function recordKey(string $requestToken): string
    {
        return self::CACHE_PREFIX.'request:'.$this->digest($requestToken);
    }

    /** @param list<string> $value */
    private function recoverActiveRequest(
        string $requestToken,
        MediaSelectionRecord $record,
        string $ownerToken,
        string $managerComponentId,
        array $value,
        MediaOwnerContext $owner,
    ): MediaSelectionRequest {
        if (now()->getTimestamp() >= $record->deadline
            || ! hash_equals($record->ownerTokenDigest, $this->owners->digest($ownerToken))
            || ! hash_equals($record->managerComponentId, $managerComponentId)
            || ! hash_equals($record->ownerComponentId, $owner->ownerComponentId)
            || ! hash_equals($record->actorId, $owner->actorId)
            || $record->teamId !== $owner->teamId
            || ! hash_equals($record->slug, $owner->slug)
            || ! hash_equals($record->valueDigest, $this->valueDigest($value))) {
            throw new InvalidMediaSelectionRequest('A media selection request is already active for this picker.');
        }

        return new MediaSelectionRequest($requestToken, $record->requestDigest, $record);
    }

    private function resolveOwner(string $ownerToken, Authenticatable $actor): MediaOwnerContext
    {
        try {
            return $this->owners->resolve($ownerToken, $actor);
        } catch (InvalidMediaOwnerToken) {
            throw new InvalidMediaSelectionRequest('The media selection owner token is invalid.');
        }
    }

    private function restoreActiveRecord(
        string $requestToken,
        MediaSelectionRecord $record,
        Throwable $failure,
    ): never {
        try {
            $this->store($requestToken, $record);
        } catch (Throwable $restoreException) {
            throw new InvalidMediaSelectionRequest(
                'Unable to restore the active media selection fence after failure.',
                previous: $restoreException,
            );
        }

        if ($failure instanceof InvalidMediaSelectionRequest) {
            throw $failure;
        }

        throw new InvalidMediaSelectionRequest(
            'Unable to durably settle the media selection request.',
            previous: $failure,
        );
    }

    /** @param list<string> $tokens */
    private function restoreOwnerRequestTokens(string $indexKey, array $tokens): void
    {
        if ($tokens === []) {
            $this->forgetOrFail($indexKey);

            return;
        }

        $this->putOrFail($indexKey, $tokens, $this->ttl() + $this->retention());
    }

    private function restoreScopeToken(string $scopeKey, mixed $token): void
    {
        if ($token === null) {
            $this->forgetOrFail($scopeKey);

            return;
        }

        if (! is_string($token)) {
            throw new InvalidMediaSelectionRequest('The previous media selection scope is invalid.');
        }

        $this->putOrFail($scopeKey, $token, $this->ttl() + $this->retention());
    }

    private function retention(): int
    {
        $retention = $this->config->get('aura.media.security.selection_retention', 60);

        if (! is_int($retention) || $retention < 1 || $retention > 3600) {
            throw new InvalidArgumentException('Aura media selection retention must be an integer from 1 through 3600 seconds.');
        }

        return $retention;
    }

    private function rollbackBegin(
        string $indexKey,
        array $previousTokens,
        string $requestToken,
        string $scopeKey,
        mixed $previousScopeToken,
        bool $indexAttempted,
        bool $recordStored,
        bool $scopeAttempted,
    ): void {
        $cleanupFailure = null;

        foreach ([
            fn () => $scopeAttempted ? $this->restoreScopeToken($scopeKey, $previousScopeToken) : null,
            fn () => $recordStored ? $this->forgetOrFail($this->recordKey($requestToken)) : null,
            fn () => $indexAttempted ? $this->restoreOwnerRequestTokens($indexKey, $previousTokens) : null,
        ] as $cleanup) {
            try {
                $cleanup();
            } catch (Throwable $exception) {
                $cleanupFailure ??= $exception;
            }
        }

        if ($cleanupFailure instanceof Throwable) {
            throw new InvalidMediaSelectionRequest(
                'Unable to roll back an incomplete media selection request.',
                previous: $cleanupFailure,
            );
        }
    }

    private function rollbackMutation(MediaSelectionMutation $application): void
    {
        try {
            ($application->rollback)();
        } catch (Throwable $exception) {
            throw new InvalidMediaSelectionRequest('Unable to roll back the media selection mutation.', previous: $exception);
        }
    }

    private function scopeKey(string $ownerToken, string $managerComponentId): string
    {
        return self::CACHE_PREFIX.'scope:'.$this->owners->digest($ownerToken).':'.$this->digest($managerComponentId);
    }

    private function sealRecord(string $requestToken, MediaSelectionRecord $record): string
    {
        return $this->encrypter->encryptString(json_encode([
            'purpose' => self::ENVELOPE_PURPOSE,
            'version' => self::ENVELOPE_VERSION,
            'request_token' => $requestToken,
            'record' => $record->toArray(),
        ], JSON_THROW_ON_ERROR));
    }

    private function store(string $requestToken, MediaSelectionRecord $record): void
    {
        $seconds = max(1, $record->deadline - now()->getTimestamp() + $this->retention());
        $this->putOrFail($this->recordKey($requestToken), $this->sealRecord($requestToken, $record), $seconds);
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
            || ! hash_equals($record->ownerComponentId, $owner->ownerComponentId)
            || ! hash_equals($record->actorId, $owner->actorId)
            || $record->teamId !== $owner->teamId
            || ! hash_equals($record->slug, $owner->slug)) {
            throw new InvalidMediaSelectionRequest('The media selection request context does not match.');
        }

        $this->assertRequestFences($requestToken, $record);

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

        $this->assertRequestFences($requestToken, $record);

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
        return $this->withNamedLock(
            self::CACHE_PREFIX.'lock:'.$this->digest($requestToken),
            10,
            $callback,
        );
    }

    /**
     * @template T
     *
     * @param  Closure(): T  $callback
     * @return T
     */
    private function withNamedLock(
        string $key,
        int $seconds,
        Closure $callback,
        int $waitSeconds = 5,
    ): mixed {
        try {
            $lock = $this->locks->lock($key, $seconds);

            if (! is_object($lock) || ! method_exists($lock, 'block') || ! method_exists($lock, 'release')) {
                throw new InvalidMediaSelectionRequest('The media selection lock is unavailable.');
            }

            if ($lock->block($waitSeconds) !== true) {
                throw new InvalidMediaSelectionRequest('The media selection lock could not be acquired.');
            }

            $result = null;
            $callbackFailure = null;

            try {
                $result = $callback();
            } catch (Throwable $exception) {
                $callbackFailure = $exception;
            }

            try {
                $released = $lock->release();
            } catch (Throwable $exception) {
                throw new InvalidMediaSelectionRequest('The media selection lock could not be released.', previous: $exception);
            }

            if (! $released) {
                throw new InvalidMediaSelectionRequest('The media selection lock could not be released.');
            }

            if ($callbackFailure instanceof Throwable) {
                throw $callbackFailure;
            }

            return $result;
        } catch (InvalidMediaSelectionRequest $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidMediaSelectionRequest('The media selection lock is unavailable.', previous: $exception);
        }
    }
}
