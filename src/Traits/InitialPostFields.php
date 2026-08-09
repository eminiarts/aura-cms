<?php

namespace Aura\Base\Traits;

use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\Team;
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

        if ($post instanceof User || $post instanceof Team) {
            return;
        }

        $attributes = $post->getAttributes();
        $user = auth()->user();
        $globalWrite = $post::isGlobalWriteInProgress();

        if (! $post->content && ! $post::usesCustomTable()) {
            $post->content = '';
        }

        if (! $post->exists
            && $user
            && (! array_key_exists('user_id', $attributes) || ($attributes['user_id'] === null && ! $globalWrite))) {
            $post->user_id = $user->id;
        }

        if (config('aura.teams')
            && $post->exists
            && $post::sharesRecordsAcrossTeams()
            && $post->isDirty('team_id')
            && $post->getOriginal('team_id') !== null
            && $post->getAttribute('team_id') === null
            && ! $globalWrite) {
            throw new \LogicException('Use promoteToGlobal() to change a shared resource to global scope.');
        }

        if (config('aura.teams')
            && ! $post->exists
            && $user
            && (! array_key_exists('team_id', $attributes) || ($attributes['team_id'] === null && ! $globalWrite))) {
            $post->team_id = $user->current_team_id;
        }

        if (config('aura.teams')
            && ! $post->exists
            && $post::sharesRecordsAcrossTeams()
            && $post->getAttribute('team_id') === null
            && ! $globalWrite) {
            throw new \LogicException('Use createGlobal() or createGlobalForSystem() to create a global shared resource.');
        }

        $attributes = $post->getAttributes();
        $hasTenantAttribute = config('aura.teams') && array_key_exists('team_id', $attributes);
        $hasOwnerAttribute = array_key_exists('user_id', $attributes);

        if ($hasTenantAttribute
            && (! $post->exists || $post->isDirty('team_id'))
            && $attributes['team_id'] !== null) {
            $authorizedTeamId = TeamScope::currentContextTeamId() ?? data_get($user, 'current_team_id');

            if ($authorizedTeamId === null || (string) $authorizedTeamId !== (string) $attributes['team_id']) {
                throw new \LogicException('Use createForTeamForSystem() or moveToTeamForSystem() for a foreign team assignment.');
            }
        }

        if ($hasOwnerAttribute
            && $attributes['user_id'] !== null
            && (! $post->exists || $post->isDirty('user_id'))
            && ! $post::isOwnerWriteAuthorized($attributes['user_id'])) {
            throw new \LogicException('A resource owner must match the authenticated actor or an explicit trusted owner context.');
        }

        if (! $post->type && ! $post::usesCustomTable()) {
            $post->type = $post::$type;
        }

        if ($post->getTable() == 'posts' && ! $post->slug) {
            $post->slug = Str::slug($post->title);
        }
    }
}
