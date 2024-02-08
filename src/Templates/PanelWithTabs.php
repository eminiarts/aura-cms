<?php

namespace Aura\Base\Templates;

class PanelWithTabs
{
    public string $name = 'PanelWithTabs';

    public function getFields()
    {
        return [
            [
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'panel-1',
            ],
            [
                'name' => 'Tab 1',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'tab-1',
            ],
            [
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'text-1',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Tab 2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'tab-2',
            ],
            [
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'text-2',
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }
}
