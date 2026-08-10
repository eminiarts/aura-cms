<?php

namespace Aura\Base\GlobalSearch;

use Aura\Base\Resource;

final readonly class GlobalSearchCandidate
{
    public function __construct(
        public Resource $resource,
        public int $rank,
    ) {}
}
