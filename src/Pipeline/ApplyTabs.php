<?php

namespace Aura\Base\Pipeline;

use Closure;

class ApplyTabs implements Pipe
{
    public function handle($fields, Closure $next)
    {
        $tabsAdded = 0;
        $added = false;
        $currentParent = null;
        $addedTabsToPanel = false;

        foreach ($fields as $key => $field) {
            if ($field['type'] === 'Aura\Base\Fields\Panel') {
                $currentParent = $field;
                $addedTabsToPanel = false;
            }

            // Add it to first Tabs
            if ($field['type'] === 'Aura\Base\Fields\Tab' && ! $added) {
                $fields->splice($key + $tabsAdded, 0, [
                    [
                        'label' => 'Tabs',
                        'name' => 'Tabs',
                        'global' => (bool) optional($field)['global'],
                        'type' => 'Aura\\Base\\Fields\\Tabs',
                        'slug' => 'tabs',
                        'style' => [],
                    ],
                ]);
                $added = true;
                $tabsAdded++;
                $addedTabsToPanel = true;
            }

            // Add it to first Tabs in Panels
            if ($currentParent && ! optional($field)['global']) {
                if ($field['type'] === 'Aura\Base\Fields\Tab' && ! $addedTabsToPanel) {
                    $fields->splice($key + $tabsAdded, 0, [
                        [
                            'label' => 'Tabs',
                            'name' => 'Tabs',
                            'global' => (bool) optional($field)['global'],
                            'type' => 'Aura\\Base\\Fields\\Tabs',
                            'slug' => 'tabs',
                            'style' => [],
                        ],
                    ]);
                    $addedTabsToPanel = true;
                    $tabsAdded++;
                }
            }
        }

        // to the next pipe
        return $next($fields);
    }
}
