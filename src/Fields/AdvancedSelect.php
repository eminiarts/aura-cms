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

    public function get($field, $value)
    {
        if (! $value) {
            return;
        }

        if (is_string($value)) {
            return json_decode($value, true);
        }

        return $value;
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

    public function set($value)
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
