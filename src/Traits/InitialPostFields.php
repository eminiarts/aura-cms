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
        $ownerColumn = $post::getOwnerColumn();
        $teamColumn = $post::getTeamColumn();
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

        if ($ownerColumn !== null
            && ! $post->exists
            && $actorUsesConnection
            && ! $trustedOwnerIntent
            && ! array_key_exists($ownerColumn, $attributes)) {
            $post->setAttribute($ownerColumn, $user->getKey());
        }

        if ($teamColumn !== null
            && $post->exists
            && $post::sharesRecordsAcrossTeams()
            && $post->isDirty($teamColumn)
            && $post->getOriginal($teamColumn) !== null
            && $post->getAttribute($teamColumn) === null
            && ! $globalWrite) {
            throw new \LogicException('Use promoteToGlobal() to change a shared resource to global scope.');
        }

        if ($teamColumn !== null
            && ! $post->exists
            && $actorUsesConnection
            && ! array_key_exists($teamColumn, $attributes)) {
            $post->setAttribute($teamColumn, $actorTeamId);
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
        $ownerColumn = $post::getOwnerColumn();
        $teamColumn = $post::getTeamColumn();
        $hasTeamContext = $trustedTeamIntent || TeamScope::hasContextForConnection($connection);
        $hasOwnerContext = $trustedOwnerIntent;
        $actorUsesConnection = $user instanceof User
            && User::connectionCacheIdentity($user->getConnection())
                === User::connectionCacheIdentity($connection);
        $actorTeamId = $actorUsesConnection
            ? $user->currentTeamIdForAuthorization()
            : null;

        if ($teamColumn !== null
            && ! $post->exists
            && $post->getAttribute($teamColumn) === null
            && ! $globalWrite
            && ! $hasTeamContext) {
            $message = $post::sharesRecordsAcrossTeams()
                ? 'Use createGlobal() or createGlobalForSystem() to create a global shared resource.'
                : 'An ordinary resource create requires a non-null team assignment.';

            throw new \LogicException($message);
        }

        $hasTenantAttribute = $teamColumn !== null && array_key_exists($teamColumn, $attributes);
        $hasOwnerAttribute = $ownerColumn !== null && array_key_exists($ownerColumn, $attributes);

        if (! $post->exists
            && $ownerColumn !== null
            && $post->isFillable($ownerColumn)
            && $hasOwnerAttribute
            && $post->getAttribute($ownerColumn) === null
            && ! $globalWrite
            && ! $hasTeamContext
            && ! $hasOwnerContext) {
            throw new \LogicException('An ordinary resource create requires a non-null owner assignment.');
        }

        if ($hasTenantAttribute
            && (! $post->exists || $post->isDirty($teamColumn))
            && $attributes[$teamColumn] !== null) {
            if ($trustedTeamIntent && (string) $trustedTeamId !== (string) $attributes[$teamColumn]) {
                throw new \LogicException('The resource team no longer matches the named system operation.');
            }

            $authorizedTeamId = TeamScope::currentContextTeamId($connection) ?? $actorTeamId;

            if (! $trustedTeamIntent
                && ($authorizedTeamId === null || (string) $authorizedTeamId !== (string) $attributes[$teamColumn])) {
                throw new \LogicException('Use createForTeamForSystem() or moveToTeamForSystem() for a foreign team assignment.');
            }
        }

        if ($hasOwnerAttribute
            && $attributes[$ownerColumn] !== null
            && (! $post->exists || $post->isDirty($ownerColumn))
            && $trustedOwnerIntent
            && (string) $trustedOwnerId !== (string) $attributes[$ownerColumn]) {
            throw new \LogicException('The resource owner no longer matches the named system operation.');
        }

        if ($hasOwnerAttribute
            && $attributes[$ownerColumn] !== null
            && (! $post->exists || $post->isDirty($ownerColumn))
            && ! $trustedOwnerIntent
            && ! $post::isOwnerWriteAuthorized($attributes[$ownerColumn], $connection)) {
            throw new \LogicException('A resource owner must match the authenticated actor or an explicit named system operation.');
        }
    }
}
