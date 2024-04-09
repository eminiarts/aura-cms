<?php

namespace Aura\Base\Operations;

use Aura\Base\Models\User;
use Aura\Base\Notifications\FlowNotification;
use Aura\Base\Resources\Role;
use Aura\Flows\Resources\Operation;
use Illuminate\Support\Facades\Blade;

class Notification extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Select Type',
                'type' => 'Aura\\Base\\Fields\\Select',
                'instructions' => 'Select the type of notification',
                'validation' => '',
                'live' => true,
                'slug' => 'type',
                'options' => [
                    'user' => 'User',
                    'role' => 'Role',
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

        // throw an exception if there is no message
        $message = $operation->options['message'] ?? throw new \Exception('No Message');

        $message = $this->validateString($message);

        // if message contains "{{" and "}}" then it is a blade template
        if (strpos($message, '{{') !== false && strpos($message, '}}') !== false) {
            $renderedMessage = Blade::render($message, [
                'resource' => $post,
            ]);
        } else {
            $renderedMessage = $message;
        }

        $type = $operation->options['type'] ?? 'user';

        if ($type == 'role') {
            // dd('Role', $operation->options['role_id']);
            $role = Role::find($operation->options['role_id']);

            // Get all users with this role

            $users = $role->users;
            // foreach user send the notification
            foreach ($users as $user) {
                $user->notify(new FlowNotification($post, $renderedMessage));
            }
        } else {
            $user = User::find($operation->options['user_id']);
            // Send the notification
            $user->notify(new FlowNotification($post, $renderedMessage));
        }

        $operationLog->response = [
            'message' => $renderedMessage,
        ];

        // Update the operation_log
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
