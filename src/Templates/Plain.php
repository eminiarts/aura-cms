<?php

namespace Eminiarts\Aura\Templates;

class Plain
{
    public string $name = 'Plain';

    public function getFields()
    {
        return [
            [
                'name' => 'Text',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'on_index' => true,
                'has_conditional_logic' => false,
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
