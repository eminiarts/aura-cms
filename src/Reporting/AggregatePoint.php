<?php

namespace Aura\Base\Reporting;

final readonly class AggregatePoint
{
    public function __construct(
        public ?string $key,
        public string $label,
        public int|string|null $value,
        public int $rowCount,
    ) {}
}
