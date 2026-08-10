<?php

namespace Aura\Base\Events;

final readonly class ResourceCreated extends ResourceEvent
{
    public function eventName(): string
    {
        return 'resource.created';
    }
}
