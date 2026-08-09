<?php

namespace Aura\Base\Contracts;

use Aura\Base\Fields\Filters\FilterCapability;
use Aura\Base\Resource;

interface ProvidesFilterCapability
{
    /**
     * @param  array<string, mixed>  $field
     */
    public function provideAuraFilterCapability(Resource $model, array $field): FilterCapability;
}
