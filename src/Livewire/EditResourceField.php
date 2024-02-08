<?php

namespace Eminiarts\Aura\Livewire;

use Eminiarts\Aura\Traits\RepeaterFields;
use Eminiarts\Aura\Traits\SaveFields;
use Illuminate\Support\Arr;
use Livewire\Component;

class EditResourceField extends Component
{
    use RepeaterFields;
    use SaveFields;

    public $field;

    public $fieldSlug;

    public $model;

    public $open = false;

    public $resource;

    public $reservedWords = ['id', 'type'];

    // listener for newFields
    protected $listeners = ['newFields' => 'newFields'];

    public function activate($params)
    {
        $this->fieldSlug = $params['fieldSlug'];
        $this->resource['fields'] = $params['field'];
        $this->field = $params['field'];

        // Check if field is an input field
        if (app($this->field['type'])->isInputField()) {
            // if $this->resource['fields']['on_index'] is not set, set it to true (default)
            if (! isset($this->resource['fields']['on_index'])) {
                $this->resource['fields']['on_index'] = true;
            }
            // on_forms
            if (! isset($this->resource['fields']['on_forms'])) {
                $this->resource['fields']['on_forms'] = true;
            }
            // on_view
            if (! isset($this->resource['fields']['on_view'])) {
                $this->resource['fields']['on_view'] = true;
            }

            // searchable
            if (! isset($this->resource['fields']['searchable'])) {
                $this->resource['fields']['searchable'] = false;
            }
        }
        $this->updatedField();
        $this->open = true;
    }

    public function deleteField($slug)
    {
        $this->dispatch('deleteField', ['slug' => $this->fieldSlug, 'value' => $this->resource['fields']]);

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
        $this->resource['fields'] = $field;
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
            'resource.fields' => app($this->field['type'])->validationRules(),
        ]);

        $rules['resource.fields.slug'] = [
            'required',
            function ($attribute, $value, $fail) {
                if (collect($this->resource['fields'])->pluck('slug')->duplicates()->values()->contains($value)) {
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
        // remove all NULL values from $this->resource['fields']
        $this->resource['fields'] = array_filter($this->resource['fields'], function ($value) {
            return ! is_null($value);
        });

        $this->validate();

        // emit event to parent with slug and value
        $this->dispatch('saveField', ['slug' => $this->fieldSlug, 'value' => $this->resource['fields']]);

        $this->open = false;
    }

    public function updatedField()
    {
        // if $this->field is undefined, return
        if (! isset($this->field['type'])) {

            return;
        }
        $fields = app($this->field['type'])->inputFields()->pluck('slug');

        // fields are not set on $this->resource['fields'] set it to false
        foreach ($fields as $field) {
            if (! isset($this->resource['fields'][$field])) {
                $this->resource['fields'][$field] = null;
            }
        }
    }

    public function updateType()
    {
        // Validate
        // $this->validate();

        // emit event to parent with slug and value
        $this->dispatch('saveField', ['slug' => $this->fieldSlug, 'value' => $this->resource['fields']]);
    }
}
