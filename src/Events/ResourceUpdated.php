<?php

namespace Aura\Base\Events;

final readonly class ResourceUpdated extends ResourceEvent
{
    public function eventName(): string
    {
        return 'resource.updated';
    }
}
