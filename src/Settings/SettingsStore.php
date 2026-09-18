<?php

namespace Aura\Base\Settings;

use Aura\Base\Resources\Option;
use Aura\Base\Support\TeamExecutionContext;
use Illuminate\Contracts\Encryption\DecryptException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use LogicException;
use RuntimeException;

final class SettingsStore
{
    private const ENCRYPTED_PREFIX = 'encrypted:';

    private const SECRET_CONTEXTS_KEY = '_secret_contexts';

    public function __construct(private readonly SettingsRegistry $registry) {}

    /**
     * Non-secret settings of every team, for lookups that run without a team
     * context (e.g. resolving which team owns a public hostname).
     *
     * @return list<array{team_id: ?int, values: array<string, mixed>}>
     */
    public function all(): array
    {
        $hidden = [...$this->registry->secretFields(), self::SECRET_CONTEXTS_KEY];

        return Option::withoutGlobalScopes()
            ->where('name', 'like', config('aura.teams') ? 'team.%.settings' : 'settings')
            ->get()
            ->map(fn (Option $option): array => [
                'team_id' => config('aura.teams') ? (int) $option->getAttribute('team_id') : null,
                'values' => Arr::except($this->values($option), $hidden),
            ])
            ->all();
    }

    /** @param  array<string, mixed>  $defaults */
    public function findOrCreate(array $defaults = []): Option
    {
        return Option::firstOrCreate(
            ['name' => $this->optionName()],
            ['value' => $defaults],
        );
    }

    /** Pass $teamId to read a team's settings without an authenticated user (public requests, jobs). */
    public function get(string $key, mixed $default = null, ?int $teamId = null): mixed
    {
        $values = $this->values(teamId: $teamId);

        if (! array_key_exists($key, $values)) {
            return $default;
        }

        if (in_array($key, $this->registry->secretFields(), true)) {
            return $this->secret($key, $values) ?? $default;
        }

        return $values[$key];
    }

    public function put(string $key, mixed $value): Option
    {
        return $this->store($this->findOrCreate(), [$key => $value], $this->registry->secretFields());
    }

    /** @param  array<string, mixed>|null  $values */
    public function secret(string $key, ?array $values = null): ?string
    {
        $values ??= $this->values();
        $value = $values[$key] ?? null;

        if (! $this->secretMatchesContext($key, $values)) {
            return null;
        }

        if (! is_string($value) || $value === '') {
            return null;
        }

        if (str_starts_with($value, self::ENCRYPTED_PREFIX)) {
            try {
                return Crypt::decryptString(substr($value, strlen(self::ENCRYPTED_PREFIX)));
            } catch (DecryptException $exception) {
                throw new RuntimeException("Stored secret setting [{$key}] could not be decrypted.", previous: $exception);
            }
        }

        try {
            return Crypt::decryptString($value);
        } catch (DecryptException) {
            return $value;
        }
    }

    /**
     * Merges $values into the stored settings. A blank secret keeps the stored one.
     *
     * @param  array<string, mixed>  $values
     * @param  list<string>  $secretFields
     */
    public function store(Option $option, array $values, array $secretFields = []): Option
    {
        $option->refresh();
        $storedValue = $option->getAttribute('value');
        $stored = is_array($storedValue) ? $storedValue : [];
        $secretContexts = is_array($stored[self::SECRET_CONTEXTS_KEY] ?? null)
            ? $stored[self::SECRET_CONTEXTS_KEY]
            : [];

        foreach ($secretFields as $slug) {
            $secret = $values[$slug] ?? null;

            if (! is_string($secret) || $secret === '') {
                unset($values[$slug]);

                continue;
            }

            $values[$slug] = self::ENCRYPTED_PREFIX.Crypt::encryptString($secret);

            if ($context = $this->registry->secretContexts()[$slug] ?? null) {
                $secretContexts[$slug] = $values[$context] ?? $stored[$context] ?? null;
            }
        }

        if ($secretContexts !== []) {
            $values[self::SECRET_CONTEXTS_KEY] = $secretContexts;
        }

        // Each settings page submits only its own fields.
        $option->update(['value' => array_replace($stored, $values)]);
        $this->forgetCache();

        return $option;
    }

    /** @return array<string, mixed> */
    public function values(?Option $option = null, ?int $teamId = null): array
    {
        $teamId ??= $this->currentTeamId();

        if (! $option && config('aura.teams') && $teamId === null) {
            return [];
        }

        // The option name carries the team id, so the lookup must not depend on TeamScope.
        $option ??= Option::withoutGlobalScopes()->where('name', $this->optionName($teamId))->first();

        if (! $option) {
            return [];
        }

        $value = $option->getAttribute('value');

        if (is_array($value)) {
            return $value;
        }

        if (! is_string($value)) {
            return [];
        }

        return json_decode($value, true) ?: [];
    }

    private function currentTeamId(): mixed
    {
        if (TeamExecutionContext::active()) {
            return TeamExecutionContext::currentTeamId();
        }

        $user = auth()->user();

        if (! $user instanceof Model) {
            return null;
        }

        $team = $user->getRelationValue('currentTeam');

        return $team instanceof Model
            ? $team->getKey()
            : $user->getAttribute('current_team_id');
    }

    private function forgetCache(): void
    {
        Cache::forget('aura.settings');

        $teamId = $this->currentTeamId();

        if (config('aura.teams') && $teamId !== null) {
            Cache::forget($teamId.'.aura.settings');
            Cache::forget('team.'.$teamId.'.settings');
        }
    }

    private function optionName(mixed $teamId = null): string
    {
        if (! config('aura.teams')) {
            return 'settings';
        }

        $teamId ??= $this->currentTeamId();

        if ($teamId === null) {
            throw new LogicException('Team settings require an authenticated current team.');
        }

        return 'team.'.$teamId.'.settings';
    }

    /** @param  array<string, mixed>  $values */
    private function secretMatchesContext(string $key, array $values): bool
    {
        $context = $this->registry->secretContexts()[$key] ?? null;

        if ($context === null) {
            return true;
        }

        $storedContexts = $values[self::SECRET_CONTEXTS_KEY] ?? [];

        return is_array($storedContexts)
            && isset($storedContexts[$key], $values[$context])
            && $storedContexts[$key] === $values[$context];
    }
}
