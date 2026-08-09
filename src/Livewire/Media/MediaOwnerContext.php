<?php

namespace Aura\Base\Livewire\Media;

use Aura\Base\Resource;

final readonly class MediaOwnerContext
{
    /**
     * @param  class-string<resource>  $modelClass
     */
    public function __construct(
        public string $ownerComponentId,
        public string $modelClass,
        public ?string $modelKey,
        public string $action,
        public string $slug,
        public string $actorId,
        public ?string $teamId,
        public string $nonce,
        public int $issuedAt,
        public int $deadline,
    ) {}

    /**
     * @param  array{
     *     owner_component_id: string,
     *     model_class: class-string<resource>,
     *     model_key: string|null,
     *     action: string,
     *     slug: string,
     *     actor_id: string,
     *     team_id: string|null,
     *     nonce: string,
     *     issued_at: int,
     *     deadline: int
     * }  $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            ownerComponentId: $payload['owner_component_id'],
            modelClass: $payload['model_class'],
            modelKey: $payload['model_key'],
            action: $payload['action'],
            slug: $payload['slug'],
            actorId: $payload['actor_id'],
            teamId: $payload['team_id'],
            nonce: $payload['nonce'],
            issuedAt: $payload['issued_at'],
            deadline: $payload['deadline'],
        );
    }

    /**
     * @return array{
     *     owner_component_id: string,
     *     model_class: class-string<resource>,
     *     model_key: string|null,
     *     action: string,
     *     slug: string,
     *     actor_id: string,
     *     team_id: string|null,
     *     nonce: string,
     *     issued_at: int,
     *     deadline: int
     * }
     */
    public function toArray(): array
    {
        return [
            'owner_component_id' => $this->ownerComponentId,
            'model_class' => $this->modelClass,
            'model_key' => $this->modelKey,
            'action' => $this->action,
            'slug' => $this->slug,
            'actor_id' => $this->actorId,
            'team_id' => $this->teamId,
            'nonce' => $this->nonce,
            'issued_at' => $this->issuedAt,
            'deadline' => $this->deadline,
        ];
    }
}
