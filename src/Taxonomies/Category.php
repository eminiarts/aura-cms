<?php

namespace Eminiarts\Aura\Taxonomies;

class Category extends Taxonomy
{
    public static $attachTo = [
        'Project',
    ];

    public static $hierarchical = true;

    public static ?string $slug = 'category';

    public static string $type = 'Category';
}
