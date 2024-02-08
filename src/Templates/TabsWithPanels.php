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
                'on_index' => true,
                'global' => true,
                'conditional_logic' => [],
                'slug' => 'tab-1',
            ],
            [
                'name' => 'Panel 1',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'panel-1',
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
                'global' => true,
                'conditional_logic' => [],
                'slug' => 'tab-2',
            ],
            [
                'name' => 'Panel 2',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'panel-2',
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
