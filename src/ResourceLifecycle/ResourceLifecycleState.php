<?php

namespace Aura\Base\ResourceLifecycle;

use Aura\Base\Resource;
use Aura\Base\Resources\User;

final readonly class ResourceLifecycleState
{
    /**
     * @param  array<string, bool|float|int|string|null>  $oldPhysical
     * @param  array<string, bool|float|int|string|null>  $oldMeta
     */
    private function __construct(
        public ResourceLifecycleOperation $operation,
        public string $operationId,
        public int $resourceObjectId,
        public string $resourceClass,
        public string $resourceMorphType,
        public bool|float|int|string|null $resourceId,
        public string $connectionName,
        public string $connectionIdentity,
        public string $table,
        public string $keyName,
        public string $scopeMode,
        public ?string $ownerColumn,
        public bool|float|int|string|null $ownerId,
        public ?string $teamColumn,
        public bool|float|int|string|null $teamId,
        public bool $sharedAcrossTeams,
        public ?string $inheritanceColumn,
        public ?string $inheritanceValue,
        public bool $hardDelete,
        public array $oldPhysical,
        public array $oldMeta,
    ) {}

    public static function capture(
        Resource $resource,
        ResourceLifecycleOperation $operation,
        ResourceLifecycleSnapshot $snapshot,
        string $operationId,
    ): self {
        $oldPhysical = $resource->exists ? $snapshot->originalPhysical($resource) : [];
        $contextAttributes = $resource->exists ? $oldPhysical : $snapshot->currentPhysical($resource);
        $ownerColumn = $resource::getOwnerColumn();
        $teamColumn = $resource::getTeamColumn();

        return new self(
            operation: $operation,
            operationId: $operationId,
            resourceObjectId: spl_object_id($resource),
            resourceClass: $resource::class,
            resourceMorphType: $resource->getMorphClass(),
            resourceId: $snapshot->scalarValue($resource->getKey()),
            connectionName: (string) ($resource->getConnection()->getName() ?? config('database.default')),
            connectionIdentity: User::connectionCacheIdentity($resource->getConnection()),
            table: $resource->getTable(),
            keyName: $resource->getKeyName(),
            scopeMode: $resource::getScopeMode(),
            ownerColumn: $ownerColumn,
            ownerId: $ownerColumn === null ? null : $snapshot->scalarValue($contextAttributes[$ownerColumn] ?? null),
            teamColumn: $teamColumn,
            teamId: $teamColumn === null ? null : $snapshot->scalarValue($contextAttributes[$teamColumn] ?? null),
            sharedAcrossTeams: $resource::sharesRecordsAcrossTeams(),
            inheritanceColumn: $resource::getInheritanceColumn(),
            inheritanceValue: $resource::getInheritanceValue(),
            hardDelete: $operation === ResourceLifecycleOperation::Delete
                && (! method_exists($resource, 'isForceDeleting') || $resource->isForceDeleting()),
            oldPhysical: $oldPhysical,
            oldMeta: $resource->exists ? $snapshot->currentMeta($resource) : [],
        );
    }
}
