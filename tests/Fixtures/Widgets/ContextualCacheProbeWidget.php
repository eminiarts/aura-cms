<?php

namespace Aura\Base\Tests\Fixtures\Widgets;

class ContextualCacheProbeWidget extends CacheProbeWidget
{
    protected function widgetCacheContextDimensions(): array
    {
        return ['resource', 'team', 'user'];
    }
}
