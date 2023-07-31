<?php

namespace Eminiarts\Aura\Fields;

use Eminiarts\Aura\Livewire\Post\View;
use Eminiarts\Aura\Traits\InputFields;
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

    public bool $group = false;

    public bool $taxonomy = false;

    public string $type = 'input';

    public $view = null;

    public function component()
    {
        if ($this->view && is_string(request()->route()->action['uses']) && str(request()->route()->action['uses'])->contains(View::class)) {
            return $this->view;
        }

        return $this->component;
    }

    public function display($field, $value, $model)
    {
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
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'field',
                'style' => [],
            ],
            [
                'label' => 'Name',
                'name' => 'Name',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'slug' => 'name',
            ],
            [
                'label' => 'Slug',
                'name' => 'Slug',
                'type' => 'Eminiarts\\Aura\\Fields\\Slug',
                'validation' => 'required',
                'slug' => 'slug',
                'based_on' => 'name',
            ],
            [
                'label' => 'Validation',
                'name' => 'Validation',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'validation',
            ],
            [
                'label' => 'Type',
                'name' => 'Type',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
                'validation' => 'required',
                'slug' => 'type',
                'options' => [
                    'option_group_1' => 'Input Fields',
                    'Eminiarts\\Aura\\Fields\\Text' => 'Text',
                    'Eminiarts\\Aura\\Fields\\Textarea' => 'Textarea',
                    'Eminiarts\\Aura\\Fields\\Number' => 'Number',
                    'Eminiarts\\Aura\\Fields\\Email' => 'Email',
                    'Eminiarts\\Aura\\Fields\\Phone' => 'Phone',

                    'option_group_2' => 'Media Fields',
                    'Eminiarts\\Aura\\Fields\\Image' => 'Image',
                    'Eminiarts\\Aura\\Fields\\File' => 'File',

                    'option_group_3' => 'Choice Fields',
                    'Eminiarts\\Aura\\Fields\\Select' => 'Select',
                    'Eminiarts\\Aura\\Fields\\Radio' => 'Radio',
                    'Eminiarts\\Aura\\Fields\\Checkbox' => 'Checkbox',
                    'Eminiarts\\Aura\\Fields\\Boolean' => 'Boolean',

                    'option_group_4' => 'JS Fields',
                    'Eminiarts\\Aura\\Fields\\Wysiwyg' => 'Wysiwyg',
                    'Eminiarts\\Aura\\Fields\\Code' => 'Code',
                    'Eminiarts\\Aura\\Fields\\Color' => 'Color',

                    'Eminiarts\\Aura\\Fields\\Date' => 'Date',
                    'Eminiarts\\Aura\\Fields\\Time' => 'Time',
                    'Eminiarts\\Aura\\Fields\\Datetime' => 'Datetime',

                    'option_group_5' => 'Layout Fields',
                    'Eminiarts\\Aura\\Fields\\Heading' => 'Heading',
                    'Eminiarts\\Aura\\Fields\\HorizontalLine' => 'Horizontal line',

                    'option_group_6' => 'Structure Fields',
                    'Eminiarts\\Aura\\Fields\\Repeater' => 'Repeater',
                    'Eminiarts\\Aura\\Fields\\Tab' => 'Tab',
                    'Eminiarts\\Aura\\Fields\\Panel' => 'Panel',
                    'Eminiarts\\Aura\\Fields\\Group' => 'Group',

                    'option_group_7' => 'Relationship Fields',
                    'Eminiarts\\Aura\\Fields\\BelongsTo' => 'BelongsTo',
                    'Eminiarts\\Aura\\Fields\\HasMany' => 'HasMany',
                    'Eminiarts\\Aura\\Fields\\AdvancedSelect' => 'AdvancedSelect',

                    'option_group_8' => 'Special Fields',
                    'Eminiarts\\Aura\\Fields\\Permissions' => 'Permissions',
                ],
            ],
            [
                'name' => 'instructions',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'instructions',
            ],

            [
                'name' => 'Searchable',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'instructions' => 'Defines if the field is searchable.',
                'validation' => '',
                'slug' => 'searchable',
            ],

            [
                'name' => 'View',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'tab-2',
                'style' => [],
            ],

            [
                'name' => 'On Index',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'validation' => '',
                'instructions' => 'Show on the index page / table.',
                'slug' => 'on_index',
            ],
            [
                'name' => 'On Forms',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'validation' => '',
                'instructions' => 'Show on the create and edit forms.',
                'slug' => 'on_forms',
            ],
            [
                'name' => 'On View',
                'type' => 'Eminiarts\\Aura\\Fields\\Boolean',
                'instructions' => 'Show on the view page.',
                'validation' => '',
                'slug' => 'on_view',
            ],

            [
                'name' => 'Width',
                'type' => 'Eminiarts\\Aura\\Fields\\Number',
                'validation' => '',
                'suffix' => '%',
                'instructions' => 'Width of the field in the form in %.',
                'slug' => 'style.width',
            ],

            [
                'label' => 'Conditional Logic',
                'name' => 'Conditional Logic',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'conditional_logic',
                'style' => [],
            ],

            [
                'label' => 'Add Condition',
                'name' => 'Add Condition',
                'type' => 'Eminiarts\\Aura\\Fields\\Repeater',
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
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
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
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
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
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'value',
                'style' => [
                    'width' => '33',
                ],
            ],

        ];
    }

    public function isInputField()
    {
        return in_array($this->type, ['input', 'repeater', 'group']);
    }

    public function isTaxonomyField()
    {
        return $this->taxonomy;
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

    public function toLivewire()
    {
        return [
            'type' => $this->type,
            'view' => $this->view,
        ];
    }

    public static function fromLivewire($data)
    {
        $field = new static();

        $field->type = $data['type'];
        $field->view = $data['view'];

        return $field;
    }
}
