<?php

namespace Aura\Base\Reporting;

enum AggregateOperation: string
{
    case Average = 'average';
    case Count = 'count';
    case Maximum = 'max';
    case Minimum = 'min';
    case Sum = 'sum';
}
