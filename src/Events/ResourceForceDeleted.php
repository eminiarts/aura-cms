<?php

namespace Aura\Base\Events;

final readonly class ResourceForceDeleted extends ResourceEvent
{
    public function eventName(): string
    {
        return 'resource.force-deleted';
    }
}
