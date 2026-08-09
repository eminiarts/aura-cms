<?php

namespace Aura\Base\Contracts;

use Aura\Base\Fields\Filters\FilterCapability;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;

interface AppliesFieldFilter
{
    /**
     * @param  array<string, mixed>  $field
     * @param  array{name: string, operator: string, value?: mixed, options?: array<string, mixed>}  $filter
     */
    public function apply(
        Builder $query,
        Resource $resource,
        array $field,
        array $filter,
        FilterCapability $capability,
    ): void;
}
