<?php

namespace Aura\Base;

/**
 * Coordinates every process-static cache derived from Aura field definitions.
 *
 * InputFields is used by resources, field types, and standalone consumers. A
 * resource-only reset therefore cannot invalidate the complete definition
 * graph. Consumers register themselves when they resolve fields so one flush
 * can clear every cache that was populated in the current process.
 */
final class FieldCacheManager
{
    /**
     * @var array<class-string, true>
     */
    private static array $consumers = [];

    private static bool $flushing = false;

    private static int $generation = 0;

    /**
     * Clear all field-definition state populated in this PHP process.
     *
     * Provider output can be retained when only version snapshots need to be
     * re-read. The version-keyed provider cache remains safe in that case.
     */
    public static function flush(bool $flushProviderResults = true): void
    {
        if (self::$flushing) {
            return;
        }

        self::$flushing = true;
        self::$generation++;

        $consumers = array_keys(self::$consumers);
        self::$consumers = [];

        try {
            foreach ($consumers as $consumer) {
                if (method_exists($consumer, 'flushOwnFieldCache')) {
                    $consumer::flushOwnFieldCache();
                }
            }

            ConditionalLogic::clearConditionsCache();

            if ($flushProviderResults && app()->bound(FieldProviderRegistry::class)) {
                app(FieldProviderRegistry::class)->flushResolved();
            }
        } finally {
            self::$flushing = false;
        }
    }

    public static function generation(): int
    {
        return self::$generation;
    }

    /**
     * @param  class-string  $consumer
     */
    public static function registerConsumer(string $consumer): void
    {
        self::$consumers[$consumer] = true;
    }
}
