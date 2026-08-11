<?php

namespace Aura\Base\Reporting;

use DateTimeImmutable;
use DateTimeInterface;
use InvalidArgumentException;

final readonly class DateRange
{
    public DateTimeImmutable $end;

    public DateTimeImmutable $start;

    public function __construct(DateTimeInterface $start, DateTimeInterface $end)
    {
        $this->start = DateTimeImmutable::createFromInterface($start);
        $this->end = DateTimeImmutable::createFromInterface($end);

        if ($end <= $start) {
            throw new InvalidArgumentException('Reporting ranges must have an end after their start.');
        }
    }
}
