<?php

namespace App\Aura\Traits;

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

            if (! isset($post->team_id) && auth()->user()) {
                $post->team_id = auth()->user()->current_team_id;
            }

            if (! $post->type) {
                $post->type = $post::$type;
            }

            if ($post->getTable() == 'posts' && ! $post->slug) {
                $post->slug = Str::slug($post->title);
            }

            // if (! $post->published_at) {
            //     $post->published_at = now();
            // }
        });
    }
}
