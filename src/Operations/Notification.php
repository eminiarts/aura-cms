<?php

namespace Eminiarts\Aura\Operations;

use Eminiarts\Aura\Resources\Operation;
use Eminiarts\Aura\Resources\Role;
use Eminiarts\Aura\Models\User;
use Illuminate\Support\Facades\Blade;

class Notification extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Select Type',
                'type' => 'Eminiarts\\Aura\\Fields\\Select',
                'instructions' => 'Select the type of notification',
                'validation' => '',
                'defer' => false,
                'slug' => 'type',
                'options' => [
                    'user' => 'User',
                    'role' => 'Role',
                ],
            ],
            [
                'name' => 'User ID',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
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
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
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
                'type' => 'Eminiarts\\Aura\\Fields\\Textarea',
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
                'post' => $post,
            ]);
        } else {
            $renderedMessage = $message;
        }

        $type = $operation->options['type'] ?? 'user';

        if ($type == 'role') {
            // dd('Role', $operation->options['role_id']);
            $role = Role::find($operation->options['role_id']);

            // Get all users with this role

            // dd('hier', $role->toArray(), $role->users->toArray());
            $users = $role->users;
            // foreach user send the notification
            foreach ($users as $user) {
                $user->notify(new \App\Notifications\FlowNotification($post, $renderedMessage));
            }
        } else {
            $user = User::find($operation->options['user_id']);
            // Send the notification
            $user->notify(new \App\Notifications\FlowNotification($post, $renderedMessage));
        }

        $operationLog->response = [
            'message' => $renderedMessage,
        ];

        // Update the operation_log
        $operationLog->status = 'success';
        $operationLog->save();
    }
}
