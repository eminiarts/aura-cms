<?php

namespace Aura\Base\Tests\Resources;

use Aura\Base\Resource;

class TemporalPickerPage extends Resource
{
    public static ?string $slug = 'temporal-picker-page';

    public static string $type = 'TemporalPickerPage';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'title',
            ],
            [
                'name' => 'Legacy date',
                'type' => 'Aura\\Base\\Fields\\Date',
                'slug' => 'legacy_date',
                'format' => 'Y-m-d',
            ],
            [
                'name' => 'Legacy datetime',
                'type' => 'Aura\\Base\\Fields\\Datetime',
                'slug' => 'legacy_datetime',
                'format' => 'Y-m-d H:i:s',
                'input_timezone' => 'UTC',
                'display_timezone' => 'UTC',
                'storage_timezone' => 'UTC',
            ],
        ];
    }
}
