<?php

namespace Aura\Base\Events;

final readonly class ResourceDeleted extends ResourceEvent
{
    public function eventName(): string
    {
        return 'resource.deleted';
    }
}
