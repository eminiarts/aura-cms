<?php

namespace Aura\Base\Services;

use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

final class EmbeddedResourceIncarnationStore
{
    public const TABLE = 'aura_embedded_resource_incarnations';

    /** @var array<string, array{incarnation: string, version: int}> */
    private array $incarnations = [];

    public function __construct(
        private readonly EmbeddedResourceIncarnationGuard $guard,
    ) {}

    public function flush(): void
    {
        $this->incarnations = [];
    }

    public function forget(Model $resource): void
    {
        $key = $resource->getKey();

        if (is_int($key) || is_string($key)) {
            unset($this->incarnations[$this->identity($resource, $key)]);
        }
    }

    /**
     * @param  iterable<int, Model>  $resources
     */
    public function prime(iterable $resources): void
    {
        /** @var array<string, array{connection: Connection, identities: array<string, array<string, string>>}> $groups */
        $groups = [];

        foreach ($resources as $resource) {
            $key = $resource->getKey();

            if (! is_int($key) && ! is_string($key)) {
                continue;
            }

            $this->guard->assertInstalled($resource);
            $identity = $this->identity($resource, $key);

            if (isset($this->incarnations[$identity])) {
                continue;
            }

            $connectionIdentity = $this->connectionIdentity($resource);
            $groups[$connectionIdentity]['connection'] = $resource->getConnection();
            $groups[$connectionIdentity]['identities'][$identity] = $this->storedIdentity($resource, $key);
        }

        foreach ($groups as $group) {
            $this->primeConnection($group['connection'], $group['identities']);
        }
    }

    public function rotate(Model $resource): void
    {
        $key = $resource->getKey();

        if (! is_int($key) && ! is_string($key)) {
            return;
        }

        $this->guard->assertInstalled($resource);
        $storedIdentity = $this->storedIdentity($resource, $key);
        $resource->getConnection()
            ->table(self::TABLE)
            ->where('resource_type', $storedIdentity['resource_type'])
            ->where('resource_key_hash', $storedIdentity['resource_key_hash'])
            ->increment('version', 1, [
                'incarnation' => (string) Str::uuid(),
                'updated_at' => now(),
            ]);

        $this->forget($resource);
    }

    public function token(Model $resource): string
    {
        return $this->incarnation($resource)['incarnation'];
    }

    public function version(Model $resource): int
    {
        return $this->incarnation($resource)['version'];
    }

    private function connectionIdentity(Connection|Model $connection): string
    {
        if ($connection instanceof Model) {
            $connection = $connection->getConnection();
        }

        return implode('|', [
            $connection->getName() ?? '',
            $connection->getDriverName(),
            $connection->getDatabaseName(),
        ]);
    }

    private function identity(Model $resource, int|string $resourceKey): string
    {
        return $this->connectionIdentity($resource)
            .'|'.$resource::class
            .'|'.$this->keyHash($resource, $resourceKey);
    }

    /**
     * @return array{incarnation: string, version: int}
     */
    private function incarnation(Model $resource): array
    {
        $key = $resource->getKey();

        if (! is_int($key) && ! is_string($key)) {
            throw new RuntimeException('Persisted embedded resources require a scalar key.');
        }

        $identity = $this->identity($resource, $key);
        $this->prime([$resource]);

        return $this->incarnations[$identity]
            ?? throw new RuntimeException('Unable to persist the embedded resource incarnation.');
    }

    private function keyHash(Model $resource, int|string $resourceKey): string
    {
        try {
            $encodedKey = json_encode(
                [$this->resourceKeyType($resource), $this->resourceKey($resourceKey)],
                JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode the embedded resource key.', previous: $exception);
        }

        return hash('sha256', $encodedKey);
    }

    /**
     * @param  array<string, array<string, string>>  $identities
     */
    private function loadIncarnations(Connection $connection, array $identities): void
    {
        if ($identities === []) {
            return;
        }

        $query = $connection->table(self::TABLE)->where(function ($query) use ($identities): void {
            foreach (collect($identities)->groupBy('resource_type') as $resourceType => $group) {
                $query->orWhere(function ($query) use ($resourceType, $group): void {
                    $query->where('resource_type', $resourceType)
                        ->whereIn('resource_key_hash', $group->pluck('resource_key_hash'));
                });
            }
        });

        foreach ($query->get(['resource_type', 'resource_key_hash', 'incarnation', 'version']) as $row) {
            $identity = $this->connectionIdentity($connection)
                .'|'.$row->resource_type
                .'|'.$row->resource_key_hash;
            $this->incarnations[$identity] = [
                'incarnation' => (string) $row->incarnation,
                'version' => (int) $row->version,
            ];
        }
    }

    /**
     * @param  array<string, array<string, string>>  $identities
     */
    private function primeConnection(Connection $connection, array $identities): void
    {
        $this->loadIncarnations($connection, $identities);
        $missing = array_diff_key($identities, $this->incarnations);

        if ($missing === []) {
            return;
        }

        $timestamp = now();
        $rows = [];

        foreach ($missing as $values) {
            $rows[] = [
                ...$values,
                'incarnation' => (string) Str::uuid(),
                'version' => 1,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        $connection->table(self::TABLE)->insertOrIgnore($rows);
        $this->loadIncarnations($connection, $missing);
    }

    private function resourceKey(int|string $resourceKey): string
    {
        $resourceKey = (string) $resourceKey;

        if (Str::length($resourceKey) > 191) {
            throw new RuntimeException('Embedded resource keys may not exceed 191 characters.');
        }

        return $resourceKey;
    }

    private function resourceKeyType(Model $resource): string
    {
        return $resource->getKeyType() === 'int' ? 'integer' : 'string';
    }

    /**
     * @return array{resource_type: string, resource_key_hash: string, resource_key_type: string, resource_key: string}
     */
    private function storedIdentity(Model $resource, int|string $resourceKey): array
    {
        return [
            'resource_type' => $resource::class,
            'resource_key_hash' => $this->keyHash($resource, $resourceKey),
            'resource_key_type' => $this->resourceKeyType($resource),
            'resource_key' => $this->resourceKey($resourceKey),
        ];
    }
}
