<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Models\Post;
use Eminiarts\Aura\Models\Scopes\TeamScope;
use Eminiarts\Aura\Resource;
use Eminiarts\Aura\Traits\CustomTable;

class Option extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'option';

    public static string $type = 'Option';

    protected $casts = [
        'value' => 'array',
    ];

    protected $fillable = ['name', 'value', 'team_id'];

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
        return '<svg class="w-5 h-5" viewBox="0 0 18 18" fill="none" stroke="currentColor" fill="none" xmlns="http://www.w3.org/2000/svg"><path d="M15.75 9a6.75 6.75 0 1 1-13.5 0 6.75 6.75 0 0 1 13.5 0Z" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"/></svg>';
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
            if (! isset($post->team_id) && auth()->user()) {
                $post->team_id = auth()->user()->current_team_id;
            }

            // unset post attributes
            unset($post->title);
            unset($post->content);
            unset($post->user_id);
            unset($post->type);
            // unset($post->team_id);
        });
    }
}
