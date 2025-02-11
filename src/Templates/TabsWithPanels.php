<?php

namespace Aura\Base\Templates;

class TabsWithPanels
{
    public string $name = 'TabsWithPanels';

    public function getFields()
    {
        return [
            [
                'name' => 'Tab 1',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'validation' => '',
                'global' => true,
                'conditional_logic' => [],
                'slug' => 'tab_1',
            ],
            [
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'panel_1',
            ],
            [
                'name' => 'Text 1',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'text_1',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Tab 2',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'validation' => '',
                'global' => true,
                'conditional_logic' => [],
                'slug' => 'tab_2',
            ],
            [
                'name' => 'Panel 2',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'panel_2',
            ],
            [
                'name' => 'Text 2',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'text_2',
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }
}
