<?php

namespace Aura\Base\Preferences;

use Aura\Base\Aura;
use Aura\Base\Exceptions\OptionOwnerIdentityException;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Services\VersionedCache;
use DateTimeInterface;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Auth\SessionGuard;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\UniqueConstraintViolationException;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use RuntimeException;

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

        if ($authenticatedIdentifier === null) {
            throw new AuthorizationException('The explicit preference actor does not match the authenticated principal.');
        }

        $userPrototype = $this->userPrototype();

        if ($userPrototype === null) {
            throw new AuthorizationException('The authenticated preference actor is not persisted.');
        }

        if ($userPrototype->getAuthIdentifierName() === $userPrototype->getKeyName()
            && ((string) $actor->getKey() !== (string) $authenticatedIdentifier
                || ($scope === PreferenceScope::User
                    && (string) $context->user?->getKey() !== (string) $authenticatedIdentifier))) {
            throw new AuthorizationException('The explicit preference actor does not match the authenticated principal.');
        }

        $authenticatedUser = $this->persistedUser($authenticatedIdentifier);

        if ($authenticatedUser === null
            || (string) $actor->getKey() !== (string) $authenticatedUser->getKey()
            || ($scope === PreferenceScope::User
                && (string) $context->user?->getKey() !== (string) $authenticatedUser->getKey())) {
            throw new AuthorizationException('The explicit preference actor does not match the authenticated principal.');
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
            && ($persistedTeam === null
                || Option::isEveryoneTeamId($persistedTeam->getKey())
                || ! $this->matchesCanonicalTeam($context->team, $persistedTeam))) {
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

        $connection = $this->optionConnection();
        $storageKeys = array_values(array_unique($storageKeys));

        $connection->transaction(function () use ($context, $scope, $storageKeys): void {
            foreach ($storageKeys as $storageKey) {
                match ($scope) {
                    PreferenceScope::User => $this->deleteUserEntry($context, $storageKey),
                    PreferenceScope::Team => $this->deleteTeamEntry($context, $storageKey),
                    PreferenceScope::Everyone => $this->deleteEveryoneEntry($storageKey),
                };
            }
        });

        match ($scope) {
            PreferenceScope::User => User::clearOptionCacheForTeam(
                $context->user->getKey(),
                config('aura.teams') ? $context->team?->getKey() : 'global',
                $connection,
            ),
            PreferenceScope::Team => Team::clearOptionCacheForTeam($context->team->getKey(), $connection),
            PreferenceScope::Everyone => Aura::clearGlobalOptionCache($connection),
        };
    }

    private function deleteEveryoneEntry(string $storageKey): void
    {
        $option = new Option;
        $query = $this->optionQuery()
            ->whereNull($option->getQualifiedDeletedAtColumn())
            ->where('name', $storageKey);

        if (config('aura.teams')) {
            $query->where('team_id', Option::EVERYONE_TEAM_ID);
        }

        $record = $query->lockForUpdate()->first();

        if ($record !== null) {
            $this->requireSuccessfulOptionMutation($record->delete());
        }
    }

    private function deleteTeamEntry(PreferenceContext $context, string $storageKey): void
    {
        $record = $this->optionQuery()
            ->whereNull((new Option)->getQualifiedDeletedAtColumn())
            ->where('team_id', $context->team->getKey())
            ->where('name', 'team.'.$context->team->getKey().'.'.$storageKey)
            ->lockForUpdate()
            ->first();

        if ($record !== null) {
            $this->requireSuccessfulOptionMutation($record->delete());
        }
    }

    private function deleteUserEntry(PreferenceContext $context, string $storageKey): void
    {
        $query = $this->optionQuery()
            ->whereNull((new Option)->getQualifiedDeletedAtColumn());

        if (config('aura.teams')) {
            $query->where('team_id', $context->team?->getKey());
        }

        $optionNames = $this->userOptionNames($context->user, $storageKey);
        $records = $query
            ->whereIn('name', $optionNames)
            ->lockForUpdate()
            ->get()
            ->map(fn (Option $record): Option => $this->verifiedUserOption(
                $record,
                $context->user,
                $optionNames,
            ));

        foreach ($records as $record) {
            $this->requireSuccessfulOptionMutation($record->delete());
        }
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
        if ($candidate === null || $candidate::class !== $canonical::class
            || (string) $candidate->getKey() !== (string) $canonical->getKey()) {
            return false;
        }

        foreach (['user_id', 'name', 'created_at', 'updated_at'] as $attribute) {
            if ($this->normalizedTeamAttribute($candidate, $attribute)
                !== $this->normalizedTeamAttribute($canonical, $attribute)) {
                return false;
            }
        }

        return true;
    }

    private function normalizedTeamAttribute(Team $team, string $attribute): string
    {
        $value = $team->getAttribute($attribute);

        return match (true) {
            $value === null => 'null',
            is_int($value) => 'int:'.$value,
            is_string($value) => 'string:'.$value,
            $value instanceof DateTimeInterface => 'datetime:'.$value->format('Y-m-d\TH:i:s.uP'),
            default => 'invalid:'.get_debug_type($value),
        };
    }

    private function optionConnection(): Connection
    {
        $authenticatedUser = Auth::user();

        if ($authenticatedUser instanceof User) {
            return $authenticatedUser->getConnection();
        }

        return (new Option)->getConnection();
    }

    /** @return Builder<Option> */
    private function optionQuery(): Builder
    {
        return Option::on($this->optionConnection()->getName())->withoutGlobalScopes();
    }

    private function ownsPersistedTeam(User $actor, Team $team): bool
    {
        $ownerId = $team->getAttribute('user_id');

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

    private function persistEncodedFloat(Option $record, mixed $value): void
    {
        if (! is_float($value) || ! is_finite($value)) {
            throw new InvalidArgumentException('Preference float storage requires a finite float.');
        }

        $record->setRawAttributes(array_replace(
            $record->getAttributes(),
            ['value' => json_encode($value, JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR)],
        ));
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
                $query = $this->optionQuery()
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

    private function requireSuccessfulOptionMutation(?bool $succeeded): void
    {
        if ($succeeded !== true) {
            throw new RuntimeException('Preference option persistence was vetoed.');
        }
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

    /** @return array<int, string> */
    private function userOptionNames(User $user, string $option): array
    {
        $userId = $user->getKey();
        $names = [
            User::optionNamePrefixFor($userId).$option,
            'aura-user-option-v2:'.VersionedCache::identity('option.user.owner', $userId).':'.$option,
        ];

        if ($user->getKeyType() === 'int') {
            $names[] = 'user.'.$userId.'.'.$option;
        }

        return array_values(array_unique($names));
    }

    private function userPrototype(): ?User
    {
        $guardName = Auth::getDefaultDriver();
        $providerName = is_string($guardName) ? config("auth.guards.{$guardName}.provider") : null;
        $userClass = is_string($providerName) ? config("auth.providers.{$providerName}.model") : null;

        if (! is_string($userClass) || ! is_a($userClass, User::class, true)) {
            $userClass = config('aura.resources.user', User::class);
        }

        if (! is_string($userClass) || ! is_a($userClass, User::class, true)) {
            return null;
        }

        return new $userClass;
    }

    /** @param array<int, string> $optionNames */
    private function verifiedUserOption(Option $record, User $user, array $optionNames): Option
    {
        $expectedOwner = VersionedCache::identity('option.user.owner', $user->getKey());
        $owner = $record->getRawOriginal('owner_identity');

        if ($owner !== null) {
            if (! is_string($owner) || ! hash_equals($expectedOwner, $owner)) {
                throw OptionOwnerIdentityException::forOption($record->getKey());
            }

            return $record;
        }

        if (! in_array($record->getRawOriginal('name'), array_slice($optionNames, 1), true)) {
            throw OptionOwnerIdentityException::forOption($record->getKey());
        }

        $record->setAttribute('owner_identity', $expectedOwner);

        return $record;
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

        $connection->transaction(function () use ($connection, $storageKey, $value, $preserveFloat): void {
            $attributes = ['name' => $storageKey];

            if (config('aura.teams')) {
                $attributes['team_id'] = Option::EVERYONE_TEAM_ID;
            }

            $record = $this->optionQuery()
                ->withTrashed()
                ->where($attributes)
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                if (config('aura.teams')) {
                    Option::createForTeamForSystem(
                        Option::EVERYONE_TEAM_ID,
                        [...$attributes, 'user_id' => null, 'value' => $value],
                        $connection,
                    );

                    return;
                }

                $record = $this->optionQuery()->newModelInstance($attributes);

                if ($preserveFloat) {
                    $this->persistEncodedFloat($record, $value);
                } else {
                    $record->setAttribute('value', $value);
                }

                $this->requireSuccessfulOptionMutation($record->save());

                return;
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
                $this->requireSuccessfulOptionMutation($record->restore());
            } else {
                $this->requireSuccessfulOptionMutation($record->save());
            }
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

        $connection->transaction(function () use ($connection, $context, $storageKey, $value, $preserveFloat): void {
            $attributes = [
                'name' => 'team.'.$context->team->getKey().'.'.$storageKey,
                'team_id' => $context->team->getKey(),
            ];
            $record = $this->optionQuery()
                ->withTrashed()
                ->where($attributes)
                ->lockForUpdate()
                ->first();

            if ($record === null) {
                Option::createForTeamForSystem(
                    $context->team->getKey(),
                    [...$attributes, 'user_id' => null, 'value' => $value],
                    $connection,
                );

                return;
            }

            if ($preserveFloat) {
                $this->persistEncodedFloat($record, $value);
            } else {
                $record->setAttribute('value', $value);
            }

            if ($record->trashed()) {
                $this->requireSuccessfulOptionMutation($record->restore());
            } else {
                $this->requireSuccessfulOptionMutation($record->save());
            }
        });

        Team::clearOptionCacheForTeam($context->team->getKey(), $connection);
    }

    private function writeUserEntry(
        PreferenceContext $context,
        string $storageKey,
        mixed $value,
        bool $preserveFloat,
    ): void {
        $connection = $this->optionConnection();
        $teamId = config('aura.teams') ? $context->team?->getKey() : null;

        $connection->transaction(function () use ($connection, $context, $storageKey, $value, $preserveFloat, $teamId): void {
            $optionNames = $this->userOptionNames($context->user, $storageKey);
            $canonicalName = $optionNames[0];
            $query = $this->optionQuery();

            if (config('aura.teams')) {
                $query->where('team_id', $teamId);
            }

            $record = null;
            $createdForTeam = false;

            foreach ($optionNames as $optionName) {
                $record = (clone $query)
                    ->withTrashed()
                    ->where('name', $optionName)
                    ->lockForUpdate()
                    ->first();

                if ($record !== null) {
                    break;
                }
            }

            if ($record !== null) {
                $record = $this->verifiedUserOption($record, $context->user, $optionNames);
                $isCreatingOrRenaming = $record->getRawOriginal('name') !== $canonicalName;
                $record->setAttribute('name', $canonicalName);
            } else {
                $isCreatingOrRenaming = true;
                if (config('aura.teams')) {
                    $record = Option::createForTeamForSystem(
                        $teamId,
                        ['name' => $canonicalName, 'user_id' => null, 'value' => $value],
                        $connection,
                    );
                    $createdForTeam = true;
                } else {
                    $record = (clone $query)->newModelInstance(['name' => $canonicalName]);
                }
            }

            $record->setAttribute(
                'owner_identity',
                VersionedCache::identity('option.user.owner', $context->user->getKey()),
            );
            if ($preserveFloat) {
                $this->persistEncodedFloat($record, $value);
            } else {
                $record->setAttribute('value', $value);
            }

            try {
                if ($createdForTeam) {
                    $updated = $connection->table($record->getTable())
                        ->useWritePdo()
                        ->where($record->getKeyName(), $record->getKey())
                        ->where('team_id', $teamId)
                        ->update(['owner_identity' => $record->getAttribute('owner_identity')]);

                    if ($updated !== 1) {
                        throw new RuntimeException('Preference owner identity persistence failed.');
                    }

                    $record->syncOriginalAttribute('owner_identity');
                } elseif ($record->trashed()) {
                    $this->requireSuccessfulOptionMutation($record->restore());
                } else {
                    $this->requireSuccessfulOptionMutation($record->save());
                }
            } catch (UniqueConstraintViolationException $exception) {
                if (! $isCreatingOrRenaming) {
                    throw $exception;
                }

                $record = (clone $query)
                    ->withTrashed()
                    ->where('name', $canonicalName)
                    ->lockForUpdate()
                    ->first();

                if (! $record instanceof Option) {
                    throw $exception;
                }

                $record = $this->verifiedUserOption($record, $context->user, $optionNames);
                $record->setAttribute(
                    'owner_identity',
                    VersionedCache::identity('option.user.owner', $context->user->getKey()),
                );
                if ($preserveFloat) {
                    $this->persistEncodedFloat($record, $value);
                } else {
                    $record->setAttribute('value', $value);
                }

                if ($record->trashed()) {
                    $this->requireSuccessfulOptionMutation($record->restore());
                } else {
                    $this->requireSuccessfulOptionMutation($record->save());
                }
            }

            (clone $query)
                ->withTrashed()
                ->whereIn('name', array_slice($optionNames, 1))
                ->where($record->getKeyName(), '!=', $record->getKey())
                ->lockForUpdate()
                ->get()
                ->each(function (Option $alias) use ($context, $optionNames): void {
                    $this->requireSuccessfulOptionMutation(
                        $this->verifiedUserOption($alias, $context->user, $optionNames)->forceDelete(),
                    );
                });
        });

        User::clearOptionCacheForTeam(
            $context->user->getKey(),
            $teamId ?? 'global',
            $connection,
        );
    }
}
