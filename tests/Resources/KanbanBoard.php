<?php

namespace Aura\Base\Tests\Resources;

use Aura\Base\Fields\Status;
use Aura\Base\Fields\Text;
use Aura\Base\Resource;

class KanbanBoard extends Resource
{
    public static $singularName = 'Kanban Card';

    public static ?string $slug = 'kanban-board';

    public static string $type = 'KanbanBoard';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'slug' => 'title',
                'type' => Text::class,
            ],
            [
                'name' => 'Summary',
                'slug' => 'content',
                'type' => Text::class,
            ],
            [
                'name' => 'Stage',
                'slug' => 'status',
                'type' => Status::class,
                'options' => [
                    ['key' => 'lead', 'value' => 'Lead', 'color' => 'bg-gray-500'],
                    ['key' => 'won', 'value' => 'Won', 'color' => 'bg-green-500'],
                    ['key' => 'lost', 'value' => 'Lost', 'color' => 'bg-red-500'],
                ],
            ],
        ];
    }

    public function kanbanSettings(): array
    {
        return [
            'enabled' => true,
            'group_field' => 'status',
            'columns' => ['lead', 'won', 'lost'],
            'card_title' => 'title',
            'card_subtitle' => 'content',
            'order_by' => ['field' => 'title', 'direction' => 'asc'],
            'show_empty_columns' => true,
        ];
    }
}
