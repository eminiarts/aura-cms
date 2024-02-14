<?php

namespace Aura\Base\Fields;

class Boolean extends Field
{
    public $optionGroup = 'Choice Fields';

    public $component = 'aura::fields.boolean';

    public $view = 'aura::fields.view-value';

    public function display($field, $value, $model)
    {
        if ($value) {
            return '<svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'; // Check icon from Heroicons
        } else {
            return '<svg class="w-6 h-6 text-gray-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>'; // X icon from Heroicons
        }
    }

    public function get($field, $value)
    {
        return (bool) $value;
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Boolean',
                'name' => 'Boolean',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'boolean-tab',
                'style' => [],
            ],
            [
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'instructions' => 'Default value on create',
                'slug' => 'default',
            ],

        ]);
    }

    public function set($value)
    {
        return (bool) $value;
    }

    public function value($value)
    {
        return (bool) $value;
    }
}
