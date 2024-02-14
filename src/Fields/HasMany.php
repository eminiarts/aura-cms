<?php

namespace Aura\Base\Fields;

use Aura\Flows\Resources\Operation;
use Aura\Base\Models\Meta;

class HasMany extends Field
{
    public $component = 'aura::fields.has-many';

    public $view = 'aura::fields.has-many-view';

    public $optionGroup = 'Relationship Fields';

    public bool $group = false;

    public string $type = 'relation';

    // public $view = 'components.fields.hasmany';

    public function relationship($model, $field)
    {
        // If it's a meta field
        if ($model->usesMeta()) {
            return $model->hasManyThrough(
                $field['resource'],
                Meta::class,
                'value',     // Foreign key on the post_meta table
                'id',        // Foreign key on the reviews table
                'id',        // Local key on the products table
                'post_id'    // Local key on the post_meta table
            )->where('post_meta.key', $field['relation']);
        }

        return $model->hasMany($field['resource'], $field['relation']);
    }

    public function get($model, $field)
    {
        // ray($field, $model);

        $relationshipQuery = $this->relationship($model, $field);

        return $relationshipQuery->get();
    }

    public function queryFor($query, $component)
    {

        $field = $component->field;
        $model = $component->model;

        if (optional($component)->parent) {
            $field = $component->parent->fieldBySlug($field['slug']);
            $model = $component->parent;
        }

        // if $field['relation'] is set, check if meta with key $field['relation'] exists, apply whereHas meta to the query

        // if optional($field)['relation'] is closure
        if (is_callable(optional($field)['relation'])) {
            return $field['relation']($query, $model);
        }

        // ray($component->field);

        if (optional($component->field)['relation']) {

            if ($model->id) {
                return $query->whereHas('meta', function ($query) use ($field, $model) {
                    $query->where('key', $field['relation'])
                        ->where('value', $model->id);
                });
            }
        }

        if ($model instanceof \Aura\Base\Resources\User) {
            return $query;
        }

        if ($model instanceof \Aura\Base\Resources\Team) {
            return $query;
        }

        if ($model instanceof \Aura\Flows\Resources\Flow) {
            return $query->where('flow_id', $model->id);
        }

        if ($model instanceof \Aura\Flows\Resources\Flow) {
            return $query->where('flow_id', $model->id);
        }

        if ($model instanceof Operation) {
            return $query->where('operation_id', $model->id);
        }

        if ($model instanceof \Aura\Flows\Resources\FlowLog) {
            return $query->where('flow_log_id', $model->id);
        }

        return $query->where('user_id', $model->id);
    }
}
