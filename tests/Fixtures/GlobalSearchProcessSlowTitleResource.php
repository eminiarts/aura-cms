<?php

namespace Aura\Base\Tests\Fixtures;

class GlobalSearchProcessSlowTitleResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-slow-title';

    public static string $type = 'ProcessSearchSlowTitle';

    public function title()
    {
        $marker = getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER');

        if (is_string($marker) && $marker !== '') {
            file_put_contents($marker, 'slow-title-entered');
        }

        usleep(750_000);

        return parent::title();
    }
}
