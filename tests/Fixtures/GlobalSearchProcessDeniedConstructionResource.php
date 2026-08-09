<?php

namespace Aura\Base\Tests\Fixtures;

class GlobalSearchProcessDeniedConstructionResource extends GlobalSearchProcessResource
{
    public static ?string $slug = 'process-search-denied-construction';

    public static string $type = 'ProcessSearchDeniedConstruction';

    public function __construct(array $attributes = [])
    {
        $marker = getenv('AURA_GLOBAL_SEARCH_HOOK_MARKER');

        if (is_string($marker) && $marker !== '') {
            file_put_contents($marker, 'constructor', FILE_APPEND);
        }

        parent::__construct($attributes);
    }
}
