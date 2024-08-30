<?php

namespace Aura\Base\Fields;

use Aura\Base\Fields\Field;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Blade;

class AdvancedSelect extends Field
{
    public $edit = 'aura::fields.advanced-select';

    public $optionGroup = 'JS Fields';

    public $view = 'aura::fields.advanced-select-view';

    public $index = 'aura::fields.advanced-select-index';

    public function api($request)
    {
        $model = app($request->model);
        $searchableFields = $model->getSearchableFields()->pluck('slug')->toArray();

        $field = $request->fullField;

        $values = $model->searchIn($searchableFields, $request->search, $model)
            ->take(5)
            ->get()
            ->map(function ($item) use ($field) {
                return [
                    'id' => $item->id,
                    'title' => $item->title(),
                    'view' => isset($field['view_select']) ? view($field['view_select'], ['item' => $item, 'field' => $field])->render() : view('aura::components.fields.advanced-select-view-select', ['item' => $item, 'field' => $field])->render(),
                    'view_selected' => isset($field['view_selected']) ? view($field['view_selected'], ['item' => $item, 'field' => $field])->render() : view('aura::components.fields.advanced-select-view-selected', ['item' => $item, 'field' => $field])->render(),
                ];
            })
            ->toArray();

        return $values;
    }

    // public function display($field, $value, $model)
    // {
    //     if (! $value) {
    //         return;
    //     }


    //     $items = app($field['resource'])->find($value);

    //     if (! $items) {
    //         return;
    //     }

    //     // return $item->title;

    //     if ($items instanceof \Illuminate\Support\Collection) {
    //         return $items->map(function ($item) {
    //             return $item->title();
    //         })->implode(', ');
    //     }

    //     return $items->title();
    // }

    public function get($class, $value)
    {
        ray('get', $class, $value);
        if (is_array($value)) {
            return array_column($value, 'id');
        } elseif (is_object($value) && method_exists($value, 'pluck')) {
            return $value->pluck('id')->toArray();
        } elseif (is_int($value)) {
            return $value;
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
                'name' => 'Thumbnail slug',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'thumbnail',
            ],

            [
                'name' => 'Custom View Selected',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'view_selected',
            ],
            [
                'name' => 'Custom View Select',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'view_select',
            ],

            [
                'name' => 'Custom View View',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'view_view',
            ],
            [
                'name' => 'Custom View Index',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'view_index',
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

            // [
            //     'name' => 'Min Items',
            //     'type' => 'Aura\\Base\\Fields\\Number',
            //     'validation' => 'min:0',
            //     'slug' => 'min_items',
            // ],
            // [
            //     'name' => 'Max Items',
            //     'type' => 'Aura\\Base\\Fields\\Number',
            //     'validation' => 'min:0',
            //     'slug' => 'max_items',
            // ],
        ]);
    }

    public function getRelation($model, $field)
    {
        if (! $model->exists) {
            return collect();
        }

        $relationshipQuery = $this->relationship($model, $field);

        // if (!isset($field['multiple']) || !$field['multiple']) {
        //     return collect([$relationshipQuery->first()]);
        // }

        return $relationshipQuery->get();
    }

    public function isRelation()
    {
        return true;
    }

    public function relationship($model, $field)
    {
        // ray('relationship', $model, $field)->blue();

        $morphClass = $field['resource'];

        return $model
            ->morphToMany($morphClass, 'related', 'post_relations', 'related_id', 'resource_id')
            ->withTimestamps()
            ->withPivot('resource_type')
            ->wherePivot('resource_type', $morphClass);
    }

    public function saved($post, $field, $value)
    {
        ray('saved here', $post, $field, $value)->blue();


        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (!$field['multiple']) {
            $ids = is_null($value) ? [] : [$value];
        } else {
            $ids = $value;
        }

        $ids = $value;

        if (is_array($ids)) {
            $post->{$field['slug']}()->syncWithPivotValues($ids, [
                'resource_type' => $field['resource'],
            ]);
        } else {
            $post->{$field['slug']}()->sync([]);
        }

    }

    public function selectedValues($model, $values, $field)
    {
        if (! $values) {
            return [];
        }

        // if $values is a string, convert it to an array
        if (! is_array($values)) {
            $values = [$values];
        }

        return app($model)->whereIn('id', $values)->get()->map(function ($item) use ($field) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
                'view' => isset($field['view_select']) ? view($field['view_select'], ['item' => $item, 'field' => $field])->render() : view('aura::components.fields.advanced-select-view-select', ['item' => $item, 'field' => $field])->render(),
                'view_selected' => isset($field['view_selected']) ? view($field['view_selected'], ['item' => $item, 'field' => $field])->render() : view('aura::components.fields.advanced-select-view-selected', ['item' => $item, 'field' => $field])->render(),
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
                'view' => isset($field['view_select']) ? view($field['view_select'], ['item' => $item, 'field' => $field])->render() : view('aura::components.fields.advanced-select-view-select', ['item' => $item, 'field' => $field])->render(),
                'view_selected' => isset($field['view_selected']) ? view($field['view_selected'], ['item' => $item, 'field' => $field])->render() : view('aura::components.fields.advanced-select-view-selected', ['item' => $item, 'field' => $field])->render(),

            ];
        })->toArray();
    }
}
