<?php

namespace Eminiarts\Aura\Operations;

use Aura\Flows\Resources\Operation;

class Mail extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Subject',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'instructions' => 'Subject of the Mail',
                'validation' => 'required',
                'slug' => 'subject',
            ],
            [
                'name' => 'To',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'instructions' => 'Recipient of the Mail',
                'validation' => 'required|email',
                'slug' => 'to',
            ],
            [
                'name' => 'Body',
                'type' => 'Eminiarts\\Aura\\Fields\\Textarea',
                'instructions' => 'Body of the Mail',
                'validation' => 'required',
                'slug' => 'body',
            ],
        ]);
    }

    public function run(Operation $operation, $post, $operationLog)
    {
        // throw an exception if there is no message
        $message = $operation->options['message'] ?? throw new \Exception('No Message');

        $operationLog->status = 'success';
        $operationLog->save();

        // Send the Mail with Laravel Mailer
        // dd($operation);
    }
}
