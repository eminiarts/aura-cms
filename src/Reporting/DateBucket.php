<?php

namespace Aura\Base\Reporting;

enum DateBucket: string
{
    case Day = 'day';
    case Month = 'month';
    case Quarter = 'quarter';
    case Week = 'week';
    case Year = 'year';
}
