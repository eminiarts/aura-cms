<?php

namespace Eminiarts\Aura\Operations;

use Eminiarts\Aura\Resources\Operation;

class UpdateResource extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Select Type',
                'type' => 'App\\Aura\\Fields\\Select',
                'instructions' => 'Select which type of resource to update',
                'validation' => '',
                'defer' => false,
                'slug' => 'type',
                'options' => [
                    'input' => 'Input Data',
                    'custom' => 'Custom',
                ],
            ],
            [
                'name' => 'User ID',
                'type' => 'App\\Aura\\Fields\\Text',
                'instructions' => 'Which user to send the notification to',
                'validation' => '',
                'conditional_logic' => [
                    [
                        'field' => 'options.type',
                        'operator' => '==',
                        'value' => 'user',
                    ],
                ],
                'slug' => 'user_id',
            ],
            [
                'name' => 'Role',
                'type' => 'App\\Aura\\Fields\\Text',
                'instructions' => 'Which role to send the notification to',
                'conditional_logic' => [
                    [
                        'field' => 'options.type',
                        'operator' => '==',
                        'value' => 'role',
                    ],
                ],
                'validation' => '',
                'slug' => 'role_id',
            ],
            [
                'name' => 'Message',
                'type' => 'App\\Aura\\Fields\\Textarea',
                'instructions' => 'Message of the notifictation',
                'validation' => 'required',
                'slug' => 'message',
            ],
        ]);
    }

    public function run(Operation $operation, $post, $operationLog)
    {
        // dd('send notification', $operation->toArray(), $post->toArray(), $operationLog->toArray());
        // dump('update resource', $operation->options);
        if ($operation->flow->options['event'] == 'updated' && 'App\\Aura\\Resources\\'.$post->type == $operation->options['resource']) {
            throw new \Exception('Cannot update post of same type');
        }

        if ($operation->options['type'] != 'Post') {
            // throw an exception if there is no message
            if ($operation->options['resource_ids'] == null) {
                throw new \Exception('No Resource Ids');
            }
            $ids = $operation->options['resource_ids'];

            if ($operation->options['resource'] == null) {
                throw new \Exception('No Resource');
            }
            $resource = $operation->options['resource'];
        } else {
            $ids = [$post->id];
            $resource = 'App\\Aura\\Resources\\'.$post->type;
        }

        if ($operation->options['data'] == null) {
            throw new \Exception('No Values');
        }
        $values = $operation->options['data'];

        if (optional($operation->options)['resource_source'] != null) {
            $o = Operation::find($operation->options['resource_source'])->logs()->latest()->first()->response;
            $decoded = $o;
            // get the IDs from the response
            $ids = [];
            foreach ($decoded as $d) {
                $ids[] = $d['id'];
            }
        }

        // dd('hier?');

        // dump('hallo?', $resource);
        // Get the Resource
        $resources = app($resource)->find($ids);

        // dd($resources);

        // Update the Resource
        $resources->each(function ($resource) use ($values) {
            $resource->update($values);
        });

        // Update the operation_log
        $operationLog->response = $resources;
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
