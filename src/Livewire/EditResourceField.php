<?php

namespace Aura\Base\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Traits\RepeaterFields;
use Aura\Base\Traits\SaveFields;
use Illuminate\Support\Arr;
use Livewire\Component;

class EditResourceField extends Component
{
    use RepeaterFields;
    use SaveFields;

    public $field;

    public $fieldSlug;

    public $form;

    public $mode = 'edit';

    public $model;

    public $newFieldIndex = null;

    public $newFieldSlug = null;

    public $open = false;

    public $reservedWords = ['id', 'type'];

    // listener for newFields
    protected $listeners = ['newFields' => 'newFields'];

    public function activate($params)
    {
        if (optional($params)['create']) {
            $this->field = [
                'type' => $params['type'] ?? 'Aura\Base\Fields\Text',
                'slug' => '',
                'name' => '',
                'on_index' => true,
                'on_forms' => true,
                'on_view' => true,
                'searchable' => false,
                'validation' => '',
                'conditional_logic' => '',
            ];

            ray('newFieldIndex', $params['id'], $params['children'])->blue();

            $this->newFieldIndex = $params['id'] + $params['children'];

            $this->model = Aura::findResourceBySlug($params['model']);

            $this->form['fields'] = $this->field;

            $this->updatedField();

            $this->open = true;

            $this->mode = 'create';

            return;
        }

        $this->mode = 'edit';

        $this->fieldSlug = $params['fieldSlug'];
        $this->form['fields'] = $params['field'];

        $this->model = Aura::findResourceBySlug($params['model']);

        $this->field = $params['field'];

        // Check if field is an input field
        if (app($this->field['type'])->isInputField()) {
            if (! isset($this->form['fields']['on_index'])) {
                $this->form['fields']['on_index'] = true;
            }
            if (! isset($this->form['fields']['on_forms'])) {
                $this->form['fields']['on_forms'] = true;
            }
            if (! isset($this->form['fields']['on_view'])) {
                $this->form['fields']['on_view'] = true;
            }
            if (! isset($this->form['fields']['searchable'])) {
                $this->form['fields']['searchable'] = false;
            }
        }
        $this->updatedField();
        $this->open = true;
    }

    public function deleteField($slug)
    {
        $this->dispatch('deleteField', ['slug' => $this->fieldSlug, 'value' => $this->form['fields']]);

        $this->open = false;
    }

    public function getGroupedFieldsProperty()
    {
        return app($this->field['type'])->getGroupedFields();
    }

    public function newFields($fields)
    {
        $field = collect($fields)->firstWhere('slug', $this->field['slug']);

        if (! $field) {
            return;
        }

        foreach ($field as $key => $value) {
            if (is_null($value)) {
                $field[$key] = false;
            }
        }

        $this->field = $field;
        $this->form['fields'] = $field;
        $this->updatedField();

        // $this->dispatch('refreshComponent');
    }

    public function render()
    {
        return view('aura::livewire.edit-resource-field')->layout('aura::components.layout.app');
    }

    public function rules()
    {
        $rules = Arr::dot([
            'form.fields' => app($this->field['type'])->validationRules(),
        ]);

        $rules['form.fields.slug'] = [
            'required',
            'regex:/^[a-zA-Z0-9][a-zA-Z0-9_-]*$/',
            'not_regex:/^[0-9]+$/',
            function ($attribute, $value, $fail) {

                // Check if a field with the same slug already exists in mappedFields
                $existingFields = $this->model->mappedFields();
                $slugExists = collect($existingFields)->pluck('slug')->contains($value);

                if ($slugExists && $value !== $this->field['slug']) {
                    $fail("A field with the slug '{$value}' already exists.");

                    return;
                }

                return false;

                if (collect($this->form['fields'])->pluck('slug')->duplicates()->values()->contains($value)) {
                    $fail('The '.$attribute.' can not be used twice.');
                }

                // check if slug is a reserved word with "in_array"
                if (in_array($value, $this->reservedWords)) {
                    $fail('The '.$attribute.' can not be a reserved word.');
                }
            },
        ];

        return $rules;
    }

    public function save()
    {
        // Validate
        // remove all NULL values from $this->form['fields']
        $this->form['fields'] = array_filter($this->form['fields'], function ($value) {
            return ! is_null($value);
        });

        $this->validate();

        if ($this->mode == 'create') {
            // ray('saveNewField', $this->form['fields'], $this->newFieldIndex, $this->newFieldSlug)->blue();
            ray('saveNewField', $this->form['fields'], $this->newFieldIndex)->blue();
            $this->dispatch('saveNewField', $this->form['fields'], $this->newFieldIndex);
        } else {
            // emit event to parent with slug and value
            $this->dispatch('saveField', ['slug' => $this->fieldSlug, 'value' => $this->form['fields']]);
        }

        $this->dispatch('finishedSavingFields');
        $this->dispatch('refresh-resource-editor');

        $this->open = false;
    }

    public function updated($property)
    {
        // $property: The name of the current property that was updated
        // ray('updated', $property)->orange();

        if ($property === 'form.fields.type') {
            // $this->username = strtolower($this->username);
            $this->updateType();
        }
    }

    public function updatedField()
    {
        // if $this->field is undefined, return
        if (! isset($this->field['type'])) {
            return;
        }

        $fields = app($this->field['type'])->inputFields()->pluck('slug');

        // fields are not set on $this->form['fields'] set it to false
        foreach ($fields as $field) {
            if (! isset($this->form['fields'][$field])) {
                $this->form['fields'][$field] = null;
            }
        }
    }

    public function updateType()
    {
        // Validate
        // $this->validate();

        // emit event to parent with slug and value
        $this->dispatch('saveField', ['slug' => $this->fieldSlug, 'value' => $this->form['fields']]);
    }
}
