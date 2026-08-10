<?php

namespace Aura\Base\Livewire\Media;

use Closure;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use InvalidArgumentException;
use Throwable;

final class MediaDetailsBroker
{
    private const CACHE_PREFIX = 'aura:media-details:v1:';

    public function __construct(
        private readonly MediaSecurityStore $store,
        private readonly ConfigRepository $config,
        private readonly MediaOwnerTokenBroker $owners,
    ) {}

    /** @return array{attachment_id: string, row_ids: list<string>, selection_ids: list<string>} */
    public function consume(
        string $token,
        string $ownerToken,
        string $componentId,
        string $fieldSlug,
        Authenticatable $actor,
    ): array {
        if (preg_match('/^[A-Za-z0-9_-]{43}$/', $token) !== 1) {
            throw new InvalidMediaOwnerContext('The media details context is invalid.');
        }

        return $this->withLock($token, function () use ($token, $ownerToken, $componentId, $fieldSlug, $actor): array {
            $record = $this->store->cache->get($this->key($token));
            $owner = $this->owners->resolve($ownerToken, $actor);

            if (! is_array($record)
                || ! is_string($record['owner_digest'] ?? null)
                || ! is_string($record['component_id'] ?? null)
                || ! is_string($record['field_slug'] ?? null)
                || ! is_string($record['attachment_id'] ?? null)
                || ! is_array($record['row_ids'] ?? null)
                || ! is_array($record['selection_ids'] ?? null)
                || ! is_int($record['deadline'] ?? null)
                || now()->getTimestamp() >= $record['deadline']
                || ! hash_equals($record['owner_digest'], $this->owners->digest($ownerToken))
                || ! hash_equals($record['component_id'], $componentId)
                || ! hash_equals($record['field_slug'], $fieldSlug)
                || ! hash_equals($record['actor_id'] ?? '', $owner->actorId)
                || ($record['team_id'] ?? null) !== $owner->teamId) {
                throw new InvalidMediaOwnerContext('The media details context is invalid.');
            }

            try {
                $forgotten = $this->store->cache->forget($this->key($token));
            } catch (Throwable $exception) {
                throw new InvalidMediaOwnerContext('The media details context could not be consumed.', previous: $exception);
            }

            if (! $forgotten) {
                throw new InvalidMediaOwnerContext('The media details context could not be consumed.');
            }

            return [
                'attachment_id' => $record['attachment_id'],
                'row_ids' => $this->normalizeIds($record['row_ids']),
                'selection_ids' => $this->normalizeIds($record['selection_ids']),
            ];
        });
    }

    /**
     * @param  list<int|string>  $rowIds
     * @param  list<int|string>  $selectionIds
     */
    public function issue(
        string $ownerToken,
        string $componentId,
        string $fieldSlug,
        int|string $attachmentId,
        array $rowIds,
        array $selectionIds,
        Authenticatable $actor,
    ): string {
        $owner = $this->owners->resolve($ownerToken, $actor);
        $rows = $this->normalizeIds($rowIds);
        $selection = $this->normalizeIds($selectionIds);
        $id = (string) $attachmentId;

        if ($componentId === '' || ! hash_equals($owner->slug, $fieldSlug) || ! in_array($id, $rows, true)) {
            throw new InvalidMediaOwnerContext('The media details context is invalid.');
        }

        $token = rtrim(strtr(base64_encode(random_bytes(32)), '+/', '-_'), '=');
        $record = [
            'owner_digest' => $this->owners->digest($ownerToken),
            'component_id' => $componentId,
            'field_slug' => $fieldSlug,
            'attachment_id' => $id,
            'row_ids' => $rows,
            'selection_ids' => $selection,
            'actor_id' => $owner->actorId,
            'team_id' => $owner->teamId,
            'deadline' => now()->getTimestamp() + $this->ttl(),
        ];

        try {
            $stored = $this->store->cache->add($this->key($token), $record, $this->ttl());
        } catch (Throwable $exception) {
            throw new InvalidMediaOwnerContext('Unable to issue a media details context.', previous: $exception);
        }

        if (! $stored) {
            throw new InvalidMediaOwnerContext('Unable to issue a media details context.');
        }

        return $token;
    }

    private function key(string $token): string
    {
        return self::CACHE_PREFIX.hash('sha256', $token);
    }

    /** @param list<int|string> $ids
     * @return list<string>
     */
    private function normalizeIds(array $ids): array
    {
        if (! array_is_list($ids)) {
            throw new InvalidArgumentException('Media IDs must be a list.');
        }

        $normalized = array_map(static fn (int|string $id): string => (string) $id, $ids);

        if (in_array('', $normalized, true) || count($normalized) !== count(array_unique($normalized, SORT_STRING))) {
            throw new InvalidArgumentException('Media IDs must be non-empty and unique.');
        }

        return $normalized;
    }

    private function ttl(): int
    {
        $ttl = $this->config->get('aura.media.security.selection_ttl', 15);

        if (! is_int($ttl) || $ttl < 1 || $ttl > 300) {
            throw new InvalidArgumentException('Aura media details TTL must be an integer from 1 through 300 seconds.');
        }

        return $ttl;
    }

    /** @param Closure(): array{attachment_id: string, row_ids: list<string>, selection_ids: list<string>} $callback
     * @return array{attachment_id: string, row_ids: list<string>, selection_ids: list<string>}
     */
    private function withLock(string $token, Closure $callback): array
    {
        try {
            $lock = $this->store->locks->lock($this->key($token).':lock', 10);

            if (! is_object($lock) || ! method_exists($lock, 'block') || ! method_exists($lock, 'release')) {
                throw new InvalidMediaOwnerContext('The media details context lock is unavailable.');
            }

            if ($lock->block(5) !== true) {
                throw new InvalidMediaOwnerContext('The media details context lock could not be acquired.');
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
                throw new InvalidMediaOwnerContext('The media details context lock could not be released.', previous: $exception);
            }

            if (! $released) {
                throw new InvalidMediaOwnerContext('The media details context lock could not be released.');
            }

            if ($callbackFailure instanceof Throwable) {
                throw $callbackFailure;
            }

            if (! is_array($result)) {
                throw new InvalidMediaOwnerContext('The media details context is invalid.');
            }

            return $result;
        } catch (InvalidMediaOwnerContext $exception) {
            throw $exception;
        } catch (Throwable $exception) {
            throw new InvalidMediaOwnerContext('The media details context lock is unavailable.', previous: $exception);
        }
    }
}
