<?php

namespace Eminiarts\Aura\Fields;

class Email extends Field
{
    public $component = 'aura::fields.email';

    // public $view = 'components.fields.email';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Email',
                'name' => 'Email',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'email-tab',
                'style' => [],
            ],
            [
                'name' => 'Default Value',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Default value on create',
                'slug' => 'default',
            ],
            [
                'name' => 'Placeholder',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'placeholder',
            ],
            [
                'name' => 'Prefix',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'prefix',
                'style' => [
                    'width' => '50',
                ],

            ],
            [
                'name' => 'Suffix',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'suffix',
                'style' => [
                    'width' => '50',
                ],

            ],
        ]);
    }
}
