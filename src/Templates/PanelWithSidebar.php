<?php

namespace Aura\Base\Templates;

class PanelWithSidebar
{
    public string $name = 'PanelWithSidebar';

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
                'style' => [
                    'width' => '70',
                ],
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
                'name' => 'Panel 2',
                'type' => 'Aura\\Base\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'panel-2',
                'style' => [
                    'width' => '30',
                ],
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
