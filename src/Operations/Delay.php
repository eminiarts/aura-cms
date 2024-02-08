<?php

namespace Aura\Base\Operations;

use Aura\Flows\Resources\Operation;

class Delay extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Delay',
                'type' => 'Aura\\Base\\Fields\\Number',
                'instructions' => 'Delay in seconds',
                'validation' => 'required|numeric',
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
