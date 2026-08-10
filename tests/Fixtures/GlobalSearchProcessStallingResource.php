<?php

namespace Aura\Base\Tests\Fixtures;

class GlobalSearchProcessStallingResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-stalling';

    public static string $type = 'ProcessSearchStalling';

    public function globalSearchAdapter()
    {
        return GlobalSearchProcessStallingAdapter::class;
    }
}
