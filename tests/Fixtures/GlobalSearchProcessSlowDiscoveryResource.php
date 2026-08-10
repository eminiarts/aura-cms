<?php

namespace Aura\Base\Tests\Fixtures;

class GlobalSearchProcessSlowDiscoveryResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-slow-discovery';

    public static string $type = 'ProcessSearchSlowDiscovery';

    public static function getGlobalSearch()
    {
        $marker = getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER');

        if (is_string($marker) && $marker !== '') {
            file_put_contents($marker, 'slow-discovery-entered-'.getmypid());
        }

        usleep(750_000);

        return true;
    }
}
