<?php

namespace Aura\Base\Traits;

use Aura\Base\Resources\User;
use Illuminate\Support\Str;

trait InitialPostFields
{
    /**
     * Fill the initial post columns (title, content, user, team, type, slug).
     *
     * Invoked as the first step of the save pipeline registered in
     * SaveFieldAttributes::bootSaveFieldAttributes(). Deliberately not a
     * trait boot method — see the pipeline comment there for why the three
     * save steps must not be separate model-event listeners.
     */
    protected static function applyInitialPostFields($post): void
    {
        if (! $post->title && $post::usesTitle()) {
            $post->title = '';
        }

        if ($post instanceof User) {
            return;
        }

        $attributes = $post->getAttributes();
        $user = auth()->user();

        if (! $post->content && ! $post::usesCustomTable()) {
            $post->content = '';
        }

        if (! array_key_exists('user_id', $attributes) && $user) {
            $post->user_id = $user->id;
        }

        if (config('aura.teams') && ! array_key_exists('team_id', $attributes) && $user) {
            $post->team_id = $user->current_team_id;
        }

        if (! $post->type && ! $post::usesCustomTable()) {
            $post->type = $post::$type;
        }

        if ($post->getTable() == 'posts' && ! $post->slug) {
            $post->slug = Str::slug($post->title);
        }
    }
}
