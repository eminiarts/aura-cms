<?php

namespace Aura\Base\ResourceLifecycle;

enum ResourceLifecycleOperation: string
{
    case Create = 'create';
    case Delete = 'delete';
    case Restore = 'restore';
    case Update = 'update';
}
