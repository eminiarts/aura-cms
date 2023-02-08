<?php

namespace Eminiarts\Aura\Operations;

use Eminiarts\Aura\Resources\Flow;
use Eminiarts\Aura\Resources\Operation;

class TriggerFlow extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Flow ID',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'instructions' => 'Which flow to trigger',
                'validation' => '',
                'slug' => 'flow_id',
            ],

            [
                'name' => 'Pass Response',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
                'instructions' => 'Select the type of notification',
                'validation' => '',
                'defer' => false,
                'slug' => 'response',
                'options' => [
                    'post' => 'Post',
                    'flow' => 'Flow',
                ],
            ],

        ]);
    }

    public function run(Operation $operation, $post, $operationLog)
    {
        //dd('trigger flow', $operation->toArray(), $post->toArray(), $operationLog->toArray());

        // throw an exception if there is no message
        $triggerFlowId = $operation->options['flow_id'] ?? throw new \Exception('No Flow to be triggered');
        $triggerFlow = Flow::where('trigger', 'flow')->find($triggerFlowId);

        // operation with operation_id of the flow
        $operation = Operation::find($triggerFlow->operation_id);

        // Create a Flow Log
        $flowLog = $triggerFlow->logs()->create([
            'post_id' => $post->id,
            'status' => 'running',
            'started_at' => now(),
        ]);

        // Run the Operation
        $operation->run($post, $flowLog->id);

        // Update the operation_log
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
