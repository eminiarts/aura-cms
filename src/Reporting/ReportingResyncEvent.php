<?php

namespace Aura\Base\Reporting;

use Aura\Base\Events\ResourceEvent;
use Aura\Base\Resource;
use Aura\Base\Resources\User;

final readonly class ReportingResyncEvent extends ResourceEvent
{
    public function eventName(): string
    {
        return 'reporting.resync';
    }

    public static function fromResource(Resource $resource): self
    {
        return new self(
            eventId: '',
            operationId: '',
            resourceClass: $resource::class,
            resourceType: $resource::getType(),
            resourceMorphType: $resource->getMorphClass(),
            resourceId: $resource->getKey(),
            occurredAt: '',
            connectionName: (string) ($resource->getConnection()->getName() ?? config('database.default')),
            connectionIdentity: User::connectionCacheIdentity($resource->getConnection()),
            table: $resource->getTable(),
            keyName: $resource->getKeyName(),
            inheritanceColumn: $resource::getInheritanceColumn(),
            inheritanceValue: $resource::getInheritanceValue(),
            scopeMode: '',
            teamColumn: null,
            teamId: null,
            ownerColumn: null,
            ownerId: null,
            sharedAcrossTeams: false,
            hardDelete: false,
            physicalChanges: [],
            metaChanges: [],
        );
    }
}
