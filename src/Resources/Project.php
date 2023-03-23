<?php

namespace Eminiarts\Aura\Resources;

use Eminiarts\Aura\Resource;

class Project extends Resource
{
    public static $fields = [];

    public static ?string $slug = 'project';

    public static string $type = 'Project';

    public static function getFields()
    {
        return [
            '.number' => [
                'id' => 3,
                'label' => 'Number',
                'name' => 'Number',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => 'required',
                'on_index' => true,
                'has_conditional_logic' => false,
                'conditional_logic' => [
                    0 => [
                        0 => [
                            'param' => '',
                            'operator' => '=',
                            'value' => '',
                        ],
                    ],
                ],
                'slug' => 'number',
                'style' => [
                    'width' => '50',
                ],
            ],
            '.text2' => [
                'id' => 1,
                'name' => 'Text Ivan Ho',
                'slug' => 'text2',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'on_index' => false,
                'conditional_logic' => [
                    0 => [
                        0 => [
                            'param' => 'bild',
                            'operator' => 'contains',
                            'value' => 'Numberssss2',
                        ],
                        1 => [
                            'param' => 'number',
                            'operator' => 'is_empty',
                            'value' => '',
                        ],
                    ],
                    1 => [
                        0 => [
                            'param' => 'bild',
                            'operator' => 'is_empty',
                            'value' => '',
                        ],
                    ],
                ],
                'has_conditional_logic' => false,
                'wrapper' => '',
            ],
            '.description' => [
                'id' => 2,
                'label' => 'Text',
                'name' => 'Beschreibung',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'has_conditional_logic' => false,
                'conditional_logic' => [
                ],
                'slug' => 'description',
                'on_index' => false,
            ],
            '.bild' => [
                'id' => 4,
                'label' => 'Image',
                'name' => 'Bild',
                'type' => 'Eminiarts\\Aura\\Fields\\Image',
                'validation' => 'nullable|img',
                'has_conditional_logic' => false,
                'on_index' => false,
                'conditional_logic' => [
                ],
                'wrapper' => [
                    'width' => '',
                    'class' => 'custom-image',
                    'id' => '',
                ],
                'slug' => 'bild',
                'style' => [
                    'width' => '50%',
                    'showInTable' => true,
                ],
            ],
        ];
    }

    public function icon()
    {
        return '<svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21h10a2 2 0 002-2V9.414a1 1 0 00-.293-.707l-5.414-5.414A1 1 0 0012.586 3H7a2 2 0 00-2 2v14a2 2 0 002 2z"></path></svg>';
    }

    public function rowView()
    {
        return 'projects.row';
    }
}
