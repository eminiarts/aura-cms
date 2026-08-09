<?php

namespace Aura\Base\GlobalSearch;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;
use JsonException;
use Throwable;

final class GlobalSearchWorkerContext
{
    private const VERSION = 1;

    /**
     * @return array{
     *     version: int,
     *     guard: string,
     *     user_id: int|string,
     *     team_id: int|string|null,
     *     connection: string,
     *     connection_fingerprint: string,
     *     signature: string
     * }|null
     */
    public function create(Authenticatable $user, string $guard): ?array
    {
        if (! $user instanceof Model) {
            return null;
        }

        $userIdentifier = $user->getAuthIdentifier();
        $teamIdentifier = data_get($user, 'current_team_id');
        $connection = $user->getConnectionName() ?? DB::getDefaultConnection();

        if (! $this->validIdentifier($userIdentifier)
            || ! $this->validNullableIdentifier($teamIdentifier)
            || ! $this->validName($guard)
            || ! $this->validName($connection)
            || ! in_array($connection, $this->allowedConnections(), true)) {
            return null;
        }

        $connectionFingerprint = $this->connectionFingerprint($connection);

        if ($connectionFingerprint === null) {
            return null;
        }

        $context = [
            'version' => self::VERSION,
            'guard' => $guard,
            'user_id' => $userIdentifier,
            'team_id' => $teamIdentifier,
            'connection' => $connection,
            'connection_fingerprint' => $connectionFingerprint,
        ];
        $signature = $this->signature($context);

        if ($signature === null) {
            return null;
        }

        return [...$context, 'signature' => $signature];
    }

    /**
     * @return array{
     *     guard: string,
     *     user_id: int|string,
     *     team_id: int|string|null,
     *     connection: string
     * }|null
     */
    public function verify(mixed $context): ?array
    {
        if (! is_array($context)
            || array_keys($context) !== [
                'version',
                'guard',
                'user_id',
                'team_id',
                'connection',
                'connection_fingerprint',
                'signature',
            ]
            || ($context['version'] ?? null) !== self::VERSION
            || ! is_string($context['guard'] ?? null)
            || ! $this->validName($context['guard'])
            || ! $this->validIdentifier($context['user_id'] ?? null)
            || ! $this->validNullableIdentifier($context['team_id'] ?? null)
            || ! is_string($context['connection'] ?? null)
            || ! $this->validName($context['connection'])
            || ! is_string($context['connection_fingerprint'] ?? null)
            || ! preg_match('/\A[a-f0-9]{64}\z/', $context['connection_fingerprint'])
            || ! is_string($context['signature'] ?? null)
            || ! preg_match('/\A[a-f0-9]{64}\z/', $context['signature'])
            || ! in_array($context['connection'], $this->allowedConnections(), true)) {
            return null;
        }

        $unsignedContext = $context;
        unset($unsignedContext['signature']);
        $expectedSignature = $this->signature($unsignedContext);
        $expectedFingerprint = $this->connectionFingerprint($context['connection']);

        if ($expectedSignature === null
            || $expectedFingerprint === null
            || ! hash_equals($expectedSignature, $context['signature'])
            || ! hash_equals($expectedFingerprint, $context['connection_fingerprint'])) {
            return null;
        }

        return [
            'guard' => $context['guard'],
            'user_id' => $context['user_id'],
            'team_id' => $context['team_id'],
            'connection' => $context['connection'],
        ];
    }

    /** @return array<int, string> */
    private function allowedConnections(): array
    {
        $configured = config('aura.global_search.worker_connections', ['@default']);

        if (! is_array($configured)
            || ! array_is_list($configured)
            || $configured === []
            || count($configured) > 32) {
            return [];
        }

        $connections = [];

        foreach ($configured as $connection) {
            if ($connection === '@default') {
                $connection = DB::getDefaultConnection();
            }

            if (! is_string($connection)
                || ! $this->validName($connection)
                || ! is_array(config("database.connections.{$connection}"))) {
                return [];
            }

            $connections[] = $connection;
        }

        return array_values(array_unique($connections));
    }

    private function connectionFingerprint(string $connection): ?string
    {
        $configuration = config("database.connections.{$connection}");
        $key = $this->signingKey();

        if (! is_array($configuration) || $key === null) {
            return null;
        }

        try {
            $encodedConfiguration = json_encode(
                $this->normalize($configuration),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
            );
        } catch (Throwable) {
            return null;
        }

        return hash_hmac(
            'sha256',
            'aura-global-search-connection-v1:'.$encodedConfiguration,
            $key,
        );
    }

    private function normalize(mixed $value): mixed
    {
        if (is_array($value)) {
            $entries = [];

            foreach ($value as $key => $entry) {
                $entries[] = [
                    'key' => (is_int($key) ? 'integer:' : 'string:').$key,
                    'value' => $this->normalize($entry),
                ];
            }

            usort(
                $entries,
                fn (array $left, array $right): int => $left['key'] <=> $right['key'],
            );

            return ['type' => 'array', 'value' => $entries];
        }

        if (is_null($value) || is_bool($value) || is_int($value) || is_float($value) || is_string($value)) {
            return ['type' => get_debug_type($value), 'value' => $value];
        }

        throw new JsonException('Unsupported worker connection configuration value.');
    }

    /** @param array<string, mixed> $context */
    private function signature(array $context): ?string
    {
        $key = $this->signingKey();

        if ($key === null) {
            return null;
        }

        try {
            $encodedContext = json_encode(
                $this->normalize($context),
                JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES,
            );
        } catch (JsonException) {
            return null;
        }

        return hash_hmac('sha256', 'aura-global-search-context-v1:'.$encodedContext, $key);
    }

    private function signingKey(): ?string
    {
        $key = config('app.key');

        return is_string($key) && $key !== '' && strlen($key) <= 4_096
            ? $key
            : null;
    }

    private function validIdentifier(mixed $identifier): bool
    {
        return is_int($identifier)
            || (is_string($identifier) && $identifier !== '' && strlen($identifier) <= 255);
    }

    private function validName(mixed $name): bool
    {
        return is_string($name)
            && preg_match('/\A[A-Za-z0-9_.-]{1,64}\z/', $name) === 1;
    }

    private function validNullableIdentifier(mixed $identifier): bool
    {
        return $identifier === null || $this->validIdentifier($identifier);
    }
}
