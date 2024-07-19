<?php

namespace Aura\Base\Fields;

class AdvancedSelect extends Field
{
    public $component = 'aura::fields.advanced-select';

    public $optionGroup = 'JS Fields';

    public $view = 'aura::fields.view-value';

    public function isRelation()
    {
        return true;
    }

    public function relationship($model, $field)
    {
        // If it's a meta field
         return $model
         ->morphToMany($field['resource'], 'related', 'post_relations', 'related_id', 'resource_id')
         ->withTimestamps()
         ->withPivot('resource_type')
         ->wherePivot('resource_type', $field['resource']);
    }

    public function getRelation($model, $field) {

        if (!$model->exists) {
            return collect();
        }

            $relationshipQuery = $this->relationship($model, $field);

            return $relationshipQuery->get();
    }

    public function get($class, $value)
    {
        if (is_array($value)) {
            return array_column($value, 'id');
        } elseif (is_object($value) && method_exists($value, 'pluck')) {
            return $value->pluck('id')->toArray();
        } else {
            return [];
        }
    }

    public function api($request)
    {
        $searchableFields = app($request->model)->getSearchableFields()->pluck('slug')->toArray();

        return app($request->model)->searchIn($searchableFields, $request->search)->take(5)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
            ];
        })->toArray();
    }

    public function display($field, $value, $model)
    {
        if (! $value) {
            return;
        }

        $items = app($field['resource'])->find($value);

        if (! $items) {
            return;
        }

        // return $item->title;

        if ($items instanceof \Illuminate\Support\Collection) {
            return $items->map(function ($item) {
                return $item->title();
            })->implode(', ');
        }

        return $items->title();
    }

    public function saved($post, $field, $value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $ids = $value;

        if (is_array($ids)) {
            ray($ids);
            $post->{$field['slug']}()->syncWithPivotValues($ids, [
                'resource_type' => $field['resource'],
            ]);
        } else {
            $post->{$field['slug']}()->sync([]);
        }

        ray($post);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Select Many',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'select-many',
                'style' => [],
            ],
            [
                'name' => 'Resource',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'resource',
            ],
            [
                'name' => 'Allow Create New',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'slug' => 'create',
            ],
            [
                'name' => 'Multiple',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'slug' => 'multiple',
            ],
        ]);
    }

    public function selectedValues($model, $values)
    {
        if (! $values) {
            return;
        }

        // if $values is a string, convert it to an array
        if (! is_array($values)) {
            $values = [$values];
        }

        return app($model)->find($values)->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
            ];
        })->toArray();
    }

    public function set($post, $field, $value)
    {
        return json_encode($value);
    }

    public function values($model)
    {
        return app($model)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
            ];
        })->toArray();
    }
}
