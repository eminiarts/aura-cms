<?php
// app/Navigation/Navigation.php

namespace Aura\Base\Navigation;

class Navigation
{
    public static function add(array $items, callable $authCallback = null): void
    {
        if ($authCallback && !$authCallback()) {
            // dd($authCallback());
            return;
        }

        app('hook_manager')->addHook('navigation', function ($navigation) use ($items) {
            foreach ($items as $item) {
                $navigation->push($item);
            }
            return $navigation;
        });
    }

    public static function clear(): void
    {
        app('hook_manager')->addHook('navigation', function ($navigation) {
            return collect([]);
        });
    }
}
