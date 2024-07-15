<?php

namespace Aura\Base\Fields;

class Email extends Field
{
    public $component = 'aura::fields.email';

    public $view = 'aura::fields.view-value';

    public $optionGroup = 'Input Fields';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Email',
                'name' => 'Email',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'email-tab',
                'style' => [],
            ],
            [
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Default value on create',
                'slug' => 'default',
            ],
            [
                'name' => 'Placeholder',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'placeholder',
            ],
        ]);
    }
}
