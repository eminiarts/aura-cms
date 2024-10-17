<?php

namespace Aura\Base\Resources;

use Aura\Base\Models\Post;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resource;

class Option extends Resource
{
    public static $customTable = true;

    public static $globalSearch = false;

    public static ?string $slug = 'option';

    public static string $type = 'Option';

    protected $casts = [
        'value' => 'array',
    ];

    protected $fillable = ['name', 'value', 'team_id'];

    protected static ?string $group = 'Aura';

    protected $table = 'options';

    public static function byName($name)
    {
        return static::where('name', $name)->first();
    }

    public static function getFields()
    {
        return [
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Textarea',
                'validation' => 'required',
                'on_index' => false,
                'slug' => 'value',
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }

    public function getIcon()
    {
        return '<svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor" class="size-6"> <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 6h9.75M10.5 6a1.5 1.5 0 1 1-3 0m3 0a1.5 1.5 0 1 0-3 0M3.75 6H7.5m3 12h9.75m-9.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-3.75 0H7.5m9-6h3.75m-3.75 0a1.5 1.5 0 0 1-3 0m3 0a1.5 1.5 0 0 0-3 0m-9.75 0h9.75" /> </svg>';
    }

    public static function getWidgets(): array
    {
        return [];
    }

    /**
     * The "booted" method of the model.
     *
     * @return void
     */
    protected static function booted()
    {
        static::addGlobalScope(new TeamScope);

        static::saving(function ($option) {

            if (config('aura.teams') && ! isset($option->team_id) && auth()->user()) {
                $option->team_id = auth()->user()->current_team_id;
            }

            // unset post attributes
            unset($option->title);
            unset($option->content);
            unset($option->user_id);
            unset($option->type);
        });
    }
}
