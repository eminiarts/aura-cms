<?php

namespace Aura\Base\Tests\Fixtures;

use Aura\Base\Resource;

class GlobalSearchProcessResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'process-search-record';

    public static string $type = 'ProcessSearchRecord';

    public static bool $usesMeta = false;

    protected $connection = 'process_search';

    protected $fillable = ['title', 'team_id', 'user_id'];

    protected static array $searchable = ['title'];

    protected $table = 'global_search_process_records';

    public function applyGlobalSearchVisibility($query, $user)
    {
        return $query->where('team_id', data_get($user, 'current_team_id'));
    }

    public static function getFields()
    {
        return [[
            'name' => 'Title',
            'type' => 'Aura\\Base\\Fields\\Text',
            'searchable' => true,
            'slug' => 'title',
        ]];
    }

    public function getIcon()
    {
        return '<svg viewBox="0 0 10 10"><path d="M0 0h10v10z"/></svg>';
    }

    public function newGlobalSearchQuery()
    {
        return static::query()->withoutGlobalScopes();
    }

    public function title()
    {
        return $this->title;
    }
}
