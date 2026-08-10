<?php

namespace Aura\Base\Tests\Fixtures;

class GlobalSearchProcessBlockingDiscoveryResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-blocking-discovery';

    public static string $type = 'ProcessSearchBlockingDiscovery';

    public static function getGlobalSearch()
    {
        $marker = getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER');

        if (is_string($marker) && $marker !== '') {
            file_put_contents($marker, 'blocking-discovery-entered-'.getmypid());
        }

        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, 0);

        if (is_array($sockets)) {
            fread($sockets[0], 1);
        }

        return true;
    }
}
