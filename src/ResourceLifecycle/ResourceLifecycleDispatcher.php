<?php

namespace Aura\Base\ResourceLifecycle;

use Aura\Base\Events\ResourceCreated;
use Aura\Base\Events\ResourceDeleted;
use Aura\Base\Events\ResourceDeleting;
use Aura\Base\Events\ResourceEvent;
use Aura\Base\Events\ResourceForceDeleted;
use Aura\Base\Events\ResourceRestored;
use Aura\Base\Events\ResourceUpdated;
use Aura\Base\Resource;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;

final class ResourceLifecycleDispatcher
{
    public function __construct(private readonly ResourceLifecycleSnapshot $snapshot) {}

    public function beginDelete(Resource $resource): ResourceLifecycleState
    {
        return $this->begin($resource, 'delete');
    }

    public function beginRestore(Resource $resource): ResourceLifecycleState
    {
        return $this->begin($resource, 'restore');
    }

    public function beginSave(Resource $resource): ResourceLifecycleState
    {
        return $this->begin($resource, $resource->exists ? 'update' : 'create');
    }

    public function dispatchDeleted(Resource $resource, ResourceLifecycleState $state): void
    {
        $physicalChanges = $this->snapshot->changes($state->oldPhysical, []);
        $metaChanges = $this->snapshot->changes($state->oldMeta, []);

        $this->dispatch(ResourceDeleting::class, $resource, $state, $physicalChanges, $metaChanges);
        $this->dispatch(ResourceDeleted::class, $resource, $state, $physicalChanges, $metaChanges);
    }

    public function dispatchForceDeleted(Resource $resource, ResourceLifecycleState $state): void
    {
        $this->dispatch(
            ResourceForceDeleted::class,
            $resource,
            $state,
            $this->snapshot->changes($state->oldPhysical, []),
            $this->snapshot->changes($state->oldMeta, []),
        );
    }

    public function dispatchRestored(Resource $resource, ResourceLifecycleState $state): void
    {
        $this->dispatch(
            ResourceRestored::class,
            $resource,
            $state,
            $this->snapshot->changes($state->oldPhysical, $this->snapshot->currentPhysical($resource)),
            $this->snapshot->changes($state->oldMeta, $this->snapshot->currentMeta($resource)),
        );
    }

    public function dispatchSaved(Resource $resource, ResourceLifecycleState $state): void
    {
        if ($state->operation === 'restore') {
            return;
        }

        $physicalChanges = $this->snapshot->changes($state->oldPhysical, $this->snapshot->currentPhysical($resource));
        $metaChanges = $this->snapshot->changes($state->oldMeta, $this->snapshot->currentMeta($resource));

        if ($state->operation === 'update' && $physicalChanges === [] && $metaChanges === []) {
            return;
        }

        $eventClass = $state->operation === 'create' ? ResourceCreated::class : ResourceUpdated::class;
        $this->dispatch($eventClass, $resource, $state, $physicalChanges, $metaChanges);
    }

    private function begin(Resource $resource, string $operation): ResourceLifecycleState
    {
        return new ResourceLifecycleState(
            operation: $operation,
            operationId: (string) Str::uuid(),
            oldPhysical: $resource->exists ? $this->snapshot->originalPhysical($resource) : [],
            oldMeta: $resource->exists ? $this->snapshot->currentMeta($resource) : [],
        );
    }

    /**
     * @param  class-string<ResourceEvent>  $eventClass
     * @param  array<string, array{old: bool|float|int|string|null, new: bool|float|int|string|null}>  $physicalChanges
     * @param  array<string, array{old: bool|float|int|string|null, new: bool|float|int|string|null}>  $metaChanges
     */
    private function dispatch(
        string $eventClass,
        Resource $resource,
        ResourceLifecycleState $state,
        array $physicalChanges,
        array $metaChanges,
    ): void {
        $ownerColumn = $resource::getOwnerColumn();
        $teamColumn = $resource::getTeamColumn();

        Event::dispatch(new $eventClass(
            eventId: (string) Str::uuid(),
            operationId: $state->operationId,
            resourceClass: $resource::class,
            resourceType: $resource::getType(),
            resourceMorphType: $resource->getMorphClass(),
            resourceId: $this->scalar($resource->getKey()),
            connectionName: $resource->getConnectionName(),
            table: $resource->getTable(),
            scopeMode: $resource::getScopeMode(),
            teamId: $teamColumn === null ? null : $this->scalar($resource->getAttribute($teamColumn)),
            ownerId: $ownerColumn === null ? null : $this->scalar($resource->getAttribute($ownerColumn)),
            physicalChanges: $physicalChanges,
            metaChanges: $metaChanges,
        ));
    }

    private function scalar(mixed $value): bool|float|int|string|null
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        return (string) $value;
    }
}
