<?php

namespace Aura\Base\Fields;

class Time extends Field
{
    public $edit = 'aura::fields.time';

    // public $view = 'components.fields.time';

    public $optionGroup = 'Input Fields';

    public function get($class, $value, $field = null)
    {
        return $value;
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Time',
                'name' => 'Time',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'date',
                'style' => [],
            ],
            [
                'label' => 'Format',
                'name' => 'Format',

                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'format',
                'default' => 'H:i',
                'instructions' => 'The format of how the date gets stored in the DB. Default is H:i. See <a href="https://www.php.net/manual/en/function.date.php" target="_blank">PHP Date</a> for more information.',
            ],
            [
                'name' => 'Display Format',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'display_format',
                'default' => 'H:i',
                'instructions' => 'How the Date gets displayed. Default is H:i. See <a href="https://www.php.net/manual/en/function.date.php" target="_blank">PHP Date</a> for more information.',
            ],
            [
                'label' => 'Enable Input',
                'name' => 'Enable Input',

                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'slug' => 'enable_input',
                'default' => true,
                'instructions' => 'Enable user input. Default is true.',
            ],
            [
                'label' => 'Enable Seconds',
                'name' => 'Enable Seconds',

                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'slug' => 'enable_seconds',
                'default' => false,
                'instructions' => 'Enable seconds. Default is false.',
            ],

            [
                'name' => 'Min Time',
                'type' => 'Aura\\Base\\Fields\\Time',
                'validation' => '',
                'slug' => 'minTime',
                'default' => false,
                'instructions' => null,
            ],

            [
                'name' => 'Max Time',
                'type' => 'Aura\\Base\\Fields\\Time',
                'validation' => '',
                'slug' => 'maxTime',
                'default' => false,
                'instructions' => null,
            ],
        ]);
    }

    public function set($post, $field, $value)
    {
        return $value;
    }
}
