<?php

namespace Aura\Base\Fields;

class Status extends Field
{
    public $component = 'aura::fields.status';

    public $optionGroup = 'Choice Fields';

    public $view = 'aura::fields.status-view';
    
    // Change all $component to $edit?
    public $edit = 'aura::fields.status';
    
    public $index = 'aura::fields.status-index';

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
                'type' => 'Aura\\Base\\Fields\\Status',
                'validation' => '',
                'slug' => 'color',
                'style' => [
                    'width' => '33',
                ],
                'options' => [
                    [
                        'key' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                        'value' => 'Blue',
                        'color' => 'bg-blue-100 text-blue-800 dark:bg-blue-900 dark:text-blue-300',
                    ],
                    [
                        'key' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                        'value' => 'Green',
                        'color' => 'bg-green-100 text-green-800 dark:bg-green-900 dark:text-green-300',
                    ],
                    [
                        'key' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                        'value' => 'Red',
                        'color' => 'bg-red-100 text-red-800 dark:bg-red-900 dark:text-red-300',
                    ],
                    [
                        'key' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                        'value' => 'Yellow',
                        'color' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900 dark:text-yellow-300',
                    ],
                    [
                        'key' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
                        'value' => 'Indigo',
                        'color' => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900 dark:text-indigo-300',
                    ],
                    [
                        'key' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                        'value' => 'Purple',
                        'color' => 'bg-purple-100 text-purple-800 dark:bg-purple-900 dark:text-purple-300',
                    ],
                    [
                        'key' => 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300',
                        'value' => 'Pink',
                        'color' => 'bg-pink-100 text-pink-800 dark:bg-pink-900 dark:text-pink-300',
                    ],
                    [
                        'key' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                        'value' => 'Gray',
                        'color' => 'bg-gray-100 text-gray-800 dark:bg-gray-700 dark:text-gray-300',
                    ],
                    [
                        'key' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                        'value' => 'Orange',
                        'color' => 'bg-orange-100 text-orange-800 dark:bg-orange-900 dark:text-orange-300',
                    ],
                    [
                        'key' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300',
                        'value' => 'Teal',
                        'color' => 'bg-teal-100 text-teal-800 dark:bg-teal-900 dark:text-teal-300',
                    ],
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
