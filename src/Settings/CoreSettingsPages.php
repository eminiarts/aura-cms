<?php

namespace Aura\Base\Settings;

use Aura\Base\Livewire\Settings;

final class CoreSettingsPages
{
    /** @return list<SettingsPage> */
    public static function all(): array
    {
        return [
            new SettingsPage(
                slug: 'general',
                title: 'General',
                fields: Settings::generalFields(),
                icon: 'cog',
                order: 0,
            ),
        ];
    }
}
