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
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Str;
use LogicException;
use Throwable;

final class ResourceLifecycleDispatcher
{
    /** @var \WeakMap<\Aura\Base\Resource, object>|null */
    private static ?\WeakMap $activeStates = null;

    /** @var \WeakMap<object, true>|null */
    private static ?\WeakMap $consumedStates = null;

    public function __construct(private readonly ResourceLifecycleSnapshot $snapshot) {}

    public function beginDelete(Resource $resource): ResourceLifecycleState
    {
        return $this->begin($resource, ResourceLifecycleOperation::Delete);
    }

    public function beginRestore(Resource $resource): ResourceLifecycleState
    {
        return $this->begin($resource, ResourceLifecycleOperation::Restore);
    }

    public function beginSave(Resource $resource): ResourceLifecycleState
    {
        return $this->begin(
            $resource,
            $resource->exists ? ResourceLifecycleOperation::Update : ResourceLifecycleOperation::Create,
        );
    }

    public function dispatchDeleted(Resource $resource, ResourceLifecycleState $state): void
    {
        $this->assertState($resource, $state, ResourceLifecycleOperation::Delete);
        $this->assertDeleteCompleted($resource, $state);
        $this->claimState($resource, $state);

        $newPhysical = $state->hardDelete ? [] : $this->snapshot->currentPhysical($resource);
        $newMeta = $state->hardDelete ? [] : $this->snapshot->currentMeta($resource);
        $physicalChanges = $this->snapshot->changes($state->oldPhysical, $newPhysical);
        $metaChanges = $this->snapshot->changes($state->oldMeta, $newMeta);

        $this->publishCommittedDeletion($resource, [
            $this->event(ResourceDeleting::class, $resource, $state, $physicalChanges, $metaChanges),
            $this->event(ResourceDeleted::class, $resource, $state, $physicalChanges, $metaChanges),
        ]);
    }

    public function dispatchForceDeleted(Resource $resource, ResourceLifecycleState $state): void
    {
        $this->assertState($resource, $state, ResourceLifecycleOperation::Delete);

        if (! $state->hardDelete) {
            throw new LogicException('A soft-delete lifecycle state cannot be used for force-delete publication.');
        }

        $this->assertDeleteCompleted($resource, $state);
        $this->claimState($resource, $state);

        $physicalChanges = $this->snapshot->changes($state->oldPhysical, []);
        $metaChanges = $this->snapshot->changes($state->oldMeta, []);

        $this->publishCommittedDeletion($resource, [
            $this->event(ResourceDeleting::class, $resource, $state, $physicalChanges, $metaChanges),
            $this->event(ResourceDeleted::class, $resource, $state, $physicalChanges, $metaChanges),
            $this->event(ResourceForceDeleted::class, $resource, $state, $physicalChanges, $metaChanges),
        ]);
    }

    public function dispatchRestored(Resource $resource, ResourceLifecycleState $state): void
    {
        $this->assertState($resource, $state, ResourceLifecycleOperation::Restore);
        $this->assertRestoreCompleted($resource, $state);
        $this->claimState($resource, $state);

        $this->dispatch($this->event(
            ResourceRestored::class,
            $resource,
            $state,
            $this->snapshot->changes($state->oldPhysical, $this->snapshot->currentPhysical($resource)),
            $this->snapshot->changes($state->oldMeta, $this->snapshot->currentMeta($resource)),
        ));
    }

    public function dispatchSaved(Resource $resource, ResourceLifecycleState $state): void
    {
        $this->assertState(
            $resource,
            $state,
            ResourceLifecycleOperation::Create,
            ResourceLifecycleOperation::Update,
        );
        $this->assertSaveCompleted($resource, $state);
        $this->claimState($resource, $state);

        $physicalChanges = $this->snapshot->changes($state->oldPhysical, $this->snapshot->currentPhysical($resource));
        $metaChanges = $this->snapshot->changes($state->oldMeta, $this->snapshot->currentMeta($resource));

        if ($state->operation === ResourceLifecycleOperation::Update
            && $this->onlyAutomaticTimestampsChanged($resource, $physicalChanges)
            && $metaChanges === []) {
            return;
        }

        $eventClass = $state->operation === ResourceLifecycleOperation::Create
            ? ResourceCreated::class
            : ResourceUpdated::class;

        $this->dispatch($this->event($eventClass, $resource, $state, $physicalChanges, $metaChanges));
    }

    private function assertDeleteCompleted(Resource $resource, ResourceLifecycleState $state): void
    {
        $persistedPhysical = $this->snapshot->persistedPhysical($resource);

        if ($state->hardDelete && ($resource->exists || $persistedPhysical !== null)) {
            throw new LogicException('The hard-delete resource lifecycle operation has not completed.');
        }

        if ($state->hardDelete) {
            $this->assertLifecycleCallbackCompleted($resource, $state);

            return;
        }

        $deletedAtColumn = method_exists($resource, 'getDeletedAtColumn')
            ? $resource->getDeletedAtColumn()
            : null;

        if (! $resource->exists
            || $deletedAtColumn === null
            || $persistedPhysical === null
            || ($persistedPhysical[$deletedAtColumn] ?? null) === null) {
            throw new LogicException('The soft-delete resource lifecycle operation has not completed.');
        }

        $this->assertLifecycleCallbackCompleted($resource, $state);
    }

    private function assertLifecycleCallbackCompleted(Resource $resource, ResourceLifecycleState $state): void
    {
        if ($resource->resourceLifecycleSequence() <= $state->resourceLifecycleSequence) {
            throw new LogicException(sprintf(
                'The resource lifecycle %s operation has not completed.',
                $state->operation->value,
            ));
        }
    }

    private function assertRestoreCompleted(Resource $resource, ResourceLifecycleState $state): void
    {
        $persistedPhysical = $this->snapshot->persistedPhysical($resource);
        $deletedAtColumn = method_exists($resource, 'getDeletedAtColumn')
            ? $resource->getDeletedAtColumn()
            : null;

        if (! $resource->exists
            || $deletedAtColumn === null
            || $persistedPhysical === null
            || ($persistedPhysical[$deletedAtColumn] ?? null) !== null) {
            throw new LogicException('The restore resource lifecycle operation has not completed.');
        }

        $this->assertLifecycleCallbackCompleted($resource, $state);
    }

    private function assertSaveCompleted(Resource $resource, ResourceLifecycleState $state): void
    {
        if (! $resource->exists || $this->snapshot->persistedPhysical($resource) === null) {
            throw new LogicException(sprintf(
                'The resource lifecycle %s operation has not completed.',
                $state->operation->value,
            ));
        }

        $this->assertLifecycleCallbackCompleted($resource, $state);
    }

    private function assertState(
        Resource $resource,
        ResourceLifecycleState $state,
        ResourceLifecycleOperation ...$allowedOperations,
    ): void {
        if (! in_array($state->operation, $allowedOperations, true)) {
            throw new LogicException(sprintf(
                'Resource lifecycle operation [%s] cannot be used for this dispatch method.',
                $state->operation->value,
            ));
        }

        $resourceId = $this->snapshot->scalarValue($resource->getKey());
        $connection = $resource->getConnection();
        $identityMatches = $state->resourceObjectId === spl_object_id($resource)
            && $state->resourceClass === $resource::class
            && $state->resourceMorphType === $resource->getMorphClass()
            && ($state->resourceId === null || $state->resourceId === $resourceId)
            && $state->connectionName === $this->connectionName($resource)
            && $state->connectionIdentity === User::connectionCacheIdentity($connection)
            && $state->table === $resource->getTable()
            && $state->keyName === $resource->getKeyName();

        if (! $identityMatches) {
            throw new LogicException('Resource lifecycle state does not belong to the supplied resource and connection.');
        }

        self::$activeStates ??= new \WeakMap;

        if ((self::$activeStates[$resource] ?? null) !== $state->claimToken()) {
            throw new LogicException('Resource lifecycle state is no longer active for the supplied resource.');
        }
    }

    private function begin(Resource $resource, ResourceLifecycleOperation $operation): ResourceLifecycleState
    {
        $state = ResourceLifecycleState::capture(
            resource: $resource,
            operation: $operation,
            snapshot: $this->snapshot,
            operationId: (string) Str::uuid(),
        );

        self::$activeStates ??= new \WeakMap;
        self::$activeStates[$resource] = $state->claimToken();

        return $state;
    }

    private function claimState(Resource $resource, ResourceLifecycleState $state): void
    {
        self::$consumedStates ??= new \WeakMap;
        $claimToken = $state->claimToken();

        if (isset(self::$consumedStates[$claimToken])) {
            throw new LogicException('Resource lifecycle state has already been dispatched.');
        }

        self::$consumedStates[$claimToken] = true;
        unset(self::$activeStates[$resource]);
    }

    private function connectionName(Resource $resource): string
    {
        return (string) ($resource->getConnection()->getName() ?? config('database.default'));
    }

    /** @param  array<string, bool|float|int|string|null>  $attributes */
    private function contextValue(array $attributes, ?string $column): bool|float|int|string|null
    {
        return $column === null ? null : $this->snapshot->scalarValue($attributes[$column] ?? null);
    }

    private function dispatch(ResourceEvent $event): void
    {
        Event::dispatch($event);
    }

    /**
     * @param  class-string<ResourceEvent>  $eventClass
     * @param  array<string, array{old: bool|float|int|string|null, new: bool|float|int|string|null}>  $physicalChanges
     * @param  array<string, array{old: bool|float|int|string|null, new: bool|float|int|string|null}>  $metaChanges
     */
    private function event(
        string $eventClass,
        Resource $resource,
        ResourceLifecycleState $state,
        array $physicalChanges,
        array $metaChanges,
    ): ResourceEvent {
        $currentAttributes = $this->snapshot->currentPhysical($resource);
        $useStoredPreOperationContext = $state->operation === ResourceLifecycleOperation::Delete;
        $ownerId = $useStoredPreOperationContext
            ? $state->ownerId
            : $this->contextValue($currentAttributes, $state->ownerColumn);
        $teamId = $useStoredPreOperationContext
            ? $state->teamId
            : $this->contextValue($currentAttributes, $state->teamColumn);

        return new $eventClass(
            eventId: (string) Str::uuid(),
            operationId: $state->operationId,
            resourceClass: $state->resourceClass,
            resourceType: $resource::getType(),
            resourceMorphType: $state->resourceMorphType,
            resourceId: $this->snapshot->scalarValue($resource->getKey()),
            occurredAt: now()->toISOString(),
            connectionName: $state->connectionName,
            connectionIdentity: $state->connectionIdentity,
            table: $state->table,
            keyName: $state->keyName,
            inheritanceColumn: $state->inheritanceColumn,
            inheritanceValue: $state->inheritanceValue,
            scopeMode: $state->scopeMode,
            teamColumn: $state->teamColumn,
            teamId: $teamId,
            ownerColumn: $state->ownerColumn,
            ownerId: $ownerId,
            sharedAcrossTeams: $state->sharedAcrossTeams,
            hardDelete: $state->hardDelete,
            physicalChanges: $physicalChanges,
            metaChanges: $metaChanges,
        );
    }

    /**
     * @param  array<string, array{old: bool|float|int|string|null, new: bool|float|int|string|null}>  $physicalChanges
     */
    private function onlyAutomaticTimestampsChanged(Resource $resource, array $physicalChanges): bool
    {
        $automaticTimestamps = array_filter([
            $resource->getCreatedAtColumn(),
            $resource->getUpdatedAtColumn(),
        ]);

        return array_diff(array_keys($physicalChanges), $automaticTimestamps) === [];
    }

    /** @param  array<int, ResourceEvent>  $events */
    private function publishCommittedDeletion(Resource $resource, array $events): void
    {
        $resource->getConnection()->afterCommit(function () use ($events): void {
            foreach ($events as $event) {
                try {
                    $this->dispatch($event);
                } catch (Throwable $exception) {
                    report($exception);
                }
            }
        });
    }
}
