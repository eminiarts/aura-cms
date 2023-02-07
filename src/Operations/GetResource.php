<?php

namespace App\Aura\Operations;

use App\Aura\Resources\Operation;

class GetResource extends BaseOperation
{
    public function run(Operation $operation, $post, $operationLog)
    {
        // dd('hier', $operation->options['resource_ids'], $operation->options['resource']);

        // throw an exception if there is no message
        $ids = $operation->options['resource_ids'] ?? throw new \Exception('No ID');
        $resource = $operation->options['resource'] ?? throw new \Exception('No Resource');

        // Get the Resource
        $resources = app($resource)::find($ids);

        // Update the operation_log
        $operationLog->response = $resources;
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
