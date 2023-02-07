<?php

namespace Eminiarts\Aura\Fields;

use Eminiarts\Aura\Traits\InputFields;
use Exception;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Illuminate\View\Component;
use Illuminate\View\View;

class Field extends Component implements Htmlable
{
    use InputFields;
    use Macroable;
    use Tappable;

    public $field;

    public bool $group = false;

    public bool $taxonomy = false;

    public string $type = 'input';

    protected string $view;

    public function display($field, $value, $model)
    {
        return $value;
    }

    // public $component;

    public function field($field)
    {
        // $this->field = $field;
        $this->withAttributes($field);

        return $this;
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
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'field',
                'style' => [],
            ],
            [
                'label' => 'Name',
                'name' => 'Name',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => 'required',
                'slug' => 'name',
            ],
            [
                'label' => 'Slug',
                'name' => 'Slug',
                'type' => 'App\\Aura\\Fields\\Slug',
                'validation' => 'required',
                'slug' => 'slug',
                'based_on' => 'name',
            ],
            [
                'label' => 'Validation',
                'name' => 'Validation',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'validation',
            ],
            [
                'label' => 'Type',
                'name' => 'Type',
                'type' => 'App\\Aura\\Fields\\Select',
                'validation' => 'required',
                'slug' => 'type',
                'options' => [
                    'option_group_1' => 'Input Fields',
                    'App\\Aura\\Fields\\Text' => 'Text',
                    'App\\Aura\\Fields\\Textarea' => 'Textarea',
                    'App\\Aura\\Fields\\Number' => 'Number',
                    'App\\Aura\\Fields\\Email' => 'Email',
                    'App\\Aura\\Fields\\Phone' => 'Phone',

                    'option_group_2' => 'Media Fields',
                    'App\\Aura\\Fields\\Image' => 'Image',
                    'App\\Aura\\Fields\\File' => 'File',

                    'option_group_3' => 'Choice Fields',
                    'App\\Aura\\Fields\\Select' => 'Select',
                    'App\\Aura\\Fields\\Radio' => 'Radio',
                    'App\\Aura\\Fields\\Checkbox' => 'Checkbox',
                    'App\\Aura\\Fields\\Toggle' => 'Toggle',
                    'App\\Aura\\Fields\\Switch' => 'Switch',

                    'option_group_4' => 'JS Fields',
                    'App\\Aura\\Fields\\Wysiwyg' => 'Wysiwyg',
                    'App\\Aura\\Fields\\Code' => 'Code',
                    'App\\Aura\\Fields\\Color' => 'Color',

                    'App\\Aura\\Fields\\Date' => 'Date',
                    'App\\Aura\\Fields\\Time' => 'Time',
                    'App\\Aura\\Fields\\Datetime' => 'Datetime',

                    'option_group_5' => 'Layout Fields',
                    'App\\Aura\\Fields\\Heading' => 'Heading',
                    'App\\Aura\\Fields\\HorizontalLine' => 'Horizontal line',

                    'option_group_6' => 'Structure Fields',
                    'App\\Aura\\Fields\\Repeater' => 'Repeater',
                    'App\\Aura\\Fields\\Tab' => 'Tab',
                    'App\\Aura\\Fields\\Panel' => 'Panel',
                    'App\\Aura\\Fields\\Group' => 'Group',

                    'option_group_7' => 'Relationship Fields',
                    'App\\Aura\\Fields\\BelongsTo' => 'BelongsTo',
                    'App\\Aura\\Fields\\HasMany' => 'HasMany',
                    'App\\Aura\\Fields\\SelectMany' => 'SelectMany',

                    'option_group_8' => 'Special Fields',
                    'App\\Aura\\Fields\\Permissions' => 'Permissions',
                ],
            ],
            [
                'name' => 'instructions',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'instructions',

            ],
            [
                'name' => 'View',
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'tab-2',
                'style' => [],
            ],

            [
                'name' => 'On Index',
                'type' => 'App\\Aura\\Fields\\Boolean',
                'validation' => '',
                'instructions' => 'Show on the index page / table.',
                'slug' => 'on_index',
            ],
            [
                'name' => 'On Forms',
                'type' => 'App\\Aura\\Fields\\Boolean',
                'validation' => '',
                'instructions' => 'Show on the create and edit forms.',
                'slug' => 'on_forms',
            ],
            [
                'name' => 'On View',
                'type' => 'App\\Aura\\Fields\\Boolean',
                'instructions' => 'Show on the view page.',
                'validation' => '',
                'slug' => 'on_view',
            ],

            [
                'name' => 'Width',
                'type' => 'App\\Aura\\Fields\\Number',
                'validation' => '',
                'suffix' => '%',
                'instructions' => 'Width of the field in the form in %.',
                'slug' => 'style.width',
            ],

            [
                'label' => 'Conditional Logic',
                'name' => 'Conditional Logic',
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'conditional_logic',
                'style' => [],
            ],

            [
                'label' => 'Add Condition',
                'name' => 'Add Condition',
                'type' => 'App\\Aura\\Fields\\Repeater',
                'validation' => '',
                'conditional_logic' => [
                ],
                'style' => [
                    'width' => '100',
                ],
                'slug' => 'conditional_logic',
            ],
            [
                'label' => 'Type',
                'name' => 'Type',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'instructions' => 'Slug of the field to check. You can also use "role"',
                'conditional_logic' => [
                ],
                'slug' => 'field',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'label' => 'Operator',
                'name' => 'Operator',
                'type' => 'App\\Aura\\Fields\\Select',
                'validation' => '',
                'options' => [
                    '==' => '==',
                    '!=' => '!=',
                    '>' => '>',
                    '>=' => '>=',
                    '<' => '<',
                    '<=' => '<=',
                ],
                'conditional_logic' => [
                ],
                'slug' => 'operator',
                'style' => [
                    'width' => '33',
                ],
            ],
            [
                'label' => 'Value',
                'name' => 'Value',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'conditional_logic' => [
                ],
                'slug' => 'value',
                'style' => [
                    'width' => '33',
                ],
            ],

        ];
    }

    public function getView(): string
    {
        if (! isset($this->view)) {
            throw new Exception('Class ['.static::class.'] extends ['.ViewComponent::class.'] but does not have a [$view] property defined.');
        }

        return $this->view;
    }

    public function isInputField()
    {
        return in_array($this->type, ['input', 'repeater', 'group']);
    }

    public function isTaxonomyField()
    {
        return $this->taxonomy;
    }

    public function render(): View
    {
        return view(
            $this->getView(),
            array_merge(
                $this->data(),
                isset($this->viewIdentifier) ? [$this->viewIdentifier => $this] : [],
            ),
        );
    }

    public function toHtml(): string
    {
        return $this->render()->render();
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
