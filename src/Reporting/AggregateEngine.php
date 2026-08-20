<?php

namespace Aura\Base\Reporting;

interface AggregateEngine
{
    public function run(AggregateDefinition $definition): AggregateResult;
}
