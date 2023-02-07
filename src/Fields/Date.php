<?php

namespace App\Aura\Fields;

class Date extends Field
{
    public string $component = 'fields.date';

    protected string $view = 'components.fields.date';

    public function get($field, $value)
    {
        return $value;
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Date',
                'name' => 'Date',
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'date',
                'style' => [],
            ],
            [
                'label' => 'Format',
                'name' => 'Format',

                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'format',
                'default' => 'd.m.Y',
                'instructions' => 'The format of the date. Default is d.m.Y. See <a href="https://www.php.net/manual/en/function.date.php" target="_blank">PHP Date</a> for more information.',
            ],
            [
                'label' => 'Enable Time',
                'name' => 'Enable Time',

                'type' => 'App\\Aura\\Fields\\Checkbox',
                'validation' => '',
                'options' => [
                    'true' => 'Enable time',
                ],
                'slug' => 'enable_time',
                'default' => false,
                'instructions' => 'Enable time selection. Default is false.',
            ],
            [
                'label' => 'Max Date',
                'name' => 'Max Date',

                'type' => 'App\\Aura\\Fields\\Number',
                'validation' => 'numeric|min:0|max:365',
                'slug' => 'maxDate',
                'default' => false,
                'instructions' => 'Number of days from today to the maximum selectable date.',
            ],
            [
                'name' => 'Week starts on',
                'type' => 'App\\Aura\\Fields\\Select',
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

    public function set($value)
    {
        return $value;
    }
}
