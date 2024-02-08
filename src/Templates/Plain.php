<?php

namespace Aura\Base\Templates;

class Plain
{
    public string $name = 'Plain';

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
                'name' => 'Text',
                'type' => 'Aura\\Base\\Fields\\Text',
                'on_index' => true,
                'conditional_logic' => [],
                'slug' => 'title',
                'style' => [
                    'width' => '100',
                ],
            ],
        ];
    }
}
