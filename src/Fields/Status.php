<?php

namespace Aura\Base\Fields;

class Status extends Field
{
    public $component = 'aura::fields.status';

    public $optionGroup = 'Choice Fields';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Select',
                'name' => 'Select',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'select',
                'style' => [],
            ],

            [
                'name' => 'Options',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'validation' => '',
                'slug' => 'options',
                // 'set' => function($model, $field, $value) {

                //     // dd($model, $field, $value);
                //     $array = [];
                //     foreach ($value as $item) {
                //         $array[$item['value']] = $item['name'];
                //     }
                //     return $array;
                // },
                // 'get' => function($model, $form, $value) {
                //     $array = $value;
                //     $result = [];
                //     foreach ($array as $key => $val) {
                //         $result[] = ['value' => $key, 'name' => $val];
                //     }
                //     return $result;
                // }
            ],
            [
                'name' => 'Key',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'key',
                'style' => [
                    'width' => '33',
                ],

            ],
            [
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'value',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Color',
                'type' => 'Aura\\Base\\Fields\\Color',
                'validation' => '',
                'slug' => 'color',
                'style' => [
                    'width' => '33',
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

            [
                'name' => 'Allow Multiple',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'exclude_from_nesting' => true,
                'slug' => 'allow_multiple',
                'instructions' => 'Allow multiple selections?',
            ],

        ]);
    }

    // public $view = 'components.fields.select';

    public function options($model, $field)
    {
        // if get"$field->slug"Options is defined on the model, use that
        if (method_exists($model, 'get'.ucfirst($field['slug']).'Options')) {
            return $model->{'get'.ucfirst($field['slug']).'Options'}();
        }

        // return the options defined in the field
        return $field['options'] ?? [];
    }
}
