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

    // listener for newFields
    protected $listeners = ['newFields' => 'newFields'];

    public function activate($params)
    {
        $this->fieldSlug = $params['fieldSlug'];
        $this->field = $params['field'];
        $this->post['fields'] = $this->field;

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
        }

        $this->open = true;
    }

    public function deleteField($slug)
    {
        $this->emit('deleteField', ['slug' => $this->fieldSlug, 'value' => $this->post['fields']]);

        $this->open = false;
    }

    public function render()
    {
        return view('aura::livewire.edit-posttype-field')->layout('aura::components.layout.app');
    }

    public function rules()
    {
        return Arr::dot([
            'post.fields' => app($this->field['type'])->validationRules(),
        ]);
    }

    public function save()
    {
        // Validate
        $this->validate();

        // emit event to parent with slug and value
        $this->emit('saveField', ['slug' => $this->fieldSlug, 'value' => $this->post['fields']]);

        $this->open = false;
    }

    public function updateType()
    {
        // Validate
        // $this->validate();

        // emit event to parent with slug and value
        $this->emit('saveField', ['slug' => $this->fieldSlug, 'value' => $this->post['fields']]);

        // refresh component


        // $this->emit('updatedOperation');
    }

    public function newFields($fields)
    {
        // get the field of $fields with the slug of $this->field['slug']
        $field = collect($fields)->firstWhere('slug', $this->field['slug']);

        // refresh the $this->field array
        $this->field = $field;


        $this->emit('refreshComponent');
    }

    public function getGroupedFieldsProperty()
    {
        return app($this->field['type'])->getGroupedFields();
    }
}
