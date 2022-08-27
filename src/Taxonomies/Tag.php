<?php

namespace Eminiarts\Aura\Taxonomies;

class Tag extends Taxonomy
{
    public static string $type = 'Tag';

    public static ?string $slug = 'tag';

    public static $hierarchical = false;

    public function component()
    {
        return 'fields.tags';
    }

    public static $attachTo = [
        'Invoice',
        'Project',
        'Post',
    ];
}
