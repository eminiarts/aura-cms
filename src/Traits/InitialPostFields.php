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
     * Invoked as the first step of Resource's explicit save pipeline.
     * Deliberately not a trait boot method — see SaveFieldAttributes for why
     * the three save steps must not be separate model-event listeners.
     */
    protected static function applyInitialPostFields(
        $post,
        bool $globalWrite = false,
        bool $trustedOwnerIntent = false,
        int|string|null $trustedOwnerId = null,
        bool $trustedTeamIntent = false,
        int|string|null $trustedTeamId = null,
    ): void {
        if (! $post->title && $post::usesTitle()) {
            $post->title = '';
        }

        if ($post instanceof User || $post instanceof Team) {
            return;
        }

        $attributes = $post->getAttributes();
        $user = auth()->user();
        $connection = $post->getConnection();
        $hasTeamContext = $trustedTeamIntent || TeamScope::hasContextForConnection($connection);
        $hasOwnerContext = $trustedOwnerIntent;
        $actorUsesConnection = $user instanceof User
            && User::connectionCacheIdentity($user->getConnection())
                === User::connectionCacheIdentity($connection);
        $actorTeamId = $actorUsesConnection
            ? $user->currentTeamIdForAuthorization()
            : null;

        if ($user !== null
            && (! $user instanceof User
                || User::connectionCacheIdentity($user->getConnection())
                    !== User::connectionCacheIdentity($connection))
            && ! $globalWrite
            && ! $hasTeamContext
            && ! $hasOwnerContext) {
            throw new \LogicException('The authenticated actor and resource must use the same database connection.');
        }

        if (! $post->content && ! $post::usesCustomTable()) {
            $post->content = '';
        }

        if (! $post->exists
            && $actorUsesConnection
            && ! $trustedOwnerIntent
            && ! array_key_exists('user_id', $attributes)) {
            $post->user_id = $user->getKey();
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
            && $actorUsesConnection
            && ! array_key_exists('team_id', $attributes)) {
            $post->team_id = $actorTeamId;
        }

        static::authorizeInitialPostFieldPersistence(
            $post,
            $globalWrite,
            $trustedOwnerIntent,
            $trustedOwnerId,
            $trustedTeamIntent,
            $trustedTeamId,
        );

        if (! $post->type && ! $post::usesCustomTable()) {
            $post->type = $post::$type;
        }

        if ($post->getTable() == 'posts' && ! $post->slug) {
            $post->slug = Str::slug($post->title);
        }
    }

    protected static function authorizeInitialPostFieldPersistence(
        $post,
        bool $globalWrite = false,
        bool $trustedOwnerIntent = false,
        int|string|null $trustedOwnerId = null,
        bool $trustedTeamIntent = false,
        int|string|null $trustedTeamId = null,
    ): void {
        if ($post instanceof User || $post instanceof Team) {
            return;
        }

        $attributes = $post->getAttributes();
        $user = auth()->user();
        $connection = $post->getConnection();
        $hasTeamContext = $trustedTeamIntent || TeamScope::hasContextForConnection($connection);
        $hasOwnerContext = $trustedOwnerIntent;
        $actorUsesConnection = $user instanceof User
            && User::connectionCacheIdentity($user->getConnection())
                === User::connectionCacheIdentity($connection);
        $actorTeamId = $actorUsesConnection
            ? $user->currentTeamIdForAuthorization()
            : null;

        if (config('aura.teams')
            && ! $post->exists
            && $post->getAttribute('team_id') === null
            && ! $globalWrite
            && ! $hasTeamContext) {
            $message = $post::sharesRecordsAcrossTeams()
                ? 'Use createGlobal() or createGlobalForSystem() to create a global shared resource.'
                : 'An ordinary resource create requires a non-null team assignment.';

            throw new \LogicException($message);
        }

        $hasTenantAttribute = config('aura.teams') && array_key_exists('team_id', $attributes);
        $hasOwnerAttribute = array_key_exists('user_id', $attributes);

        if (! $post->exists
            && $post->isFillable('user_id')
            && $hasOwnerAttribute
            && $post->getAttribute('user_id') === null
            && ! $globalWrite
            && ! $hasTeamContext
            && ! $hasOwnerContext) {
            throw new \LogicException('An ordinary resource create requires a non-null owner assignment.');
        }

        if ($hasTenantAttribute
            && (! $post->exists || $post->isDirty('team_id'))
            && $attributes['team_id'] !== null) {
            if ($trustedTeamIntent && (string) $trustedTeamId !== (string) $attributes['team_id']) {
                throw new \LogicException('The resource team no longer matches the named system operation.');
            }

            $authorizedTeamId = TeamScope::currentContextTeamId($connection) ?? $actorTeamId;

            if (! $trustedTeamIntent
                && ($authorizedTeamId === null || (string) $authorizedTeamId !== (string) $attributes['team_id'])) {
                throw new \LogicException('Use createForTeamForSystem() or moveToTeamForSystem() for a foreign team assignment.');
            }
        }

        if ($hasOwnerAttribute
            && $attributes['user_id'] !== null
            && (! $post->exists || $post->isDirty('user_id'))
            && $trustedOwnerIntent
            && (string) $trustedOwnerId !== (string) $attributes['user_id']) {
            throw new \LogicException('The resource owner no longer matches the named system operation.');
        }

        if ($hasOwnerAttribute
            && $attributes['user_id'] !== null
            && (! $post->exists || $post->isDirty('user_id'))
            && ! $trustedOwnerIntent
            && ! $post::isOwnerWriteAuthorized($attributes['user_id'], $connection)) {
            throw new \LogicException('A resource owner must match the authenticated actor or an explicit named system operation.');
        }
    }
}
