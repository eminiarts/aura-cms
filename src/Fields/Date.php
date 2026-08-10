<?php

namespace Aura\Base\Fields;

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Contracts\FieldValueStorage;
use Aura\Base\Contracts\ProvidesFilterCapability;
use Aura\Base\Fields\Filters\FilterCapability;
use Aura\Base\Resource;
use Aura\Base\Support\TemporalValue;
use Illuminate\Database\Eloquent\Model;

class Date extends Field implements ProvidesFilterCapability
{
    public const DEFAULT_DISPLAY_FORMAT = 'd.m.Y';

    public $edit = 'aura::fields.date';

    public $index = 'aura::fields.date-index';

    public $optionGroup = 'Input Fields';

    public $tableColumnType = 'date';

    public $view = 'aura::fields.view-value';

    public function filterOptions()
    {
        return [
            'date_is' => __('is'),
            'date_is_not' => __('is not'),
            'date_before' => __('before'),
            'date_after' => __('after'),
            'date_on_or_before' => __('on or before'),
            'date_on_or_after' => __('on or after'),
            'date_is_empty' => __('is empty'),
            'date_is_not_empty' => __('is not empty'),
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
                'label' => 'Date',
                'name' => 'Date',
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
                'default' => 'd.m.Y',
                'instructions' => 'The format accepted and emitted by create/edit controls. Values are persisted as Y-m-d. See <a href="https://www.php.net/manual/en/function.date.php" target="_blank">PHP Date</a> for more information.',
            ],
            [
                'name' => 'Display Format',

                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'display_format',
                'default' => config('aura.fields.date.display_format', self::DEFAULT_DISPLAY_FORMAT),
                'instructions' => 'How the date is displayed on index and view surfaces. See <a href="https://www.php.net/manual/en/function.date.php" target="_blank">PHP Date</a> for more information.',
            ],
            [
                'label' => 'Enable Input',
                'name' => 'Enable Input',

                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'options' => [
                    'true' => 'Enable Input',
                ],
                'slug' => 'enable_input',
                'default' => true,
                'instructions' => 'Enable user input. Default is true.',
            ],
            [
                'label' => 'Max Date',
                'name' => 'Max Date',

                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => 'numeric|min:0|max:365',
                'slug' => 'maxDate',
                'default' => false,
                'instructions' => 'Number of days from today to the maximum selectable date.',
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
        return TemporalValue::hydrateDate($value, $field, $context);
    }

    public function normalizeForStorage(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueStorage $storage,
    ): mixed {
        return TemporalValue::normalizeDate($value, $field);
    }

    public function presentValue(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueContext $context = FieldValueContext::Index,
    ): mixed {
        $field['_aura_hydrated'] = true;

        return parent::presentValue($value, $field, $model, $context);
    }

    public function provideAuraFilterCapability(Resource $model, array $field): FilterCapability
    {
        return FilterCapability::date($this->filterOptions(), 'Y-m-d');
    }

    public function set($post, $field, $value)
    {
        return TemporalValue::normalizeDate($value, is_array($field) ? $field : []);
    }
}
