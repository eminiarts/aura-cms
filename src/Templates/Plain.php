<?php

namespace Eminiarts\Aura\Templates;

class Plain
{
    public string $name = 'Plain';

    public function getFields()
    {
        return [
            [
                'name' => 'Panel 1',
                'type' => 'Eminiarts\\Aura\\Fields\\Panel',
                'validation' => '',
                'on_index' => true,
                'conditional_logic' => [
                ],
                'slug' => 'panel-1',
            ],
            [
                'name' => 'Text',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'on_index' => true,
                'conditional_logic' => [
                ],
                'slug' => 'title',
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }
}
