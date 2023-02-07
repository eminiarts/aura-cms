<?php

namespace Eminiarts\Aura\Aura\Operations;

use Eminiarts\Aura\Resources\Operation;

class Delay extends BaseOperation
{
    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Delay',
                'type' => 'App\\Aura\\Fields\\Number',
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
