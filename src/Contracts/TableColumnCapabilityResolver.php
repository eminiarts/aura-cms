<?php

namespace Aura\Base\Contracts;

use Aura\Base\Resource;
use Aura\Base\Table\TableColumnCapability;

interface TableColumnCapabilityResolver
{
    public function resolve(Resource $resource, string $key): ?TableColumnCapability;
}
