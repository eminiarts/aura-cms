<?php

namespace Aura\Base\Fields;

use Aura\Base\Contracts\ProvidesFilterCapability;
use Aura\Base\Fields\Filters\FilterCapability;
use Aura\Base\Resource;
use Illuminate\Support\HtmlString;

class Boolean extends Field implements ProvidesFilterCapability
{
    public $edit = 'aura::fields.boolean';

    public $optionGroup = 'Choice Fields';

    public $view = 'aura::fields.view-value';

    public function display($field, $value, $model)
    {
        $normalized = $this->normalizeBoolean($value);

        if ($normalized === null || $normalized === '') {
            return $normalized;
        }

        if ($normalized === true) {
            return new HtmlString('<svg class="w-6 h-6" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"></path></svg>'); // Check icon from Heroicons
        }

        if ($normalized === false) {
            return new HtmlString('<svg class="w-6 h-6 text-gray-200" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>'); // X icon from Heroicons
        }

        return parent::display($field, $value, $model);
    }

    public function filterOptions()
    {
        return [
            'equals' => __('equals'),
            'not_equals' => __('does not equal'),
            'is' => __('is'),
            'is_not' => __('is not'),
            'is_empty' => __('is empty'),
            'is_not_empty' => __('is not empty'),
        ];
    }

    public function get($class, $value, $field = null)
    {
        return $this->normalizeBoolean($value);
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
                'default' => false,
            ],

        ]);
    }

    public function provideAuraFilterCapability(Resource $model, array $field): FilterCapability
    {
        return FilterCapability::boolean($this->filterOptions());
    }

    public function set($post, $field, $value)
    {
        return $this->normalizeBoolean($value);
    }

    public function value($value)
    {
        return $this->normalizeBoolean($value);
    }

    private function normalizeBoolean(mixed $value): mixed
    {
        if ($value === null || $value === '' || is_bool($value)) {
            return $value;
        }

        if (is_int($value) || is_float($value)) {
            return match ($value) {
                0, 0.0 => false,
                1, 1.0 => true,
                default => $value,
            };
        }

        if (! is_string($value)) {
            return $value;
        }

        return match (strtolower(trim($value))) {
            '0', 'false', 'off', 'no' => false,
            '1', 'true', 'on', 'yes' => true,
            default => $value,
        };
    }
}
