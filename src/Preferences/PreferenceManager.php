<?php

namespace Aura\Base\Preferences;

use Aura\Base\Aura;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\User;
use Aura\Base\Services\VersionedCache;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Connection;
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
        $this->authorizeWrite($definition, $scope, $context, $actor);
        $this->deleteEntry($definition, $scope, $context);
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
        $this->authorizeWrite($definition, $scope, $context, $actor);
        $this->writeEntry($definition, $scope, $context, $value);
    }

    private function authorizeWrite(
        PreferenceDefinition $definition,
        PreferenceScope $scope,
        PreferenceContext $context,
        ?User $actor,
    ): void {
        if (! $definition->supports($scope)) {
            throw new InvalidArgumentException("Preference [{$definition->key}] does not support {$scope->value} scope.");
        }

        if ($actor === null) {
            throw new AuthorizationException('An explicit actor is required to write preferences.');
        }

        $authorized = match ($scope) {
            PreferenceScope::User => $context->user !== null
                && (string) $actor->getKey() === (string) $context->user->getKey()
                && $this->canUseTeam($actor, $context),
            PreferenceScope::Team => $context->team !== null
                && ($actor->isAuraGlobalAdmin() || $actor->ownsTeam($context->team)),
            PreferenceScope::Everyone => $actor->isAuraGlobalAdmin(),
        };

        if (! $authorized) {
            throw new AuthorizationException("Not authorized to write {$scope->value} preferences.");
        }
    }

    private function canUseTeam(User $actor, PreferenceContext $context): bool
    {
        if (! config('aura.teams')) {
            return $context->team === null;
        }

        if ($context->team === null) {
            return false;
        }

        return $actor->isAuraGlobalAdmin()
            || $actor->teams()->whereKey($context->team->getKey())->exists();
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

    private function optionConnection(): Connection
    {
        return (new Option)->getConnection();
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

    private function writeEntry(
        PreferenceDefinition $definition,
        PreferenceScope $scope,
        PreferenceContext $context,
        mixed $value,
    ): void {
        $storageKey = $this->storageKey($definition, $context);

        match ($scope) {
            PreferenceScope::User => $context->user->updateOptionForTeam(
                $storageKey,
                $value,
                config('aura.teams') ? $context->team?->getKey() : null,
            ),
            PreferenceScope::Team => $context->team->updateOption($storageKey, $value),
            PreferenceScope::Everyone => $this->writeEveryoneEntry($storageKey, $value),
        };
    }

    private function writeEveryoneEntry(string $storageKey, mixed $value): void
    {
        $connection = $this->optionConnection();

        $connection->transaction(function () use ($storageKey, $value): void {
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

            $record->setAttribute('value', $value);

            if ($record->trashed()) {
                $record->setAttribute($record->getDeletedAtColumn(), null);
            }

            $record->saveQuietly();
        });

        Aura::clearGlobalOptionCache($connection);
    }
}
