<?php

namespace Aura\Base\Reporting;

final readonly class AggregateDefinition
{
    public function __construct(
        public string $resource,
        public AggregateOperation $operation,
        public ?string $metric = null,
        public ?string $groupBy = null,
        public ?DateRange $range = null,
        public ?DateBucket $bucket = null,
        public string $timezone = 'UTC',
        public ?string $queryScope = null,
    ) {}
}
