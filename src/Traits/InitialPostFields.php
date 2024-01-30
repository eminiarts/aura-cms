<?php

namespace Eminiarts\Aura\Traits;

use Illuminate\Support\Str;

trait InitialPostFields
{
    protected static function bootInitialPostFields()
    {
        static::saving(function ($post) {
            if (! $post->title && $post::usesTitle()) {
                $post->title = '';
            }

            if (! $post->content && ! $post::usesCustomTable()) {
                $post->content = '';
            }

            if (! $post->user_id && auth()->user()) {
                $post->user_id = auth()->user()->id;
            }

            if (config('aura.teams') && ! isset($post->team_id) && auth()->user()) {
                $post->team_id = auth()->user()->current_team_id;
            }

            if (! $post->type && ! $post::usesCustomTable()) {
                $post->type = $post::$type;
            }

            if ($post->getTable() == 'posts' && ! $post->slug) {
                $post->slug = Str::slug($post->title);
            }
        });
    }
}
