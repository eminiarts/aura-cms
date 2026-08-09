<?php

namespace Aura\Base\Livewire\Media;

final readonly class MediaSelectionRecord
{
    public function __construct(
        public string $requestDigest,
        public string $ownerTokenDigest,
        public string $managerComponentId,
        public string $ownerComponentId,
        public string $actorId,
        public ?string $teamId,
        public string $slug,
        public string $valueDigest,
        public int $issuedAt,
        public int $deadline,
        public string $state,
        public ?string $errorCode,
        public ?string $claimId,
    ) {}

    /** @param array<string, mixed> $record */
    public static function fromArray(array $record): self
    {
        return new self(
            requestDigest: $record['request_digest'],
            ownerTokenDigest: $record['owner_token_digest'],
            managerComponentId: $record['manager_component_id'],
            ownerComponentId: $record['owner_component_id'],
            actorId: $record['actor_id'],
            teamId: $record['team_id'],
            slug: $record['slug'],
            valueDigest: $record['value_digest'],
            issuedAt: $record['issued_at'],
            deadline: $record['deadline'],
            state: $record['state'],
            errorCode: $record['error_code'],
            claimId: $record['claim_id'],
        );
    }

    /** @return array<string, int|string|null> */
    public function toArray(): array
    {
        return [
            'request_digest' => $this->requestDigest,
            'owner_token_digest' => $this->ownerTokenDigest,
            'manager_component_id' => $this->managerComponentId,
            'owner_component_id' => $this->ownerComponentId,
            'actor_id' => $this->actorId,
            'team_id' => $this->teamId,
            'slug' => $this->slug,
            'value_digest' => $this->valueDigest,
            'issued_at' => $this->issuedAt,
            'deadline' => $this->deadline,
            'state' => $this->state,
            'error_code' => $this->errorCode,
            'claim_id' => $this->claimId,
        ];
    }

    public function withState(string $state, ?string $errorCode = null, ?string $claimId = null): self
    {
        return new self(
            requestDigest: $this->requestDigest,
            ownerTokenDigest: $this->ownerTokenDigest,
            managerComponentId: $this->managerComponentId,
            ownerComponentId: $this->ownerComponentId,
            actorId: $this->actorId,
            teamId: $this->teamId,
            slug: $this->slug,
            valueDigest: $this->valueDigest,
            issuedAt: $this->issuedAt,
            deadline: $this->deadline,
            state: $state,
            errorCode: $errorCode,
            claimId: $claimId,
        );
    }
}
