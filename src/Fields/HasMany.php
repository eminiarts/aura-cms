<?php

namespace Eminiarts\Aura\Aura\Fields;

class HasMany extends Field
{
    public string $component = 'fields.hasmany';

    public bool $group = true;

    public string $type = 'relation';

    protected string $view = 'components.fields.hasmany';

    public function queryFor($model, $query)
    {
        if ($model instanceof \App\Aura\Resources\User) {
            return $query->where('user_id', $model->id);
        }

        if ($model instanceof \App\Aura\Resources\Team) {
            return $query;
        }

        if ($model instanceof \App\Aura\Resources\Flow) {
            return $query->where('flow_id', $model->id);
        }

        if ($model instanceof \App\Aura\Resources\Flow) {
            return $query->where('flow_id', $model->id);
        }

        if ($model instanceof \App\Aura\Resources\Operation) {
            return $query->where('operation_id', $model->id);
        }

        if ($model instanceof \App\Aura\Resources\FlowLog) {
            return $query->where('flow_log_id', $model->id);
        }

        return $query->where('user_id', $model->id);
    }
}
