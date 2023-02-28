<?php

namespace Eminiarts\Aura\Templates;

class PanelWithTabs
{
    public string $name = 'PanelWithTabs';

    public function getFields()
    {
        return [
            [
                'name' => 'Panel 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'has_conditional_logic' => false,
                'conditional_logic' => [
                ],
                'slug' => 'panel-1',
            ],
            [
                'name' => 'Tab 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'validation' => '',
                'on_index' => true,
                'has_conditional_logic' => false,
                'conditional_logic' => [
                ],
                'slug' => 'tab-1',
            ],
            [
                'name' => 'Text 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'has_conditional_logic' => false,
                'conditional_logic' => [
                ],
                'slug' => 'text-1',
                'style' => [
                    'width' => '100',
                ],
            ],
            [
                'name' => 'Tab 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'validation' => '',
                'on_index' => true,
                'has_conditional_logic' => false,
                'conditional_logic' => [
                ],
                'slug' => 'tab-2',
            ],
            [
                'name' => 'Text 2',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'on_index' => true,
                'has_conditional_logic' => false,
                'conditional_logic' => [
                ],
                'slug' => 'text-2',
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }
}
