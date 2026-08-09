<?php

namespace Aura\Base\Fields;

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Contracts\FieldValueStorage;
use Aura\Base\Support\TemporalValue;
use Illuminate\Database\Eloquent\Model;

class Datetime extends Field
{
    public const DEFAULT_DISPLAY_FORMAT = 'd.m.Y H:i';

    public $edit = 'aura::fields.datetime';

    public $index = 'aura::fields.datetime-index';

    public $optionGroup = 'Input Fields';

    // public $view = 'components.fields.datetime';

    public $tableColumnType = 'timestamp';

    public $view = 'aura::fields.view-value';

    public function displayValue(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueContext $context = FieldValueContext::Index,
    ): mixed {
        $field['_aura_hydrated'] = true;

        return parent::displayValue($value, $field, $model, $context);
    }

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
                'instructions' => 'The format accepted and emitted by create/edit controls. Values are persisted as Y-m-d H:i:s in the storage timezone. See <a href="https://www.php.net/manual/en/function.date.php" target="_blank">PHP Date</a> for more information.',
            ],
            [
                'name' => 'Display Format',

                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'display_format',
                'default' => config('aura.fields.datetime.display_format', self::DEFAULT_DISPLAY_FORMAT),
                'instructions' => 'How the datetime is displayed on index and view surfaces. See <a href="https://www.php.net/manual/en/function.date.php" target="_blank">PHP Date</a> for more information.',
            ],
            [
                'name' => 'Input Timezone',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'nullable|timezone:all',
                'slug' => 'input_timezone',
                'instructions' => 'Timezone used to interpret form values. Defaults to the display timezone.',
            ],
            [
                'name' => 'Display Timezone',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'nullable|timezone:all',
                'slug' => 'display_timezone',
                'instructions' => 'Timezone used for edit, index, and view values. Defaults to the application timezone.',
            ],
            [
                'name' => 'Storage Timezone',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'nullable|timezone:all',
                'slug' => 'storage_timezone',
                'default' => config('aura.fields.datetime.storage_timezone'),
                'instructions' => 'Timezone used for persisted timestamp values. Defaults to the application timezone for backward compatibility.',
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

    public function hydrateFromStorage(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueStorage $storage,
        FieldValueContext $context = FieldValueContext::Model,
    ): mixed {
        return TemporalValue::hydrateDatetime($value, $field, $context);
    }

    public function normalizeForStorage(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueStorage $storage,
    ): mixed {
        return TemporalValue::normalizeDatetime($value, $field);
    }

    public function set($post, $field, $value)
    {
        return TemporalValue::normalizeDatetime($value, is_array($field) ? $field : []);
    }
}
