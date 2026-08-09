<?php

namespace Aura\Base\Schema;

use Closure;
use RuntimeException;
use Throwable;

final class SchemaMigrationLock
{
    /** @var array<string, array{depth: int, handle: resource}> */
    private static array $heldLocks = [];

    public static function run(string $key, Closure $callback): mixed
    {
        $lockKey = hash('sha256', $key);

        if (isset(self::$heldLocks[$lockKey])) {
            self::$heldLocks[$lockKey]['depth']++;

            try {
                return $callback();
            } finally {
                self::$heldLocks[$lockKey]['depth']--;
            }
        }

        $directory = storage_path('framework/cache/aura-schema-locks');

        if (! is_dir($directory) && ! mkdir($directory, 0755, true) && ! is_dir($directory)) {
            throw new RuntimeException("Unable to create Aura schema lock directory [{$directory}].");
        }

        $path = $directory.'/'.$lockKey.'.lock';
        $handle = fopen($path, 'c+');

        if ($handle === false) {
            throw new RuntimeException("Unable to open Aura schema lock [{$path}].");
        }

        try {
            if (! flock($handle, LOCK_EX)) {
                throw new RuntimeException("Unable to acquire Aura schema lock [{$path}].");
            }

            self::$heldLocks[$lockKey] = ['depth' => 1, 'handle' => $handle];

            return $callback();
        } catch (Throwable $exception) {
            throw $exception;
        } finally {
            unset(self::$heldLocks[$lockKey]);
            flock($handle, LOCK_UN);
            fclose($handle);
        }
    }
}
