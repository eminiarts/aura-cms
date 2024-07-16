<?php

namespace Aura\Base\Resources;

use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Tag extends Resource
{
    public static $hierarchical = false;

    public static ?string $slug = 'tag';

    public static string $type = 'Tag';

    protected static ?string $group = 'Aura';

    public function component()
    {
        return 'fields.tags';
    }

    public function title()
    {
        return $this->title;
    }

    public static function getFields()
    {
        return [
            'name' => [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'title',
            ],

            'description' => [
                'label' => 'Text',
                'name' => 'Beschreibung',
                'type' => 'Aura\\Base\\Fields\\Text',
                'conditional_logic' => [],
                'slug' => 'description',
            ],
            'slug' => [
                'name' => 'Slug',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'slug',
            ],
            'count' => [
                'name' => 'Count',
                'type' => 'Aura\\Base\\Fields\\Number',
                'conditional_logic' => [],
                'slug' => 'count',
                'on_forms' => false,
            ],

        ];
    }

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M9.568 3H5.25A2.25 2.25 0 0 0 3 5.25v4.318c0 .597.237 1.17.659 1.591l9.581 9.581c.699.699 1.78.872 2.607.33a18.095 18.095 0 0 0 5.223-5.223c.542-.827.369-1.908-.33-2.607L11.16 3.66A2.25 2.25 0 0 0 9.568 3Z" /> <path stroke-linecap="round" stroke-linejoin="round" d="M6 6h.008v.008H6V6Z" /> </svg>';
    }

    /**
     * Get all of the posts that are assigned this tag.
     */
    // public function posts(): MorphToMany
    // {
    //     return $this->morphedByMany(Post::class, 'taggable');
    // }
}
