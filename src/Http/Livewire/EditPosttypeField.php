<?php

namespace Eminiarts\Aura\Http\Livewire;

use Eminiarts\Aura\Traits\RepeaterFields;
use Eminiarts\Aura\Traits\SaveFields;
use Illuminate\Support\Arr;
use Livewire\Component;

class EditPosttypeField extends Component
{
    use RepeaterFields;
    use SaveFields;

    public $field;

    public $fieldSlug;

    public $model;

    public $open = false;

    public $post;

    public $reservedWords = ['id', 'type'];

    // listener for newFields
    protected $listeners = ['newFields' => 'newFields'];

    public function activate($params)
    {
        $this->fieldSlug = $params['fieldSlug'];
        $this->post['fields'] = $params['field'];
        $this->field = $params['field'];

        // Check if field is an input field
        if (app($this->field['type'])->isInputField()) {
            // if $this->post['fields']['on_index'] is not set, set it to true (default)
            if (! isset($this->post['fields']['on_index'])) {
                $this->post['fields']['on_index'] = true;
            }
            // on_forms
            if (! isset($this->post['fields']['on_forms'])) {
                $this->post['fields']['on_forms'] = true;
            }
            // on_view
            if (! isset($this->post['fields']['on_view'])) {
                $this->post['fields']['on_view'] = true;
            }

            // searchable
            if (! isset($this->post['fields']['searchable'])) {
                $this->post['fields']['searchable'] = false;
            }
        }
        $this->updatedField();
        $this->open = true;
    }

    public function deleteField($slug)
    {
        $this->emit('deleteField', ['slug' => $this->fieldSlug, 'value' => $this->post['fields']]);

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
        $this->post['fields'] = $field;
        $this->updatedField();

        // $this->emit('refreshComponent');
    }

    public function render()
    {
        return view('aura::livewire.edit-posttype-field')->layout('aura::components.layout.app');
    }

    public function rules()
    {
        $rules = Arr::dot([
            'post.fields' => app($this->field['type'])->validationRules(),
        ]);

        $rules['post.fields.slug'] = [
            'required',
            function ($attribute, $value, $fail) {
                if (collect($this->post['fields'])->pluck('slug')->duplicates()->values()->contains($value)) {
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
        // remove all NULL values from $this->post['fields']
        $this->post['fields'] = array_filter($this->post['fields'], function ($value) {
            return ! is_null($value);
        });

        $this->validate();

        // emit event to parent with slug and value
        $this->emit('saveField', ['slug' => $this->fieldSlug, 'value' => $this->post['fields']]);

        $this->open = false;
    }

    public function updatedField()
    {
        // if $this->field is undefined, return
        if (! isset($this->field['type'])) {

            return;
        }
        $fields = app($this->field['type'])->inputFields()->pluck('slug');

        // fields are not set on $this->post['fields'] set it to false
        foreach ($fields as $field) {
            if (! isset($this->post['fields'][$field])) {
                $this->post['fields'][$field] = null;
            }
        }
    }

    public function updateType()
    {
        // Validate
        // $this->validate();

        // emit event to parent with slug and value
        $this->emit('saveField', ['slug' => $this->fieldSlug, 'value' => $this->post['fields']]);
    }
}
