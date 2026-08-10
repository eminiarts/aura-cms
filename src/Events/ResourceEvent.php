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
        public ?string $connectionName,
        public string $table,
        public string $scopeMode,
        public bool|float|int|string|null $teamId,
        public bool|float|int|string|null $ownerId,
        public array $physicalChanges,
        public array $metaChanges,
    ) {}
}
