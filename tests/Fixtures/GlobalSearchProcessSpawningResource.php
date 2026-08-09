<?php

namespace Aura\Base\Tests\Fixtures;

class GlobalSearchProcessSpawningResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-spawning';

    public static string $type = 'ProcessSearchSpawning';

    public function globalSearchAdapter()
    {
        return GlobalSearchProcessSpawningAdapter::class;
    }
}
