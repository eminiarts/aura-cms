<?php

namespace Aura\Base\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

final class EmbeddedResourceIncarnationStore
{
    public const TABLE = 'aura_embedded_resource_incarnations';

    /** @var array<string, string> */
    private array $tokens = [];

    public function forget(Model $resource): void
    {
        $key = $resource->getKey();

        if (is_int($key) || is_string($key)) {
            unset($this->tokens[$this->identity($resource::class, $key)]);
        }
    }

    /**
     * @param  iterable<int, Model>  $resources
     */
    public function prime(iterable $resources): void
    {
        $identities = [];

        foreach ($resources as $resource) {
            $key = $resource->getKey();

            if (! is_int($key) && ! is_string($key)) {
                continue;
            }

            $identity = $this->identity($resource::class, $key);

            if (isset($this->tokens[$identity])) {
                continue;
            }

            $identities[$identity] = [
                'resource_type' => $resource::class,
                'resource_key_hash' => $this->keyHash($key),
            ];
        }

        if ($identities === []) {
            return;
        }

        $this->loadTokens($identities);

        $missing = array_diff_key($identities, $this->tokens);

        if ($missing === []) {
            return;
        }

        $timestamp = now();
        $rows = [];

        foreach ($missing as $identity => $values) {
            $rows[] = [
                ...$values,
                'incarnation' => (string) Str::uuid(),
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        DB::table(self::TABLE)->insertOrIgnore($rows);
        $this->loadTokens($missing);
    }

    public function rotate(Model $resource): void
    {
        $key = $resource->getKey();

        if (! is_int($key) && ! is_string($key)) {
            return;
        }

        try {
            DB::table(self::TABLE)
                ->where('resource_type', $resource::class)
                ->where('resource_key_hash', $this->keyHash($key))
                ->update([
                    'incarnation' => (string) Str::uuid(),
                    'updated_at' => now(),
                ]);
        } catch (QueryException) {
            // Existing applications may delete resources before publishing the
            // new migration. Legacy components must keep working during that
            // upgrade window; secure contexts fail closed when they are issued.
        }

        $this->forget($resource);
    }

    public function token(Model $resource): string
    {
        $key = $resource->getKey();

        if (! is_int($key) && ! is_string($key)) {
            throw new RuntimeException('Persisted embedded resources require a scalar key.');
        }

        $identity = $this->identity($resource::class, $key);
        $this->prime([$resource]);

        return $this->tokens[$identity]
            ?? throw new RuntimeException('Unable to persist the embedded resource incarnation.');
    }

    private function identity(string $resourceClass, int|string $resourceKey): string
    {
        return $resourceClass.'|'.$this->keyHash($resourceKey);
    }

    private function keyHash(int|string $resourceKey): string
    {
        try {
            $encodedKey = json_encode(
                [get_debug_type($resourceKey), $resourceKey],
                JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION,
            );
        } catch (JsonException $exception) {
            throw new RuntimeException('Unable to encode the embedded resource key.', previous: $exception);
        }

        return hash('sha256', $encodedKey);
    }

    /**
     * @param  array<string, array{resource_type: class-string<Model>, resource_key_hash: string}>  $identities
     */
    private function loadTokens(array $identities): void
    {
        $query = DB::table(self::TABLE)->where(function ($query) use ($identities): void {
            foreach (collect($identities)->groupBy('resource_type') as $resourceType => $group) {
                $query->orWhere(function ($query) use ($resourceType, $group): void {
                    $query->where('resource_type', $resourceType)
                        ->whereIn('resource_key_hash', $group->pluck('resource_key_hash'));
                });
            }
        });

        foreach ($query->get(['resource_type', 'resource_key_hash', 'incarnation']) as $row) {
            $identity = $row->resource_type.'|'.$row->resource_key_hash;
            $this->tokens[$identity] = $row->incarnation;
        }
    }
}
