<?php

namespace Aura\Base\Contracts;

interface ResourceLifecycleEvent
{
    public function eventName(): string;
}
