<?php

namespace Aura\Base\GlobalSearch;

final readonly class GlobalSearchResult
{
    public function __construct(
        public int|string $id,
        public string $type,
        public string $title,
        public string $icon,
        public string $url,
        public int $rank,
        public int $resourceOrder,
    ) {}
}
