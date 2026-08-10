<?php

namespace Aura\Base\Preferences;

use Aura\Base\Aura;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Services\VersionedCache;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Database\Connection;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;

final readonly class PreferenceManager
{
    public function __construct(private PreferenceRegistry $registry) {}

    public function get(string $key, PreferenceContext $context): mixed
    {
        return $this->resolve($key, $context)->value;
    }

    public function reset(
        string $key,
        PreferenceScope $scope,
        PreferenceContext $context,
        ?User $actor,
    ): void {
        $definition = $this->registry->get($key);
        $authorizedContext = $this->authorizeWrite($definition, $scope, $context, $actor);
        $this->deleteEntry($definition, $scope, $authorizedContext);
    }

    public function resolve(string $key, PreferenceContext $context): PreferenceResult
    {
        $definition = $this->registry->get($key);
        $contexts = $definition->resourceAware && $context->resource !== null
            ? [$context, $context->forApplication()]
            : [$context->forApplication()];

        foreach ($contexts as $candidateContext) {
            foreach ([PreferenceScope::User, PreferenceScope::Team, PreferenceScope::Everyone] as $scope) {
                if (! $definition->supports($scope)) {
                    continue;
                }

                $entry = $this->readEntry($definition, $scope, $candidateContext);

                if ($entry['found']) {
                    $definition->validate($entry['value']);

                    return new PreferenceResult(
                        $entry['value'],
                        $scope,
                        $candidateContext->resource !== null,
                        isLegacy: $entry['legacy'],
                    );
                }
            }
        }

        return new PreferenceResult($definition->default, null, false, isDefault: true);
    }

    public function set(
        string $key,
        mixed $value,
        PreferenceScope $scope,
        PreferenceContext $context,
        ?User $actor,
    ): void {
        $definition = $this->registry->get($key);
        $definition->validate($value);
        $authorizedContext = $this->authorizeWrite($definition, $scope, $context, $actor);
        $this->writeEntry($definition, $scope, $authorizedContext, $value);
    }

    private function authenticatedActor(
        ?User $actor,
        PreferenceScope $scope,
        PreferenceContext $context,
    ): User {
        if (! $this->isStableUserReference($actor)) {
            throw new AuthorizationException('An authentic explicit actor is required to write preferences.');
        }

        if ($scope === PreferenceScope::User
            && ! $this->isStableUserReference($context->user)) {
            throw new AuthorizationException('The explicit preference target is not authentic.');
        }

        if (config('aura.teams')
            && in_array($scope, [PreferenceScope::User, PreferenceScope::Team], true)
            && ! $this->isStableTeamReference($context->team)) {
            throw new AuthorizationException('The explicit preference team target is not authentic.');
        }

        $authenticatedIdentifier = $this->authenticatedIdentifier();

        if ($authenticatedIdentifier === null
            || (string) $actor->getKey() !== (string) $authenticatedIdentifier
            || ($scope === PreferenceScope::User
                && (string) $context->user?->getKey() !== (string) $authenticatedIdentifier)) {
            throw new AuthorizationException('The explicit preference actor does not match the authenticated principal.');
        }

        $authenticatedUser = $this->persistedUser($authenticatedIdentifier);

        if ($authenticatedUser === null) {
            throw new AuthorizationException('The authenticated preference actor is not persisted.');
        }

        return $authenticatedUser;
    }

    private function authenticatedIdentifier(): string|int|null
    {
        $guard = Auth::guard();
        $authenticatedUser = $guard->user();

        if (! $authenticatedUser instanceof User) {
            return null;
        }

        $identifier = $authenticatedUser->getAuthIdentifier();

        if ($guard instanceof SessionGuard) {
            $sessionIdentifier = $guard->getSession()->get($guard->getName());

            if ($sessionIdentifier === null || $identifier === null
                || (string) $sessionIdentifier !== (string) $identifier) {
                return null;
            }

            return $sessionIdentifier;
        }

        return is_string($identifier) || is_int($identifier) ? $identifier : null;
    }

    private function authorizeWrite(
        PreferenceDefinition $definition,
        PreferenceScope $scope,
        PreferenceContext $context,
        ?User $actor,
    ): PreferenceContext {
        if (! $definition->supports($scope)) {
            throw new InvalidArgumentException("Preference [{$definition->key}] does not support {$scope->value} scope.");
        }

        if ($scope === PreferenceScope::Team && ! config('aura.teams')) {
            throw new InvalidArgumentException('Team preference writes are unavailable while teams are disabled.');
        }

        $persistedActor = $this->authenticatedActor($actor, $scope, $context);

        $persistedUser = $scope === PreferenceScope::User ? $persistedActor : null;
        $persistedTeam = in_array($scope, [PreferenceScope::User, PreferenceScope::Team], true)
            && config('aura.teams')
                ? $this->persistedTeam($context->team)
                : null;

        if (config('aura.teams')
            && in_array($scope, [PreferenceScope::User, PreferenceScope::Team], true)
            && ($persistedTeam === null || ! $this->matchesCanonicalTeam($context->team, $persistedTeam))) {
            throw new AuthorizationException('The explicit preference team target is not authentic.');
        }

        $isGlobalAdmin = $persistedActor->isAuraGlobalAdmin();

        $authorized = match ($scope) {
            PreferenceScope::User => $persistedUser !== null
                && $this->sameKey($persistedActor, $persistedUser)
                && $this->canUseTeam($persistedActor, $persistedTeam, $context),
            PreferenceScope::Team => $persistedTeam !== null
                && ($isGlobalAdmin || $this->ownsPersistedTeam($persistedActor, $persistedTeam)),
            PreferenceScope::Everyone => $isGlobalAdmin,
        };

        if (! $authorized) {
            throw new AuthorizationException("Not authorized to write {$scope->value} preferences.");
        }

        return new PreferenceContext(
            $context->application,
            $persistedUser ?? $persistedActor,
            $persistedTeam,
            $context->resource,
        );
    }

    private function canUseTeam(User $actor, ?Team $team, PreferenceContext $context): bool
    {
        if (! config('aura.teams')) {
            return $context->team === null;
        }

        if ($team === null) {
            return false;
        }

        return $actor->isAuraGlobalAdmin()
            || $actor->teams()->whereKey($team->getKey())->exists();
    }

    private function deleteEntry(
        PreferenceDefinition $definition,
        PreferenceScope $scope,
        PreferenceContext $context,
    ): void {
        $storageKeys = [$this->storageKey($definition, $context)];

        foreach ($definition->legacyKeys as $legacyKey) {
            $storageKeys[] = $this->expandLegacyKey($legacyKey, $context);
        }

        foreach (array_unique($storageKeys) as $storageKey) {
            match ($scope) {
                PreferenceScope::User => $context->user->deleteOptionForTeam(
                    $storageKey,
                    config('aura.teams') ? $context->team?->getKey() : null,
                ),
                PreferenceScope::Team => $context->team->deleteOption($storageKey),
                PreferenceScope::Everyone => $this->deleteEveryoneEntry($storageKey),
            };
        }
    }

    private function deleteEveryoneEntry(string $storageKey): void
    {
        $connection = $this->optionConnection();

        $connection->transaction(function () use ($storageKey): void {
            $option = new Option;
            $query = Option::withoutGlobalScopes()
                ->whereNull($option->getQualifiedDeletedAtColumn())
                ->where('name', $storageKey);

            if (config('aura.teams')) {
                $query->where('team_id', Option::EVERYONE_TEAM_ID);
            }

            $query->lockForUpdate()->first()?->deleteQuietly();
        });

        Aura::clearGlobalOptionCache($connection);
    }

    private function expandLegacyKey(string $key, PreferenceContext $context): string
    {
        return str_replace(
            ['{application}', '{resource}'],
            [$context->application, $context->resource ?? ''],
            $key,
        );
    }

    private function isStableTeamReference(?Team $team): bool
    {
        $prototype = $this->teamPrototype();

        if ($team === null || $prototype === null || $team::class !== $prototype::class
            || $team->getTable() !== $prototype->getTable() || ! $team->exists) {
            return false;
        }

        $key = $team->getKey();
        $originalKey = $team->getRawOriginal($team->getKeyName());
        if ($key === null || $key === '' || $originalKey === null || $originalKey === ''
            || (string) $key !== (string) $originalKey
            || Option::isEveryoneTeamId($key)) {
            return false;
        }

        return $team->getConnection()->getName() === $this->optionConnection()->getName();
    }

    private function isStableUserReference(?User $user): bool
    {
        $prototype = $this->userPrototype();

        if ($user === null || $prototype === null || $user::class !== $prototype::class
            || $user->getTable() !== $prototype->getTable() || ! $user->exists) {
            return false;
        }

        $key = $user->getKey();
        $originalKey = $user->getRawOriginal($user->getKeyName());

        if ($key === null || $key === '' || $originalKey === null || $originalKey === ''
            || (string) $key !== (string) $originalKey) {
            return false;
        }

        return $user->getConnection()->getName() === $this->optionConnection()->getName();
    }

    private function matchesCanonicalTeam(?Team $candidate, Team $canonical): bool
    {
        if ($candidate === null || $candidate::class !== $canonical::class) {
            return false;
        }

        foreach ([$candidate->getKeyName(), 'user_id', 'name', 'created_at', 'updated_at'] as $attribute) {
            if ($candidate->getAttribute($attribute) != $canonical->getAttribute($attribute)) {
                return false;
            }
        }

        return true;
    }

    private function optionConnection(): Connection
    {
        return (new Option)->getConnection();
    }

    private function ownsPersistedTeam(User $actor, Team $team): bool
    {
        $ownerId = $team->getAttribute($actor->getForeignKey());

        return $ownerId !== null
            && $ownerId !== ''
            && (string) $actor->getKey() === (string) $ownerId;
    }

    private function persistedTeam(?Team $team): ?Team
    {
        if (! $this->isStableTeamReference($team)) {
            return null;
        }

        $prototype = $this->teamPrototype();

        if ($prototype === null) {
            return null;
        }

        $prototype->setConnection($this->optionConnection()->getName());

        return $prototype->newQuery()
            ->withoutGlobalScope(TeamScope::class)
            ->whereKey($team->getKey())
            ->first();
    }

    private function persistedUser(string|int $identifier): ?User
    {
        $prototype = $this->userPrototype();

        if ($prototype === null) {
            return null;
        }

        $prototype->setConnection($this->optionConnection()->getName());

        return $prototype->newQuery()
            ->withoutGlobalScope(TeamScope::class)
            ->where($prototype->getAuthIdentifierName(), $identifier)
            ->first();
    }

    /** @return array{found: bool, value: mixed, legacy: bool} */
    private function readEntry(
        PreferenceDefinition $definition,
        PreferenceScope $scope,
        PreferenceContext $context,
    ): array {
        $entry = $this->readStorageKey($scope, $context, $this->storageKey($definition, $context));

        if ($entry['found']) {
            return [...$entry, 'legacy' => false];
        }

        foreach ($definition->legacyKeys as $legacyKey) {
            $entry = $this->readStorageKey($scope, $context, $this->expandLegacyKey($legacyKey, $context));

            if ($entry['found']) {
                return [...$entry, 'legacy' => true];
            }
        }

        return ['found' => false, 'value' => null, 'legacy' => false];
    }

    /** @return array{found: bool, value: mixed} */
    private function readEveryoneEntry(string $storageKey): array
    {
        return VersionedCache::remember(
            'option.global',
            VersionedCache::identity('preference.everyone', $storageKey),
            now()->addHour(),
            function () use ($storageKey): array {
                $option = new Option;
                $query = Option::withoutGlobalScopes()
                    ->whereNull($option->getQualifiedDeletedAtColumn())
                    ->where('name', $storageKey);

                if (config('aura.teams')) {
                    $query->where('team_id', Option::EVERYONE_TEAM_ID);
                }

                $record = $query->first(['value']);

                return ['found' => $record !== null, 'value' => $record?->getAttributeValue('value')];
            },
            $this->optionConnection(),
        );
    }

    /** @return array{found: bool, value: mixed} */
    private function readStorageKey(
        PreferenceScope $scope,
        PreferenceContext $context,
        string $storageKey,
    ): array {
        return match ($scope) {
            PreferenceScope::User => $context->user?->getOptionEntryForTeam(
                $storageKey,
                config('aura.teams') ? $context->team?->getKey() : null,
            ) ?? ['found' => false, 'value' => null],
            PreferenceScope::Team => config('aura.teams')
                ? ($context->team?->getOptionEntryExplicit($storageKey) ?? ['found' => false, 'value' => null])
                : ['found' => false, 'value' => null],
            PreferenceScope::Everyone => $this->readEveryoneEntry($storageKey),
        };
    }

    private function sameKey(User $first, User $second): bool
    {
        return (string) $first->getKey() === (string) $second->getKey();
    }

    private function storageKey(PreferenceDefinition $definition, PreferenceContext $context): string
    {
        $resource = $definition->resourceAware && $context->resource !== null
            ? 'resource:'.$context->resource
            : 'application';

        return 'preference.v1.'.VersionedCache::identity(
            'preference',
            $context->application,
            $resource,
            $definition->key,
        );
    }

    private function teamPrototype(): ?Team
    {
        $teamClass = config('aura.resources.team', Team::class);

        if (! is_string($teamClass) || ! is_a($teamClass, Team::class, true)) {
            return null;
        }

        return new $teamClass;
    }

    private function userPrototype(): ?User
    {
        $userClass = config('aura.resources.user', User::class);

        if (! is_string($userClass) || ! is_a($userClass, User::class, true)) {
            return null;
        }

        return new $userClass;
    }

    private function writeEncodedFloat(string $name, string|int|null $teamId, mixed $value): void
    {
        if (! is_float($value) || ! is_finite($value)) {
            throw new InvalidArgumentException('Preference float storage requires a finite float.');
        }

        $option = new Option;
        $query = $this->optionConnection()->table($option->getTable())->where('name', $name);

        if (config('aura.teams')) {
            $query->where('team_id', $teamId);
        }

        if ((clone $query)->count() !== 1) {
            throw new InvalidArgumentException('Preference float storage target is not canonical.');
        }

        $query->update([
            'value' => json_encode($value, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR),
        ]);
    }

    private function writeEntry(
        PreferenceDefinition $definition,
        PreferenceScope $scope,
        PreferenceContext $context,
        mixed $value,
    ): void {
        $storageKey = $this->storageKey($definition, $context);
        $preserveFloat = $definition->type === PreferenceValueType::Float && is_float($value);

        match ($scope) {
            PreferenceScope::User => $this->writeUserEntry($context, $storageKey, $value, $preserveFloat),
            PreferenceScope::Team => $this->writeTeamEntry($context, $storageKey, $value, $preserveFloat),
            PreferenceScope::Everyone => $this->writeEveryoneEntry($storageKey, $value, $preserveFloat),
        };
    }

    private function writeEveryoneEntry(string $storageKey, mixed $value, bool $preserveFloat): void
    {
        $connection = $this->optionConnection();

        $connection->transaction(function () use ($storageKey, $value, $preserveFloat): void {
            $attributes = ['name' => $storageKey];

            if (config('aura.teams')) {
                $attributes['team_id'] = Option::EVERYONE_TEAM_ID;
            }

            $record = Option::withoutGlobalScopes()
                ->withTrashed()
                ->where($attributes)
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                $record = Option::withoutGlobalScopes()->newModelInstance($attributes);
            }

            if ($preserveFloat) {
                $record->setRawAttributes(array_replace(
                    $record->getAttributes(),
                    ['value' => json_encode($value, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR)],
                ));
            } else {
                $record->setAttribute('value', $value);
            }

            if ($record->trashed()) {
                $record->setAttribute($record->getDeletedAtColumn(), null);
            }

            $record->saveQuietly();
        });

        Aura::clearGlobalOptionCache($connection);
    }

    private function writeTeamEntry(
        PreferenceContext $context,
        string $storageKey,
        mixed $value,
        bool $preserveFloat,
    ): void {
        $connection = $this->optionConnection();

        $connection->transaction(function () use ($context, $storageKey, $value, $preserveFloat): void {
            $context->team->updateOption($storageKey, $value);

            if ($preserveFloat) {
                $this->writeEncodedFloat(
                    'team.'.$context->team->getKey().'.'.$storageKey,
                    $context->team->getKey(),
                    $value,
                );
            }
        });
    }

    private function writeUserEntry(
        PreferenceContext $context,
        string $storageKey,
        mixed $value,
        bool $preserveFloat,
    ): void {
        $connection = $this->optionConnection();
        $teamId = config('aura.teams') ? $context->team?->getKey() : null;

        $connection->transaction(function () use ($context, $storageKey, $value, $preserveFloat, $teamId): void {
            $context->user->updateOptionForTeam($storageKey, $value, $teamId);

            if ($preserveFloat) {
                $this->writeEncodedFloat(
                    User::optionNamePrefixFor($context->user->getKey()).$storageKey,
                    $teamId,
                    $value,
                );
            }
        });
    }
}
