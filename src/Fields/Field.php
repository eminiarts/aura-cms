<?php

namespace Aura\Base\Fields;

use Aura\Base\Contracts\FieldPresentationContract;
use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Contracts\FieldValueContract;
use Aura\Base\Contracts\FieldValueStorage;
use Aura\Base\Schema\FieldColumn;
use Aura\Base\Support\FieldDisplayValue;
use Aura\Base\Traits\InputFields;
use Illuminate\Contracts\Support\Htmlable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Traits\Macroable;
use Illuminate\Support\Traits\Tappable;
use Livewire\Wireable;

abstract class Field implements FieldPresentationContract, FieldValueContract, Wireable
{
    use InputFields;
    use Macroable {
        __call as macroCall;
    }
    use Tappable;

    public $edit = null;

    public $field;

    public bool $group = false;

    public $index = null;

    public bool $on_forms = true;

    public $optionGroup = 'Fields';

    /** @deprecated Return an Htmlable value from display() instead. */
    public bool $rawHtmlDisplay = false;

    public bool $sameLevelGrouping = false;

    public $tableColumnType = 'string';

    public $tableNullable = true;

    public bool $taxonomy = false;

    public string $type = 'input';

    public $view = null;

    public $wrap = false;

    public $wrapper = null;

    /**
     * Preserve calls to the typed displayValue() API without declaring that
     * method on the base class. Older Aura documentation encouraged subclasses
     * to declare displayValue($value, $model); a parent signature would make
     * those classes fail during PHP class loading.
     *
     * @param  array<int, mixed>  $parameters
     */
    public function __call($method, $parameters)
    {
        if ($method === 'displayValue') {
            $second = $parameters[1] ?? null;
            $usesLegacyShape = $second instanceof Model || ($second === null && count($parameters) < 3);
            $field = $usesLegacyShape ? [] : (is_array($second) ? $second : []);
            $model = $usesLegacyShape
                ? $second
                : (($parameters[2] ?? null) instanceof Model ? $parameters[2] : null);
            $context = ($parameters[3] ?? null) instanceof FieldValueContext
                ? $parameters[3]
                : FieldValueContext::Index;

            return $this->presentValue($parameters[0] ?? null, $field, $model, $context);
        }

        return $this->macroCall($method, $parameters);
    }

    /**
     * Describe the Laravel Blueprint column used by generated custom-table migrations.
     *
     * @param  array<string, mixed>  $field
     */
    public function columnDefinition(array $field): FieldColumn
    {
        return new FieldColumn(
            type: $this->tableColumnType,
            nullable: $this->tableNullable,
        );
    }

    public function display($field, $value, $model)
    {

        if (optional($field)['display_view']) {
            return FieldDisplayValue::sanitizedHtml(
                view($field['display_view'], ['row' => $model, 'field' => $field, 'value' => $value])->render(),
            );
        }

        if ($this->index) {
            $componentName = $this->index;

            return new HtmlString(Blade::render(
                '<x-dynamic-component :component="$componentName" :row="$row" :field="$field" :value="$value" />',
                [
                    'componentName' => $componentName,
                    'row' => $model,
                    'field' => $field,
                    'value' => $value,
                ],
            ));
        }

        if ($value === null || $value === '') {
            return $value;
        }

        return FieldDisplayValue::escape($value);
    }

    public function edit()
    {
        if ($this->edit) {
            return $this->edit;
        }
    }

    public function field($field)
    {
        return $this;
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

    public function getFilterValues($model, $field)
    {
        // Default implementation returns an empty array
        // Most field types don't need predefined values for filtering
        return [];
    }

    public function hydrateFromStorage(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueStorage $storage,
        FieldValueContext $context = FieldValueContext::Model,
    ): mixed {
        return $this->get($this, $value, $field);
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

    public function normalizeForStorage(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueStorage $storage,
    ): mixed {
        if (method_exists($this, 'set')) {
            return $this->set($model, $field, $value);
        }

        return $value;
    }

    public function presentValue(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueContext $context = FieldValueContext::Index,
    ): mixed {
        $usesLegacyDisplayValue = method_exists($this, 'displayValue');
        $display = $usesLegacyDisplayValue
            ? $this->invokeLegacyDisplayValue($value, $field, $model, $context)
            : $this->display($field, $value, $model);

        if ($display instanceof Htmlable) {
            return $display;
        }

        $displayMethod = new \ReflectionMethod($this, 'display');

        // The base implementation escapes all plain values and only emits
        // template markup as HtmlString. Custom overrides must explicitly
        // return Htmlable; their plain strings/arrays remain untrusted.
        if (! $usesLegacyDisplayValue && $displayMethod->getDeclaringClass()->getName() === self::class) {
            return $display === null || $display === ''
                ? $display
                : new HtmlString((string) $display);
        }

        return FieldDisplayValue::secure($display);
    }

    /**
     * Field-aware adapter that preserves the historical zero-argument
     * rendersOnIndex() extension point for third-party field subclasses.
     *
     * @param  array<string, mixed>  $field
     */
    public function rendersConfiguredFieldOnIndex(array $field): bool
    {
        return (bool) $this->rendersOnIndex();
    }

    /** @return bool */
    public function rendersOnIndex()
    {
        return $this->index !== null;
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

    /**
     * @param  array<string, mixed>  $field
     */
    protected function invokeLegacyDisplayValue(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueContext $context,
    ): mixed {
        $method = new \ReflectionMethod($this, 'displayValue');

        if ($method->isVariadic() || $method->getNumberOfParameters() >= 4) {
            return $method->invoke($this, $value, $field, $model, $context);
        }

        if ($method->getNumberOfParameters() === 3) {
            return $method->invoke($this, $value, $field, $model);
        }

        if ($method->getNumberOfParameters() === 2) {
            return $method->invoke($this, $value, $model);
        }

        return $method->invoke($this, $value);
    }
}
