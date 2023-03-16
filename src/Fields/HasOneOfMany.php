<?php

namespace Eminiarts\Aura\Fields;

class HasOneOfMany extends Field
{
    public $component = 'aura::fields.hasOneOfMany';

    public bool $group = true;

    public string $type = 'relation';

    // public $view = 'components.fields.hasmany';

    public function queryFor($model, $query)
    {
        if ($model instanceof \Eminiarts\Aura\Resources\User) {
            return $query->where('user_id', $model->id);
        }

        if ($model instanceof \Eminiarts\Aura\Resources\Team) {
            return $query;
        }

        if ($model instanceof \Eminiarts\Aura\Resources\Flow) {
            return $query->where('flow_id', $model->id);
        }

        if ($model instanceof \Eminiarts\Aura\Resources\Flow) {
            return $query->where('flow_id', $model->id);
        }

        if ($model instanceof \Eminiarts\Aura\Resources\Operation) {
            return $query->where('operation_id', $model->id);
        }

        if ($model instanceof \Eminiarts\Aura\Resources\FlowLog) {
            return $query->where('flow_log_id', $model->id);
        }

        return $query->where('user_id', $model->id);
    }
}
