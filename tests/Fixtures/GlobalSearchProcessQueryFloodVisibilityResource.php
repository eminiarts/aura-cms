<?php

namespace Aura\Base\Tests\Fixtures;

class GlobalSearchProcessQueryFloodVisibilityResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-query-flood-visibility';

    public static string $type = 'ProcessSearchQueryFloodVisibility';

    public function applyGlobalSearchVisibility($query, $user)
    {
        $marker = (string) getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER');

        for ($queryIndex = 0; $queryIndex < 10; $queryIndex++) {
            $this->getConnection()->table('global_search_process_records')->count();
            file_put_contents($marker, 'q', FILE_APPEND);
        }

        return parent::applyGlobalSearchVisibility($query, $user);
    }
}
