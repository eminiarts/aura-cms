<?php

namespace Aura\Base\Tests\Fixtures\Widgets;

use Aura\Base\Widgets\Widget;

class CacheProbeWidget extends Widget
{
    public bool $childMounted = false;

    public static int $resolutions = 0;

    public function cachedValue(): int
    {
        return cache()->remember($this->cacheKey, $this->cacheDuration, function (): int {
            return ++self::$resolutions;
        });
    }

    public function mount(): void
    {
        $this->childMounted = true;
    }

    public function render(): string
    {
        return '<div>Cache probe</div>';
    }
}
