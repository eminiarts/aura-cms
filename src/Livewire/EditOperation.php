<?php

namespace Aura\Base\Livewire;

use Aura\Base\Traits\RepeaterFields;
use Aura\Flows\Resources\Operation;
use Illuminate\Support\Arr;
use Livewire\Component;

class EditOperation extends Component
{
    use RepeaterFields;

    public $model;

    public $open = false;

    public $resource;

    public function activate($params)
    {
        $this->model = Operation::find($params['model']);

        $this->form['fields'] = $this->model->fields;

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
            'form.fields' => $this->model->validationRules(),
        ]);
    }

    public function save()
    {
        // Validate
        $this->validate();

        $this->model->update($this->form['fields']);

        $this->open = false;

        $this->dispatch('updatedOperation');

        $this->notify('Operation saved');
    }

    public function updateType()
    {
        // when type is changed, update the fields
        $this->model->update(['type' => $this->form['fields']['type']]);

        $this->dispatch('updatedOperation');
    }

    public function validateBeforeClosing()
    {
        $this->validate();

        $this->open = false;
    }
}
