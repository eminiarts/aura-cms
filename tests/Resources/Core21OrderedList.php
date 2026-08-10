<?php

namespace Aura\Base\Tests\Resources;

use Aura\Base\Contracts\DeclaresTableRowOrdering;
use Aura\Base\Fields\Text;
use Aura\Base\Resource;
use Aura\Base\Table\TableRowOrdering;

class Core21OrderedList extends Resource implements DeclaresTableRowOrdering
{
    public static $singularName = 'Ordered item';

    public static ?string $slug = 'core21-ordered-list';

    public static string $type = 'Core21OrderedList';

    public function defaultTableSort(): string
    {
        return 'created_at';
    }

    public function defaultTableSortDirection(): string
    {
        return 'asc';
    }

    public static function getFields(): array
    {
        return [
            ['name' => 'Title', 'slug' => 'title', 'type' => Text::class],
            ['name' => 'Summary', 'slug' => 'content', 'type' => Text::class],
        ];
    }

    public function tableRowOrdering(): TableRowOrdering
    {
        return TableRowOrdering::make('created_at');
    }
}
