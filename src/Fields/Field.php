<?php

namespace Aura\Base\Fields;

use Aura\Base\Traits\InputFields;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Livewire\Wireable;

class Field implements Wireable
{
    use InputFields;
    use Macroable;
    use Tappable;

    public $component = null;

    public $field;

    public $tableColumnType = 'string';

    public bool $group = false;

    public bool $on_forms = true;

    public $optionGroup = 'Fields';

    public bool $taxonomy = false;

    public string $type = 'input';

    public $view = null;

    public function component()
    {
        if ($this->view && is_string(request()->route()->action['uses']) && str(request()->route()->action['uses'])->contains('View')) {
            return $this->view;
        }

        return $this->component;
    }

    public function display($field, $value, $model)
    {
        if (optional($field)['display_view']) {
            return view($field['display_view'], ['row' => $model, 'field' => $field])->render();
        }

        return $value;
    }

    // public $component;

    public function field($field)
    {
        // $this->field = $field;
        //$this->withAttributes($field);
        return $this;

        return get_class($this);
    }

    public static function fromLivewire($data)
    {
        $field = new static();

        $field->type = $data['type'];
        $field->view = $data['view'];

        return $field;
    }

    public function get($field, $value)
    {
        return $value;
    }

    public function getFields()
    {
        return [
            [
                'label' => 'Field',
                'name' => 'Field',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'field',
                'style' => [],
            ],
            [
                'label' => 'Name',
                'name' => 'Name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'slug' => 'name',
            ],
            [
                'label' => 'Slug',
                'name' => 'Slug',
                'type' => 'Aura\\Base\\Fields\\Slug',
                'validation' => 'required',
                'slug' => 'slug',
                'based_on' => 'name',
                'custom' => true,
                'disabled' => true,
            ],
            [
                'label' => 'Validation',
                'name' => 'Validation',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'validation',
            ],
            [
                'label' => 'Type',
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
                'label' => 'Conditional Logic',
                'name' => 'Conditional Logic',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'conditional_logic',
                'style' => [],
            ],

            [
                'label' => 'Add Condition',
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
                'label' => 'Type',
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
                'label' => 'Operator',
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
                'label' => 'Value',
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

    public function view($view, $data = [], $mergeData = [])
    {
        $this->view = $view;

        return $this;
    }
}
