<?php

namespace Eminiarts\Aura\Taxonomies;

class Tag extends Taxonomy
{
    public static $hierarchical = false;

    public static ?string $slug = 'tag';

    public static string $type = 'Tag';

    public function component()
    {
        return 'fields.tags';
    }
}
