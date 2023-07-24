<?php

namespace Eminiarts\Aura\Livewire;

use Aura\Flows\Resources\Operation;
use Eminiarts\Aura\Traits\RepeaterFields;
use Illuminate\Support\Arr;
use Livewire\Component;

class EditOperation extends Component
{
    use RepeaterFields;

    public $model;

    public $open = false;

    public $post;

    public function activate($params)
    {
        $this->model = Operation::find($params['model']);

        // dd($this->model->validationRules());

        $this->post['fields'] = $this->model->fields;

        // watch open property and trigger function on change
        // $this->watch('open', 'validateBeforeClosing');

        // dd($this->post['fields']);

        // Merge fields from type with fields from model
        // $this->post['fields'] = array_merge($this->post['fields'], $this->model->type->fields);

        $this->open = true;
    }

    public function deleteOperation($id)
    {
        $this->model = Operation::find($id);

        $this->model->delete();

        $this->open = false;

        $this->dispatch('refreshComponent');
    }

    public function getGroupedFieldsProperty()
    {
        // Fields from the model
        $modelFields = $this->model->getFields();

        // Specific fields for the operation type
        $operationFields = app($this->model->type)->getFields();

        // Merge the two
        $fields = array_merge($modelFields, $operationFields);

        return $this->model->getGroupedFields($fields);
    }

    public function render()
    {
        return view('aura::livewire.edit-operation');
    }

    public function rules()
    {
        return Arr::dot([
            'post.fields' => $this->model->validationRules(),
        ]);
    }

    public function save()
    {
        // dd($this->rules(), $this->post);
        // Validate

        $this->validate();

        // dd($this->post, $this->model);
        $this->model->update($this->post['fields']);

        // emit event to parent with slug and value
        // $this->dispatch('saveField', ['slug' => $this->post['key'], 'value' => $this->post['fields']]);

        // emit to parent, that operation has been updated

        $this->open = false;

        $this->dispatch('updatedOperation');

        $this->notify('Operation saved');
    }

    public function updateType()
    {
        // when type is changed, update the fields
        $this->model->update(['type' => $this->post['fields']['type']]);

        $this->dispatch('updatedOperation');
    }

    // public function updatingOpen($value)
    // {
    //     dd('updatingOpen', $value);
    // }

    public function validateBeforeClosing()
    {
        // dd('validateBeforeClosing', $value);
        $this->validate();

        $this->open = false;
    }
}
