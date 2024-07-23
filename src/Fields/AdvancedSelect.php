<?php

namespace Aura\Base\Fields;

class AdvancedSelect extends Field
{
    public $component = 'aura::fields.advanced-select';

    public $optionGroup = 'JS Fields';

    public $view = 'aura::fields.view-value';

    public function api($request)
    {
        $searchableFields = app($request->model)->getSearchableFields()->pluck('slug')->toArray();

        $field = $request->fullField;

        dd(app($request->model)->getBaseFillable());

        dd(app($request->model)->searchIn($searchableFields, $request->search)->take(5)->get(), $searchableFields, $request->search);

        $values = app($request->model)->searchIn($searchableFields, $request->search)->take(5)->get()->map(function ($item) use ($field) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
                'view' => view($field['view'], ['item' => $item])->render()
            ];
        })->toArray();

        dd($values);

        return $values;
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
                'name' => 'Custom View',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'view',
            ],
            [
                'name' => 'Custom View Selected',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'view-selected',
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

    public function getRelation($model, $field)
    {

        if (! $model->exists) {
            return collect();
        }

        $relationshipQuery = $this->relationship($model, $field);

        return $relationshipQuery->get();
    }

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

    public function selectedValues($model, $values, $field)
    {
        if (! $values) {
            return;
        }

        // if $values is a string, convert it to an array
        if (! is_array($values)) {
            $values = [$values];
        }

        return app($model)->get()->map(function ($item) use ($field) {
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

    public function values($model, $field)
    {
        return app($model)->get()->map(function ($item) use ($field) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
                'view' => view($field['view'], ['item' => $item])->render()
            ];
        })->toArray();
    }
}
