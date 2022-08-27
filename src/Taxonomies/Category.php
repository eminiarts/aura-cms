<?php

namespace Eminiarts\Aura\Taxonomies;

class Category extends Taxonomy
{
    public static string $type = 'Category';

    public static ?string $slug = 'category';

    public static $hierarchical = true;

    public static $fields = [];

    public static $attachTo = [
        'Project',
    ];
}
