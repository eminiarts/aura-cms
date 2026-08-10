<?php

namespace Aura\Base\Events;

final readonly class ResourceRestored extends ResourceEvent
{
    public function eventName(): string
    {
        return 'resource.restored';
    }
}
