<?php

// app/Navigation/Navigation.php

namespace Aura\Base\Navigation;

use Aura\Base\Services\VersionedCache;

class Navigation
{
    public static function add(array $items, ?callable $authCallback = null): void
    {
        if ($authCallback && ! $authCallback()) {
            return;
        }

        $fingerprint = VersionedCache::isSafe($items)
            ? 'navigation.items.'.hash('sha256', serialize($items))
            : null;

        app('hook_manager')->addHook(
            'navigation',
            function ($navigation) use ($items) {
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
}
