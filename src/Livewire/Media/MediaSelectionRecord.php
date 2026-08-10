<?php

namespace Aura\Base\Livewire\Media;

use InvalidArgumentException;

final readonly class MediaSelectionRecord
{
    /** @var list<string> */
    private const KEYS = [
        'actor_id',
        'claim_id',
        'claimed_at',
        'completed_at',
        'deadline',
        'error_code',
        'generation',
        'issued_at',
        'manager_component_id',
        'owner_component_id',
        'owner_token_digest',
        'request_digest',
        'slug',
        'state',
        'team_id',
        'value_digest',
    ];

    public function __construct(
        public string $requestDigest,
        public string $ownerTokenDigest,
        public string $managerComponentId,
        public string $ownerComponentId,
        public string $actorId,
        public ?string $teamId,
        public string $slug,
        public string $valueDigest,
        public int $generation,
        public int $issuedAt,
        public int $deadline,
        public string $state,
        public ?string $errorCode,
        public ?string $claimId,
        public ?int $claimedAt,
        public ?int $completedAt,
    ) {
        $this->assertValid();
    }

    /** @param array<string, mixed> $record */
    public static function fromArray(array $record): self
    {
        $keys = array_keys($record);
        sort($keys);

        if ($keys !== self::KEYS
            || ! is_string($record['request_digest'])
            || ! is_string($record['owner_token_digest'])
            || ! is_string($record['manager_component_id'])
            || ! is_string($record['owner_component_id'])
            || ! is_string($record['actor_id'])
            || (! is_null($record['team_id']) && ! is_string($record['team_id']))
            || ! is_string($record['slug'])
            || ! is_string($record['value_digest'])
            || ! is_int($record['generation'])
            || ! is_int($record['issued_at'])
            || ! is_int($record['deadline'])
            || ! is_string($record['state'])
            || (! is_null($record['error_code']) && ! is_string($record['error_code']))
            || (! is_null($record['claim_id']) && ! is_string($record['claim_id']))
            || (! is_null($record['claimed_at']) && ! is_int($record['claimed_at']))
            || (! is_null($record['completed_at']) && ! is_int($record['completed_at']))) {
            throw new InvalidArgumentException('The media selection record shape is invalid.');
        }

        return new self(
            requestDigest: $record['request_digest'],
            ownerTokenDigest: $record['owner_token_digest'],
            managerComponentId: $record['manager_component_id'],
            ownerComponentId: $record['owner_component_id'],
            actorId: $record['actor_id'],
            teamId: $record['team_id'],
            slug: $record['slug'],
            valueDigest: $record['value_digest'],
            generation: $record['generation'],
            issuedAt: $record['issued_at'],
            deadline: $record['deadline'],
            state: $record['state'],
            errorCode: $record['error_code'],
            claimId: $record['claim_id'],
            claimedAt: $record['claimed_at'],
            completedAt: $record['completed_at'],
        );
    }

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        $this->assertValid();

        return [
            'request_digest' => $this->requestDigest,
            'owner_token_digest' => $this->ownerTokenDigest,
            'manager_component_id' => $this->managerComponentId,
            'owner_component_id' => $this->ownerComponentId,
            'actor_id' => $this->actorId,
            'team_id' => $this->teamId,
            'slug' => $this->slug,
            'value_digest' => $this->valueDigest,
            'generation' => $this->generation,
            'issued_at' => $this->issuedAt,
            'deadline' => $this->deadline,
            'state' => $this->state,
            'error_code' => $this->errorCode,
            'claim_id' => $this->claimId,
            'claimed_at' => $this->claimedAt,
            'completed_at' => $this->completedAt,
        ];
    }

    public function withState(string $state, ?string $errorCode = null, ?string $claimId = null): self
    {
        $allowed = match ($this->state) {
            'pending' => in_array($state, ['processing', 'expired'], true),
            'processing' => in_array($state, ['succeeded', 'failed', 'expired'], true),
            default => false,
        };

        if (! $allowed) {
            throw new InvalidArgumentException('The media selection state transition is invalid.');
        }

        $now = now()->getTimestamp();

        return new self(
            requestDigest: $this->requestDigest,
            ownerTokenDigest: $this->ownerTokenDigest,
            managerComponentId: $this->managerComponentId,
            ownerComponentId: $this->ownerComponentId,
            actorId: $this->actorId,
            teamId: $this->teamId,
            slug: $this->slug,
            valueDigest: $this->valueDigest,
            generation: $this->generation + 1,
            issuedAt: $this->issuedAt,
            deadline: $this->deadline,
            state: $state,
            errorCode: $errorCode,
            claimId: $claimId,
            claimedAt: $state === 'processing' ? $now : $this->claimedAt,
            completedAt: in_array($state, ['succeeded', 'failed', 'expired'], true) ? $now : null,
        );
    }

    private function assertValid(): void
    {
        if (! $this->isDigest($this->requestDigest)
            || ! $this->isDigest($this->ownerTokenDigest)
            || ! $this->isDigest($this->valueDigest)
            || ! $this->isIdentifier($this->managerComponentId)
            || ! $this->isIdentifier($this->ownerComponentId)
            || ! $this->isIdentifier($this->actorId)
            || ($this->teamId !== null && ! $this->isIdentifier($this->teamId))
            || ! $this->isIdentifier($this->slug)
            || $this->generation < 0
            || $this->generation > 2
            || $this->issuedAt < 1
            || $this->deadline <= $this->issuedAt
            || $this->deadline - $this->issuedAt > 300
            || ! in_array($this->state, ['pending', 'processing', 'succeeded', 'failed', 'expired'], true)) {
            throw new InvalidArgumentException('The media selection record context is invalid.');
        }

        $valid = match ($this->state) {
            'pending' => $this->errorCode === null
                && $this->generation === 0
                && $this->claimId === null
                && $this->claimedAt === null
                && $this->completedAt === null,
            'processing' => $this->errorCode === null
                && $this->generation === 1
                && $this->isClaim($this->claimId)
                && $this->isClaimedTimestamp()
                && $this->completedAt === null,
            'succeeded' => $this->errorCode === null
                && $this->generation === 2
                && $this->claimId === null
                && $this->isClaimedTimestamp()
                && $this->isCompletedBeforeDeadline(),
            'failed' => $this->isFailureCode($this->errorCode)
                && $this->generation === 2
                && $this->claimId === null
                && $this->isClaimedTimestamp()
                && $this->isCompletedBeforeDeadline(),
            'expired' => $this->errorCode === 'selection_timeout'
                && (($this->generation === 1 && $this->claimedAt === null)
                    || ($this->generation === 2 && $this->claimedAt !== null))
                && $this->claimId === null
                && ($this->claimedAt === null || $this->isClaimedTimestamp())
                && $this->completedAt !== null
                && $this->completedAt >= $this->deadline,
        };

        if (! $valid) {
            throw new InvalidArgumentException('The media selection record state is invalid.');
        }
    }

    private function isClaim(?string $claimId): bool
    {
        return is_string($claimId) && preg_match('/^[a-f0-9]{64}$/D', $claimId) === 1;
    }

    private function isClaimedTimestamp(): bool
    {
        return $this->claimedAt !== null
            && $this->claimedAt >= $this->issuedAt
            && $this->claimedAt < $this->deadline;
    }

    private function isCompletedBeforeDeadline(): bool
    {
        return $this->completedAt !== null
            && $this->claimedAt !== null
            && $this->completedAt >= $this->claimedAt
            && $this->completedAt < $this->deadline;
    }

    private function isDigest(string $value): bool
    {
        return preg_match('/^[a-f0-9]{64}$/D', $value) === 1;
    }

    private function isFailureCode(?string $errorCode): bool
    {
        return is_string($errorCode)
            && $errorCode !== 'selection_timeout'
            && preg_match('/^[a-z][a-z0-9_]{0,63}$/D', $errorCode) === 1;
    }

    private function isIdentifier(string $value): bool
    {
        return $value !== ''
            && strlen($value) <= 255
            && preg_match('/[\x00-\x1F\x7F]/', $value) !== 1;
    }
}
