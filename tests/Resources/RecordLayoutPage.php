<?php

namespace Aura\Base\Tests\Resources;

use Aura\Base\RecordLayout\DefinesRecordLayoutPanels;
use Aura\Base\RecordLayout\RecordLayoutPanel;
use Aura\Base\RecordLayout\RecordLayoutRegion;
use Aura\Base\Resource;
use Aura\Base\Tests\Fixtures\RecordLayout\TestPanel;

class RecordLayoutPage extends Resource implements DefinesRecordLayoutPanels
{
    public static ?string $slug = 'record-layout-page';

    public static string $type = 'RecordLayoutPage';

    public static function getFields(): array
    {
        return [[
            'name' => 'Content',
            'slug' => 'content',
            'type' => 'Aura\\Base\\Fields\\Textarea',
        ]];
    }

    public static function recordLayoutPanels(): array
    {
        return [
            new RecordLayoutPanel('header', RecordLayoutRegion::HeaderActions, TestPanel::class),
            new RecordLayoutPanel('summary', RecordLayoutRegion::LeftSummary, TestPanel::class),
            new RecordLayoutPanel('main', RecordLayoutRegion::MainContent, TestPanel::class),
            new RecordLayoutPanel('sidebar', RecordLayoutRegion::RightSidebar, TestPanel::class),
            new RecordLayoutPanel('timeline', RecordLayoutRegion::ActivityTimeline, TestPanel::class),
        ];
    }
}
