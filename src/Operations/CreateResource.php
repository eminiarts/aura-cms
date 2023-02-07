<?php

namespace Eminiarts\Aura\Operations;

use Eminiarts\Aura\Resources\Operation;

class CreateResource extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Resource',
                'type' => 'App\\Aura\\Fields\\Text',
                'instructions' => 'Class of the Resource',
                'validation' => 'required',
                'slug' => 'resource',
            ],
            [
                'name' => 'Data Title',
                'type' => 'App\\Aura\\Fields\\Text',
                'instructions' => 'Title of the Resource',
                'validation' => 'required',
                'slug' => 'data.title',
            ],
            [
                'name' => 'Data Status',
                'type' => 'App\\Aura\\Fields\\Text',
                'instructions' => 'Status of the Resource',
                'validation' => 'required',
                'slug' => 'data.status',
            ],
        ]);
    }

    public function run(Operation $operation, $post, $operationLog)
    {
        // dd('send notification', $operation->toArray(), $post->toArray(), $operationLog->toArray());

        // if $post->type is the same as $operation->options['resource'] then throw expception
        // dd($post->type, $operation->options['resource']);

        if ($operation->flow->options['event'] == 'created' && 'App\\Aura\\Resources\\'.$post->type == $operation->options['resource']) {
            throw new \Exception('Cannot create post of same type');
        }

        // throw an exception if there is no message
        if ($operation->options['data'] == null) {
            throw new \Exception('No Values');
        }
        $values = $operation->options['data'];

        if ($operation->options['resource'] == null) {
            throw new \Exception('No Resource');
        }
        $resource = $operation->options['resource'];

        // Create the Resource with the values
        $resource = app($resource)->create($values);

        // Update the operation_log
        $operationLog->response = $resource;
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
