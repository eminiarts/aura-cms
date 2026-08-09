<?php

namespace Aura\Base\Livewire\Media;

use Aura\Base\Fields\Field;
use Aura\Base\Resource;
use Livewire\Component;

final readonly class MediaOwnerContext
{
    /**
     * @param  class-string<resource>  $modelClass
     * @param  class-string<Field>|null  $fieldType
     */
    public function __construct(
        public string $ownerComponentId,
        public ?string $ownerComponentClass,
        public string $modelClass,
        public ?string $modelKey,
        public string $action,
        public string $slug,
        public ?string $fieldType,
        public string $actorId,
        public ?string $teamId,
        public string $nonce,
        public int $issuedAt,
        public int $deadline,
    ) {}

    /**
     * @param  array{
     *     owner_component_id: string,
     *     owner_component_class: class-string<Component>|null,
     *     model_class: class-string<resource>,
     *     model_key: string|null,
     *     action: string,
     *     slug: string,
     *     field_type: class-string<Field>|null,
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
            ownerComponentClass: $payload['owner_component_class'],
            modelClass: $payload['model_class'],
            modelKey: $payload['model_key'],
            action: $payload['action'],
            slug: $payload['slug'],
            fieldType: $payload['field_type'],
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
     *     owner_component_class: class-string<Component>|null,
     *     model_class: class-string<resource>,
     *     model_key: string|null,
     *     action: string,
     *     slug: string,
     *     field_type: class-string<Field>|null,
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
            'owner_component_class' => $this->ownerComponentClass,
            'model_class' => $this->modelClass,
            'model_key' => $this->modelKey,
            'action' => $this->action,
            'slug' => $this->slug,
            'field_type' => $this->fieldType,
            'actor_id' => $this->actorId,
            'team_id' => $this->teamId,
            'nonce' => $this->nonce,
            'issued_at' => $this->issuedAt,
            'deadline' => $this->deadline,
        ];
    }
}
