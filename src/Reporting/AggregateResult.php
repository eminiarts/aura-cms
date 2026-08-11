<?php

namespace Aura\Base\Reporting;

final readonly class AggregateResult
{
    /** @param list<AggregatePoint> $points */
    public function __construct(public int|string|null $value, public array $points = []) {}
}
