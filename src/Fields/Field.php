<?php

namespace Aura\Base\Fields;

use Aura\Base\Traits\InputFields;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Str;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Livewire\Wireable;

abstract class Field implements Wireable
{
    use InputFields;
    use Macroable;
    use Tappable;

    public $edit = null;

    public $field;

    public bool $group = false;

    public $index = null;

    public bool $on_forms = true;

    public $optionGroup = 'Fields';

    public bool $sameLevelGrouping = false;

    public $tableColumnType = 'string';

    public $tableNullable = true;

    public bool $taxonomy = false;

    public string $type = 'input';

    public $view = null;

    public $wrap = false;

    public $wrapper = null;

    public function display($field, $value, $model)
    {

        if (optional($field)['display_view']) {
            return view($field['display_view'], ['row' => $model, 'field' => $field, 'value' => $value])->render();
        }

        if ($this->index) {
            $componentName = $this->index;
            // If the component name starts with 'aura::', remove it
            if (Str::startsWith($componentName, 'aura::')) {
                //   $componentName = Str::after($componentName, 'aura::');
            }

            // Ensure the component name starts with 'fields.'
            if (! Str::startsWith($componentName, 'fields.')) {
                // $componentName = 'fields.' . $componentName;
            }

            return Blade::render(
                '<x-dynamic-component :component="$componentName" :row="$row" :field="$field" :value="$value" />',
                [
                    'componentName' => $componentName,
                    'row' => $model,
                    'field' => $field,
                    'value' => $value,
                ]
            );
        }

        return $value;
    }

    public function edit()
    {
        if ($this->edit) {
            return $this->edit;
        }
    }

    // public $edit;

    public function field($field)
    {
        // $this->field = $field;
        // $this->withAttributes($field);
        return $this;

        return get_class($this);
    }

    public function filterOptions()
    {
        return [
            'contains' => __('contains'),
            'does_not_contain' => __('does not contain'),
            'is' => __('is'),
            'is_not' => __('is not'),
            'starts_with' => __('starts with'),
            'ends_with' => __('ends with'),
            'is_empty' => __('is empty'),
            'is_not_empty' => __('is not empty'),
            'equals' => __('equals'),
            'not_equals' => __('does not equal'),
            'greater_than' => __('greater than'),
            'less_than' => __('less than'),
            'greater_than_or_equal' => __('greater than or equal to'),
            'less_than_or_equal' => __('less than or equal to'),
            'in' => __('in'),
            'not_in' => __('not in'),
            'like' => __('like'),
            'not_like' => __('not like'),
            'regex' => __('matches regex'),
            'not_regex' => __('does not match regex'),
        ];
    }

    public static function fromLivewire($data)
    {
        $field = new static;

        $field->type = $data['type'];
        $field->view = $data['view'];

        return $field;
    }

    public function get($class, $value, $field = null)
    {
        return $value;
    }

    public function getFields()
    {
        return [
            [
                'name' => 'Field',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'field',

                'style' => [],
            ],
            [
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'slug' => 'name',
            ],
            [
                'name' => 'Slug',
                'type' => 'Aura\\Base\\Fields\\Slug',
                'validation' => 'required|regex:/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/|not_regex:/^[0-9]+$/',
                'slug' => 'slug',
                'based_on' => 'name',
                'custom' => true,
                'disabled' => true,
            ],
            [
                'name' => 'Validation',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'validation',
            ],
            [
                'name' => 'Type',
                'type' => 'Aura\\Base\\Fields\\Select',
                'live' => true,
                'validation' => 'required',
                'slug' => 'type',
                'options' => app('aura')::getFieldsWithGroups(),
            ],
            [
                'name' => 'instructions',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'instructions',
            ],

            [
                'name' => 'Searchable',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'instructions' => 'Defines if the field is searchable.',
                'validation' => '',
                'slug' => 'searchable',
                'default' => false,
            ],

            [
                'name' => 'View',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-2',
                'style' => [],

            ],

            [
                'name' => 'On Index',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'instructions' => 'Show on the index page / table.',
                'slug' => 'on_index',
            ],
            [
                'name' => 'On Forms',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'instructions' => 'Show on the create and edit forms.',
                'slug' => 'on_forms',
            ],
            [
                'name' => 'On View',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'instructions' => 'Show on the view page.',
                'validation' => '',
                'slug' => 'on_view',
            ],

            [
                'name' => 'Width',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'suffix' => '%',
                'instructions' => 'Width of the field in the form in %.',
                'slug' => 'style.width',
            ],

            [
                'name' => 'Conditional Logic',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'conditional_logic',
                'style' => [],

            ],

            [
                'name' => 'Add Condition',
                'type' => 'Aura\\Base\\Fields\\Repeater',
                'validation' => '',
                'conditional_logic' => [],
                'style' => [
                    'width' => '100',
                ],
                'slug' => 'conditional_logic',
            ],
            [
                'name' => 'Type',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Slug of the field to check. You can also use "role"',
                'conditional_logic' => [],
                'slug' => 'field',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Operator',
                'type' => 'Aura\\Base\\Fields\\Select',
                'validation' => '',
                'options' => [
                    '' => 'Please Select',
                    '==' => '==',
                    '!=' => '!=',
                    '>' => '>',
                    '>=' => '>=',
                    '<' => '<',
                    '<=' => '<=',
                ],
                'conditional_logic' => [],
                'slug' => 'operator',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'name' => 'Value',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'value',
                'style' => [
                    'width' => '33',
                ],
            ],

        ];
    }

    // public function view($view, $data = [], $mergeData = [])
    // {
    //     $this->view = $view;

    //     return $this;
    // }

    public function getFilterValues($model, $field)
    {
        // Default implementation returns an empty array
        // Most field types don't need predefined values for filtering
        return [];
    }

    public function isDisabled($model, $field)
    {
        if (optional($field)['disabled'] instanceof \Closure) {
            return $field['disabled']($model);
        }

        return $field['disabled'] ?? false;
    }

    public function isInputField()
    {
        return in_array($this->type, ['input', 'repeater', 'group']);
    }

    public function isRelation()
    {
        return in_array($this->type, ['relation']);
    }

    public function isTaxonomyField()
    {
        return $this->taxonomy;
    }

    public function toLivewire()
    {
        return [
            'type' => $this->type,
            'view' => $this->view,
        ];
    }

    public function value($value)
    {
        return $value;
    }

    public function view()
    {
        if ($this->view) {
            return $this->view;
        }

        if ($this->edit) {
            return $this->edit;
        }
    }
}
