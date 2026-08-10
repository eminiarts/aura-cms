<?php

namespace Aura\Base\Events;

use Aura\Base\Contracts\ResourceLifecycleEvent;
use Illuminate\Contracts\Events\ShouldDispatchAfterCommit;
use Illuminate\Foundation\Events\Dispatchable;

abstract readonly class ResourceEvent implements ResourceLifecycleEvent, ShouldDispatchAfterCommit
{
    use Dispatchable;

    /**
     * @param  array<string, array{old: bool|float|int|string|null, new: bool|float|int|string|null}>  $physicalChanges
     * @param  array<string, array{old: bool|float|int|string|null, new: bool|float|int|string|null}>  $metaChanges
     */
    public function __construct(
        public string $eventId,
        public string $operationId,
        public string $resourceClass,
        public string $resourceType,
        public string $resourceMorphType,
        public bool|float|int|string|null $resourceId,
        public string $occurredAt,
        public string $connectionName,
        public string $connectionIdentity,
        public string $table,
        public string $keyName,
        public ?string $inheritanceColumn,
        public ?string $inheritanceValue,
        public string $scopeMode,
        public ?string $teamColumn,
        public bool|float|int|string|null $teamId,
        public ?string $ownerColumn,
        public bool|float|int|string|null $ownerId,
        public bool $sharedAcrossTeams,
        public bool $hardDelete,
        public array $physicalChanges,
        public array $metaChanges,
    ) {}
}
