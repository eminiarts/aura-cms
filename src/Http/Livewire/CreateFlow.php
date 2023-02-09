<?php

namespace Eminiarts\Aura\Http\Livewire;

use Eminiarts\Aura\Resources\Flow;
use Eminiarts\Aura\Resources\Operation;
use Livewire\Component;

class CreateFlow extends Component
{
    public Flow $flow;

    public $model;

    public $name;

    public $operations;

    public $options;

    public $status;

    public $trigger;

    protected $listeners = ['refreshComponent' => '$refresh', 'updatedOperation' => 'refresh'];

    protected $rules = [
        'flow' => '',
        'flow.id' => '',
        'flow.name' => '',
        'flow.operation_id' => '',
        'flow.trigger' => '',
        'flow.status' => '',
        'flow.options' => '',
        'flow.data' => '',
        'operations.*.id' => '',
        'operations.*.name' => '',
        'operations.*.status' => '',
        'operations.*.user_id' => '',
        'operations.*.type' => '',
        'operations.*.flow_id' => '',
        'operations.*.created_at' => '',
        'operations.*.options' => '',
        'operations.*.updated_at' => '',
        'operations.*.deleted_at' => '',
        'operations.*.reject_id' => '',
        'operations.*.resolve_id' => '',
    ];

    public function addOperation($type, $operationId)
    {
        // dd('hi');
        $o = Operation::find($operationId);

        $x = 12;
        $y = 0;
        if ($type == 'reject') {
            $y = 14;
        }
        // code...
        $operation = $this->flow->operations()->create([
            'name' => 'New Operation',
            'status' => 'active',
            'user_id' => auth()->id(),
            'type' => 'Eminiarts\\Aura\\Operations\\Log',
            'options' => [
                'x' => $o['options']['x'] + $x,
                'y' => $o['options']['y'] + $y,
            ],
        ]);

        $this->connectOperation($type, $operationId, $operation->id);

        // Refresh the Component
        $this->emit('refreshComponent');
        $this->operations = $this->flow->fresh()->operations;
    }

    public function addOperationFlow()
    {
        // dd('hi');
        // Create a new operation
        $operation = $this->flow->operations()->create([
            'name' => 'New Operation',
            'status' => 'active',
            'user_id' => auth()->id(),
            'type' => 'Eminiarts\\Aura\\Operations\\Log',
            'options' => [
                'x' => 2 + 12,
                'y' => 2 + 0,
            ],
        ]);

        // Assign the new operation to the flow
        $this->flow->operation_id = $operation->id;
        $this->flow->save();

        // Refresh the flow's operations
        $this->operations = $this->flow->fresh()->operations;

        // Refresh the UI
        $this->emit('refreshComponent');
    }

    public function connectFlow($targetId)
    {
        // dd('hi');
        $this->flow->update([
            'operation_id' => $targetId,
        ]);

        $this->operations = $this->flow->fresh()->operations;
    }

    public function connectOperation($type, $operationId, $targetId)
    {
        // dd('hi');
        if (! $operationId) {
            return;
        }
        // if $targetId is null, then we are removing the connection
        if (! $targetId) {
            $operation = Operation::find($operationId);
            // dd($operation->resolve_id);
            //$operation['resolve_id'] = null;

            // unset resolve_id of the operation
            if ($type == 'resolve') {
                $operation->update(['resolve_id' => null]);
            } else {
                $operation->update(['reject_id' => null]);
            }

            $operation->name = 'Resolve should be deleted';
            $operation->save();
            // dd('removed', $operation->toArray());
            $this->operations = $this->flow->fresh()->operations;

            return;
        }

        // make sure, that the connection does not create a loop
        if ($this->hasConnectionLoop($operationId, $targetId, $type)) {
            // return an error or throw an exception
            return;
        }

        $operation = Operation::find($operationId);
        $operation->{$type.'_id'} = $targetId;
        $operation->save();

        // Refresh the Component
        $this->operations = $this->flow->fresh()->operations;
    }

    public function createOperation()
    {
        // dd('hi');
        $operation = $this->flow->operations()->create([
            'name' => 'New Operation',
            'status' => 'active',
            'user_id' => auth()->id(),
        ]);

        $this->operations->push($operation);
    }

    public function mount($model)
    {
        $this->flow = $model;
        $this->model = $model;

        $this->operations = $this->flow->operations;

        // catch event operationUpdated and refresh the component
        // $this->on('operationUpdated', function ($operation) {
        //     $this->operations = $this->flow->fresh()->operations;
        // });
    }

    public function refresh()
    {
        $this->operations = $this->flow->fresh()->operations;
        $this->emit('refreshComponent');
    }

    public function render()
    {
        return view('aura::livewire.create-flow');
    }

    public function saveOperation($o)
    {
        // dd('hi');
        // Save only the options of Operation
        $operation = Operation::find($o['id']);
        $operation->options = $o['options'];
        $operation->save();
    }

    public function selectOperation($operationId)
    {
        // dd('hi');
        $this->emit('openSlideOver', 'edit-operation', ['model' => $operationId]);
    }

    private function hasConnectionLoop($operationId, $targetId)
    {
        $currentOperation = Operation::find($operationId);
        $targetOperation = Operation::find($targetId);

        // if the target operation is the current operation, then we have a loop
        if ($currentOperation->id == $targetOperation->id) {
            return true;
        }

        // if the target operation has a connection to the current operation, then we have a loop
        if ($targetOperation->resolve_id == $currentOperation->id || $targetOperation->reject_id == $currentOperation->id) {
            return true;
        }

        // recursion: check the next operation in the chain
        if ($targetOperation->resolve_id) {
            return $this->hasConnectionLoop($operationId, $targetOperation->resolve_id);
        }

        if ($targetOperation->reject_id) {
            return $this->hasConnectionLoop($operationId, $targetOperation->reject_id);
        }

        // if we reach the end of the chain, there is no loop
        return false;
    }
}
