<?php

namespace Aura\Base\Operations;

use Aura\Flows\Resources\Operation;

class DeleteResource extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Select Type',
                'type' => 'Aura\\Base\\Fields\\Select',
                'instructions' => 'Select the type of delete',
                'validation' => '',
                'defer' => false,
                'slug' => 'type',
                'options' => [
                    'input' => 'Input',
                    'custom' => 'Custom',
                ],
            ],
            [
                'name' => 'User ID',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Which user to send the notification to',
                'validation' => 'required',
                'conditional_logic' => [
                    [
                        'field' => 'options.type',
                        'operator' => '==',
                        'value' => 'custom',
                    ],
                ],
                'slug' => 'resource',
            ],
            [
                'name' => 'Role',
                'type' => 'Aura\\Base\\Fields\\Text',
                'instructions' => 'Which role to send the notification to',
                'conditional_logic' => [
                    [
                        'field' => 'options.type',
                        'operator' => '==',
                        'value' => 'custom',
                    ],
                ],
                'validation' => 'required',
                'slug' => 'resource_ids',
            ],
        ]);
    }

    public function run(Operation $operation, $post, $operationLog)
    {

        // dd($operation->flow->trigger == 'resource' && get_class($post) == $operation->flow->options['resource'], get_class($post));
        // get the resource type of the post and the resource type of the flow trigger
        // throw an exception if the resource type of the post is the same as the resource type of the flow trigger
        // if ($operation->flow->trigger == 'resource' && get_class($post) == $operation->flow->options['resource']) {
        //     throw new \Exception('Cannot delete post of same type');
        // }

        // dd('bish ier');

        if (isset($operation->flow->options['event']) && $operation->flow->options['event'] == 'deleted' && get_class($post) == $operation->options['resource']) {
            throw new \Exception('Cannot delete post of same type');
        }

        // throw an exception if there are no ids
        if ($operation->options['type'] == 'custom') {
            if ($operation->options['resource_ids'] == null) {
                throw new \Exception('No Resource Ids');
            }
            $ids = $operation->options['resource_ids'];

            if ($operation->options['resource'] == null) {
                throw new \Exception('No Resource');
            }
            $resource = $operation->options['resource'] ?? throw new \Exception('No Resource');
        } else {
            $ids = [$post->id];
            $resource = get_class($post);
        }

        // Get the Resource
        $resources = app($resource)->find($ids);

        // delete the Resources
        $resources->each(function ($r) {
            $r->delete();
        });

        // dd('hier', $resources);
        // Update the operation_log
        $operationLog->response = $resources;
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
