<?php

namespace Eminiarts\Aura\Operations;

use Eminiarts\Aura\Resources\Operation;

class Condition extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Condition',
                'type' => 'Eminiarts\\Aura\\Fields\\Code',
                'instructions' => 'Condition to evaluate',
                'validation' => 'required',
                'slug' => 'delay',
            ],
        ]);
    }

    public function run(Operation $operation, $post, $operationLog)
    {
        // Delay successfull
        $operationLog->status = 'success';
        $operationLog->save();
    }
}