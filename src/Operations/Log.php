<?php

namespace Eminiarts\Aura\Operations;

use Aura\Flows\Resources\Operation;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Log as LaravelLog;

class Log extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Message',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'instructions' => 'Message that will be logged in the laravel log',
                'validation' => 'required',
                'slug' => 'message',
            ],
        ]);
    }

    public function run(Operation $operation, $post, $operationLog, $data = null)
    {
        if ($data) {
            $post = $data;
        }
        // dd('hier', $operation->options['message']);
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

        $operationLog->response = [
            'message' => $renderedMessage,
        ];
        $operationLog->save();

        LaravelLog::info($renderedMessage);

        return $post;
    }
}
