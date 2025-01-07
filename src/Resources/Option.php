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
        return view('aura::components.icon.option')->render();
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
        if (config('aura.teams')) {
            static::addGlobalScope(new TeamScope);
        }

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
