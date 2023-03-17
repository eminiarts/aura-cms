<?php

namespace Eminiarts\Aura\Fields;

class HasMany extends Field
{
    public $component = 'aura::fields.has-many';

    public bool $group = false;

    public string $type = 'relation';

    // public $view = 'components.fields.hasmany';

    public function queryFor($model, $query, $field)
    {
        // ray('hier', $model, $query, $field);

        // if $field['relation'] is set, check if meta with key $field['relation'] exists, apply whereHas meta to the query

        if (optional($field)['relation']) {
            return $query->whereHas('meta', function ($query) use ($field) {
                $query->where('key', $field['relation']);
            });
        }

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
