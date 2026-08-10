<?php

namespace Aura\Base\Tests\Resources;

use Aura\Base\Resource;

/**
 * Browser-test resource: one single-select and one multi-select Image
 * field, both opening the Media Picker.
 */
class GalleryPage extends Resource
{
    public array $actions = [
        'markReviewed' => [
            'label' => 'Mark reviewed',
            'ability' => 'update',
        ],
    ];

    public static ?string $slug = 'gallery-page';

    public static string $type = 'GalleryPage';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'title',
            ],
            [
                'name' => 'Hero',
                'type' => 'Aura\\Base\\Fields\\Image',
                'validation' => '',
                'slug' => 'hero',
                'use_media_manager' => true,
                'max_files' => 1,
            ],
            [
                'name' => 'Gallery',
                'type' => 'Aura\\Base\\Fields\\Image',
                'validation' => '',
                'slug' => 'gallery',
                'use_media_manager' => true,
            ],
        ];
    }

    public function markReviewed(): void
    {
        $this->forceFill(['content' => 'reviewed'])->save();
    }
}
