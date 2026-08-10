<?php

namespace Aura\Base\ResourceLifecycle;

final readonly class ResourceLifecycleState
{
    /**
     * @param  array<string, bool|float|int|string|null>  $oldPhysical
     * @param  array<string, bool|float|int|string|null>  $oldMeta
     */
    public function __construct(
        public string $operation,
        public string $operationId,
        public array $oldPhysical,
        public array $oldMeta,
    ) {}
}
