<?php

namespace Eminiarts\Aura\Operations;

use Aura\Flows\Resources\Operation;

class CreateResource extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Resource',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'instructions' => 'Class of the Resource',
                'validation' => 'required',
                'slug' => 'resource',
            ],
            [
                'name' => 'Data Title',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'instructions' => 'Title of the Resource',
                'validation' => 'required',
                'slug' => 'data.title',
            ],
            [
                'name' => 'Data Status',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'instructions' => 'Status of the Resource',
                'validation' => 'required',
                'slug' => 'data.status',
            ],
        ]);
    }

    public function run(Operation $operation, $post, $operationLog)
    {
        // dd('send notification', $operation->toArray(), $post->toArray(), $operationLog->toArray());

        ray(Operation::find($operation->id)->toArray(), $operation->options['resource']);
        ray('this should run', 'Eminiarts\\Aura\\Resources\\'.$post->type, $operation->options['resource'], $operation->options);

        // if $post->type is the same as $operation->options['resource'] then throw expception
        // dd($post->type, $operation->options['resource']);

        if ($operation->flow->options['event'] == 'created' && 'Eminiarts\\Aura\\Resources\\'.$post->type == $operation->options['resource']) {
            throw new \Exception('Cannot create post of same type');
        }




        dd('hier running');


        // throw an exception if there is no message
        if ($operation->options['data'] == null) {
            throw new \Exception('No Values');
        }
        $values = $operation->options['data'];

        if ($operation->options['resource'] == null) {
            throw new \Exception('No Resource');
        }
        $resource = $operation->options['resource'];


        ray('this should run', $operation, $post);


        dd('hier running');


        // Create the Resource with the values
        $resource = app($resource)->create($values);

        // Update the operation_log
        $operationLog->response = $resource;
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
