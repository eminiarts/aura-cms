<?php

namespace App\Aura\Operations;

use App\Aura\Resources\Operation;

class Condition extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Condition',
                'type' => 'App\\Aura\\Fields\\Code',
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
