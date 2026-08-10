<?php

namespace Aura\Base\Widgets;

use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\User;
use DateTimeInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use InvalidArgumentException;
use JsonException;

trait InteractsWithWidgetCache
{
    final public function mountInteractsWithWidgetCache(): void
    {
        $this->refreshWidgetCacheState(resetLoadedOnMiss: true);
    }

    final protected function normalizedWidgetCacheValue(mixed $value): mixed
    {
        if ($value instanceof DateTimeInterface) {
            return Carbon::parse($value)->utc()->format('Y-m-d\TH:i:s.u\Z');
        }

        if (is_array($value)) {
            if (! array_is_list($value)) {
                ksort($value, SORT_STRING);
            }

            foreach ($value as $key => $item) {
                $value[$key] = $this->normalizedWidgetCacheValue($item);
            }

            return $value;
        }

        if ($value === null || is_scalar($value)) {
            if (is_float($value) && ! is_finite($value)) {
                throw new InvalidArgumentException('Widget cache configuration cannot contain non-finite floats.');
            }

            return $value;
        }

        throw new InvalidArgumentException('Widget cache configuration must contain only scalar, date, array, or null values.');
    }

    final protected function refreshWidgetCacheState(bool $resetLoadedOnMiss = false): void
    {
        $this->isCached = cache()->has($this->getCacheKeyProperty());

        if ($this->isCached) {
            $this->loaded = true;
        } elseif ($resetLoadedOnMiss) {
            $this->loaded = false;
        }
    }

    /**
     * @return array<string, int|string|null>
     */
    private function declaredWidgetCacheContext(): array
    {
        $dimensions = $this->widgetCacheContextDimensions();

        if (! is_array($dimensions) || ! array_is_list($dimensions)) {
            throw new InvalidArgumentException('Widget cache context must be a list of declared dimensions.');
        }

        $allowed = ['resource', 'team', 'user'];
        $dimensions = array_values(array_unique($dimensions));

        foreach ($dimensions as $dimension) {
            if (! is_string($dimension) || ! in_array($dimension, $allowed, true)) {
                throw new InvalidArgumentException('Widget cache context supports only resource, team, and user dimensions.');
            }
        }

        sort($dimensions, SORT_STRING);

        /** @var User|null $user */
        $user = Auth::user();
        $context = [];

        foreach ($dimensions as $dimension) {
            $context[$dimension] = match ($dimension) {
                'resource' => $this->widgetCacheResourceIdentity(),
                'team' => $this->widgetCacheTeamIdentity($user),
                'user' => $this->widgetCacheUserIdentity($user),
            };
        }

        return $context;
    }

    private function normalizedWidgetCacheDate(mixed $value): mixed
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof DateTimeInterface || is_string($value)) {
            return Carbon::parse($value)->utc()->format('Y-m-d\TH:i:s.u\Z');
        }

        throw new InvalidArgumentException('Widget cache dates must be date strings, date objects, or null.');
    }

    /**
     * @param  array<string, int|string|null>  $segments
     */
    private function widgetCacheDimensionIdentity(array $segments): string
    {
        try {
            return hash('sha256', json_encode(
                $this->normalizedWidgetCacheValue($segments),
                JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
            ));
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Widget cache context identity could not be encoded.', previous: $exception);
        }
    }

    private function widgetCacheFingerprint(): string
    {
        $identifier = $this->widget['id'] ?? $this->widget['slug'] ?? null;

        if (! is_string($identifier) || trim($identifier) === '') {
            $identifier = static::class;
        }

        if (str_contains($identifier, "\0")) {
            throw new InvalidArgumentException('Widget cache identifier is invalid.');
        }

        try {
            return hash('sha256', json_encode(
                $this->normalizedWidgetCacheValue([
                    'component' => static::class,
                    'config' => $this->widget,
                    'context' => $this->declaredWidgetCacheContext(),
                    'end' => $this->normalizedWidgetCacheDate($this->end),
                    'identifier' => $identifier,
                    'start' => $this->normalizedWidgetCacheDate($this->start),
                ]),
                JSON_PRESERVE_ZERO_FRACTION | JSON_THROW_ON_ERROR,
            ));
        } catch (JsonException $exception) {
            throw new InvalidArgumentException('Widget cache identity could not be encoded.', previous: $exception);
        }
    }

    private function widgetCacheResourceIdentity(): ?string
    {
        if (! $this->model) {
            return null;
        }

        $key = $this->model->getKey();

        return $this->widgetCacheDimensionIdentity([
            'class' => get_class($this->model),
            'connection' => User::connectionCacheIdentity($this->model->getConnection()),
            'key' => $key,
            'key_name' => $this->model->getKeyName(),
            'key_type' => $this->model->getKeyType(),
            'morph' => $this->model->getMorphClass(),
            'table' => $this->model->getTable(),
        ]);
    }

    private function widgetCacheTeamId(?User $user): int|string|null
    {
        if (! config('aura.teams') || $user === null) {
            return null;
        }

        $connection = $this->model?->getConnection() ?? $user->getConnection();

        if (TeamScope::hasContextForConnection($connection)) {
            return TeamScope::currentContextTeamId($connection);
        }

        return TeamScope::currentTeamIdForUser($user);
    }

    private function widgetCacheTeamIdentity(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        $connection = $this->model?->getConnection() ?? $user->getConnection();
        $teamId = $this->widgetCacheTeamId($user);

        return $this->widgetCacheDimensionIdentity([
            'connection' => User::connectionCacheIdentity($connection),
            'team_id' => $teamId,
        ]);
    }

    private function widgetCacheUserIdentity(?User $user): ?string
    {
        if ($user === null) {
            return null;
        }

        return $this->widgetCacheDimensionIdentity([
            'auth_identifier' => $user->getAuthIdentifier(),
            'auth_identifier_name' => $user->getAuthIdentifierName(),
            'class' => get_class($user),
            'connection' => User::connectionCacheIdentity($user->getConnection()),
        ]);
    }
}
