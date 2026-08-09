<?php

namespace Aura\Base\Tests\Fixtures;

class GlobalSearchProcessQueryFloodAdapterResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-query-flood-adapter';

    public static string $type = 'ProcessSearchQueryFloodAdapter';

    public function globalSearchAdapter()
    {
        return GlobalSearchProcessQueryFloodAdapter::class;
    }
}
