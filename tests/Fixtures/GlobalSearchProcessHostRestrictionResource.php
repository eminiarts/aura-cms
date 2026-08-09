<?php

namespace Aura\Base\Tests\Fixtures;

class GlobalSearchProcessHostRestrictionResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-host-restriction';

    public static string $type = 'ProcessSearchHostRestriction';

    public static function getGlobalSearch()
    {
        file_put_contents(
            (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER'),
            function_exists('putenv') ? 'available' : 'disabled',
            FILE_APPEND,
        );

        return true;
    }
}
