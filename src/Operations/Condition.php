<?php

namespace Aura\Base\Operations;

use Aura\Flows\Resources\Operation;

class Condition extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Condition',
                'type' => 'Aura\\Base\\Fields\\Code',
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
