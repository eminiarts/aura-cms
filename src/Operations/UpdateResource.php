<?php

namespace Aura\Base\Operations;

use Aura\Base\Resources\Post;
use Aura\Flows\Resources\Operation;

class UpdateResource extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Select Type',
                'type' => 'Aura\\Base\\Fields\\Select',
                'instructions' => 'Select which type of resource to update',
                'validation' => '',
                'live' => true,
                'slug' => 'type',
                'options' => [
                    'input' => 'Input Data',
                    'custom' => 'Custom',
                ],
            ],
            [
                'name' => 'User ID',
                'type' => 'Aura\\Base\\Fields\\Text',
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
                'type' => 'Aura\\Base\\Fields\\Text',
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
                'type' => 'Aura\\Base\\Fields\\Textarea',
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

        // if ($operation->flow->options['event'] == 'updated' && get_class($post) == $operation->options['resource']) {
        //     throw new \Exception('Cannot update post of same type');
        // }

        if ($operation->options['type'] != Post::class) {
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
            $resource = get_class($post);
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

        // Get the Resource
        $resources = app($resource)->find($ids);

        // Update the Resource
        $resources->each(function ($resource) use ($values) {
            // update the resource silently

            $resource->updateQuietly($values);
        });

        // Update the operation_log
        $operationLog->response = $resources;
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
