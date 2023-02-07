<?php

namespace Eminiarts\Aura\Pipeline;

use Closure;

class ApplyLayoutFields implements Pipe
{
    /**
     * @param  array  $layouts
     * @return array
     */
    public function createMainPanel(array $layouts): array
    {
        if (count($layouts) == 0) {
            $layouts[] = [
                'name' => 'Main Panel',
                'slug' => 'main-panel',
                'type' => 'App\Aura\Fields\Panel',
                'field' => app('App\Aura\Fields\Panel'),
                'field_type' => 'layout',
            ];
        }

        return $layouts;
    }

    /**
     * @param  array  $layouts
     * @param  int|string|null  $lastKey
     * @return array
     */
    public function createTabs(array $layouts, int|string|null $lastKey): array
    {
        $layouts[$lastKey]['fields'][] = [
            'name' => 'Tabs',
            'slug' => 'tabs',
            'type' => 'App\Aura\Fields\Tabs',
            'field' => app('App\Aura\Fields\Tabs'),
            'field_type' => 'tabs',
        ];

        return $layouts;
    }

    public function handle($fields, Closure $next)
    {
        $layouts = [];
        $isTab = null;
        $tabsKey = null;

        $fields = $fields->values();

        // dd('hier', $fields);

        foreach ($fields as $key => $field) {
            if ($field['field_type'] == 'layout') {
                $layouts[] = $field;
                $isTab = null;
                $tabsKey = null;

                continue;
            }

            // Create Main Panel, if there is no Main Panel yet
            $layouts = $this->createMainPanel($layouts);

            $lastKey = array_key_last($layouts);

            if (! is_null($isTab) && $field['field_type'] != 'tab') {
                $layouts[$lastKey]['fields'][$tabsKey]['fields'][$isTab]['fields'][] = $field;

                continue;
            }

            if ($field['field_type'] == 'tab' && is_null($isTab) && is_null($tabsKey)) {
                $layouts = $this->createTabs($layouts, $lastKey);
                $tabsKey = array_key_last($layouts[$lastKey]['fields']);
            }

            if ($field['field_type'] == 'tab') {
                $layouts[$lastKey]['fields'][$tabsKey]['fields'][] = $field;
                $isTab = array_key_last($layouts[$lastKey]['fields'][$tabsKey]['fields']);
            } else {
                $layouts[$lastKey]['fields'][] = $field;
            }
        }

        return  $next($layouts);
    }
}
