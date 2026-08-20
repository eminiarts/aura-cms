<?php

namespace Aura\Base\Contracts;

interface DeclaresReportingQueryScopes
{
    /** @return list<string> */
    public static function reportingQueryScopes(): array;
}
