<?php

namespace Aura\Base\Events;

final readonly class ResourceDeleting extends ResourceEvent
{
    public function eventName(): string
    {
        return 'resource.deleting';
    }
}
