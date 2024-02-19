<?php

namespace Aura\Base\Livewire;

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

    public $open = false;

    public $reservedWords = ['id', 'type'];

    // listener for newFields
    protected $listeners = ['newFields' => 'newFields'];

    public function activate($params)
    {
        $this->fieldSlug = $params['fieldSlug'];
        $this->form['fields'] = $params['field'];
        $this->field = $params['field'];

        // Check if field is an input field
        if (app($this->field['type'])->isInputField()) {
            // if $this->form['fields']['on_index'] is not set, set it to true (default)
            if (! isset($this->form['fields']['on_index'])) {
                $this->form['fields']['on_index'] = true;
            }
            // on_forms
            if (! isset($this->form['fields']['on_forms'])) {
                $this->form['fields']['on_forms'] = true;
            }
            // on_view
            if (! isset($this->form['fields']['on_view'])) {
                $this->form['fields']['on_view'] = true;
            }

            // searchable
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
            function ($attribute, $value, $fail) {
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

        // dd($this->form, ['slug' => $this->fieldSlug, 'value' => $this->form['fields']]);

        // emit event to parent with slug and value
        $this->dispatch('saveField', ['slug' => $this->fieldSlug, 'value' => $this->form['fields']]);

        $this->open = false;
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
