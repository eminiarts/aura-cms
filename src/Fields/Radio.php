<?php

namespace Aura\Base\Fields;

use Aura\Base\Fields\Filters\FilterCapability;
use Aura\Base\Resource;

class Radio extends Field
{
    public $edit = 'aura::fields.radio';

    public $optionGroup = 'Choice Fields';

    // public $view = 'components.fields.radio';

    public function filterCapability(Resource $model, array $field): FilterCapability
    {
        return $this->optionFilterCapability($model, $field);
    }

    public function filterOptions()
    {
        return [
            'is' => __('is'),
            'is_not' => __('is not'),
            'is_empty' => __('is empty'),
            'is_not_empty' => __('is not empty'),
        ];
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Radio',
                'name' => 'Radio',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'radio',
                'style' => [],
            ],

            [
                'name' => 'Options',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'validation' => '',
                'slug' => 'options',
            ],
            [
                'name' => 'Key',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'key',
                'style' => [
                    'width' => '50',
                ],

            ],
            [
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'value',
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Default Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Default value on create',
                'exclude_from_nesting' => true,
                'slug' => 'default',
            ],
        ]);
    }

    public function getFilterValues($model, $field)
    {
        return $this->options($model, $field);
    }

    public function options($model, $field)
    {
        if (method_exists($model, 'get'.ucfirst($field['slug']).'Options')) {
            return $model->{'get'.ucfirst($field['slug']).'Options'}();
        }

        return $field['options'] ?? [];
    }
}
