<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Models\Scopes\TeamScope;
use Eminiarts\Aura\Resource;

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

    public static function getFields()
    {
        return [
            [
                'name' => 'Name',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'slug' => 'name',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Value',
                'type' => 'Eminiarts\\Aura\\Fields\\Textarea',
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
        return '<svg class="w-6 h-6" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M3 8L15 8M15 8C15 9.65686 16.3431 11 18 11C19.6569 11 21 9.65685 21 8C21 6.34315 19.6569 5 18 5C16.3431 5 15 6.34315 15 8ZM9 16L21 16M9 16C9 17.6569 7.65685 19 6 19C4.34315 19 3 17.6569 3 16C3 14.3431 4.34315 13 6 13C7.65685 13 9 14.3431 9 16Z" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>';
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
        static::addGlobalScope(new TeamScope());

        static::saving(function ($post) {
            if (config('aura.teams') && ! isset($post->team_id) && auth()->user()) {
                $post->team_id = auth()->user()->current_team_id;
            }

            // unset post attributes
            unset($post->title);
            unset($post->content);
            unset($post->user_id);
            unset($post->type);
        });
    }
}
