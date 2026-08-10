<?php

namespace Aura\Base\Tests\Fixtures;

class GlobalSearchProcessRawPdoAdapterResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-raw-pdo-adapter';

    public static string $type = 'ProcessSearchRawPdoAdapter';

    public function globalSearchAdapter()
    {
        return GlobalSearchProcessRawPdoAdapter::class;
    }
}
