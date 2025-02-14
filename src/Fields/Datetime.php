<?php

namespace Aura\Base\Fields;

class Datetime extends Field
{
    public $edit = 'aura::fields.datetime';

    public $optionGroup = 'Input Fields';

    // public $view = 'components.fields.datetime';

    public $tableColumnType = 'timestamp';

    public $view = 'aura::fields.view-value';

    public function filterOptions()
    {
        return [
            'is' => __('is'),
            'is_not' => __('is not'),
            'before' => __('before'),
            'after' => __('after'),
            'on_or_before' => __('on or before'),
            'on_or_after' => __('on or after'),
            'is_empty' => __('is empty'),
            'is_not_empty' => __('is not empty'),
        ];
    }

    public function get($class, $value, $field = null)
    {
        return $value;
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Datetime',
                'name' => 'Datetime',
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
                'default' => 'd.m.Y H:i',
                'instructions' => 'The format of how the date gets stored in the DB. Default is d.m.Y H:i. See <a href="https://www.php.net/manual/en/function.date.php" target="_blank">PHP Date</a> for more information.',
            ],
            [
                'name' => 'Display Format',

                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'display_format',
                'default' => 'd.m.Y H:i',
                'instructions' => 'How the Date gets displayed. Default is d.m.Y H:i. See <a href="https://www.php.net/manual/en/function.date.php" target="_blank">PHP Date</a> for more information.',
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
                'label' => 'Max Date',
                'name' => 'Max Date',

                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'slug' => 'maxDate',
                'default' => false,
                'instructions' => 'Number of days from today to the maximum selectable date.',
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

            [
                'name' => 'Week starts on',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => '',
                'options' => [
                    '0' => 'Sunday',
                    '1' => 'Monday',
                    '2' => 'Tuesday',
                    '3' => 'Wednesday',
                    '4' => 'Thursday',
                    '5' => 'Friday',
                    '6' => 'Saturday',
                ],
                'slug' => 'weekStartsOn',
                'default' => 1,
                'instructions' => 'The day the week starts on. 0 (Sunday) to 6 (Saturday). Default is 1 (Monday).',
            ],
        ]);
    }

    public function set($post, $field, $value)
    {
        return $value;
    }
}
