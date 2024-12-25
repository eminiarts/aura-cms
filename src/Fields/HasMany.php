<?php

namespace Aura\Base\Fields;

use Aura\Base\Models\Meta;
use Aura\Flows\Resources\Operation;

class HasMany extends Field
{
    public $edit = 'aura::fields.has-many';

    public bool $group = false;

    public $optionGroup = 'Relationship Fields';

    public string $type = 'relation';

    public $view = 'aura::fields.has-many';

    public function get($class, $value, $field = null)
    {
        $relationshipQuery = $this->relationship($class, $value);

        return $relationshipQuery->get();
    }

    public function getRelation($model, $field)
    {
        if (! $model->exists) {
            return collect();
        }

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

        if (isset($component->field['resource'])) {
            $relationship = $this->relationship($model, $field);

            return $relationship->getQuery();
        }

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

    public function relationship($model, $field)
    {
        if (isset($field['column'])) {
            return $model->hasMany($field['resource'], $field['column']);
        }
    
        if ($field['reverse'] ?? false) {
            return $model
                ->morphedByMany($field['resource'], 'related', 'post_relations', 'resource_id', 'related_id')
                ->withTimestamps()
                ->withPivot('related_type')
                ->wherePivot('related_type', $field['resource']);
        }
    
        return $model
            ->morphedByMany($field['resource'], 'resource', 'post_relations', 'related_id', 'resource_id')
            ->withTimestamps()
            ->withPivot('resource_type')
            ->wherePivot('resource_type', $field['resource']);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Has Many',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'has_many',
            ],
            // If table settings are modified, you can set the create_link to the foreign key
            // eg: /admin/resources/create?actor=437" -> foreign_key=actor
            [
                'name' => 'Foreign Key',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'foreign_key',
            ],
            [
                'name' => 'Resource',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'resource',
            ],
        ]);
    }
}
