<?php

// app/Navigation/Navigation.php

namespace Aura\Base\Navigation;

use Aura\Base\Services\VersionedCache;
use Illuminate\Database\Connection;
use InvalidArgumentException;
use Throwable;

class Navigation
{
    public static function add(array $items, ?callable $authCallback = null): void
    {
        $fingerprint = $authCallback === null && VersionedCache::isSafe($items)
            ? 'navigation.items.'.hash('sha256', serialize($items))
            : null;

        app('hook_manager')->addHook(
            'navigation',
            function ($navigation) use ($authCallback, $items) {
                if ($authCallback !== null) {
                    try {
                        if (! auth()->check() || ! $authCallback()) {
                            return $navigation;
                        }
                    } catch (Throwable) {
                        return $navigation;
                    }
                }

                foreach ($items as $item) {
                    $navigation->push($item);
                }

                return $navigation;
            },
            $fingerprint,
        );
    }

    public static function clear(): void
    {
        app('hook_manager')->addHook(
            'navigation',
            fn ($navigation) => collect([]),
            'navigation.clear.v1',
        );
    }

    public static function clearCache(?Connection $connection = null): void
    {
        VersionedCache::bump('navigation', $connection);
    }

    /**
     * Build a cache-safe Gate contract for a non-resource navigation item.
     *
     * @return array{ability: string, arguments: mixed}
     */
    public static function policy(string $ability, mixed $arguments = []): array
    {
        if (trim($ability) === '' || ! VersionedCache::isSafe($arguments)) {
            throw new InvalidArgumentException('Navigation policies require a non-empty ability and scalar arguments.');
        }

        return [
            'ability' => $ability,
            'arguments' => $arguments,
        ];
    }
}
