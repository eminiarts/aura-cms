<?php

namespace Eminiarts\Aura\Fields;

class AdvancedSelect extends Field
{
    public $component = 'aura::fields.advanced-select';

    public $view = 'aura::fields.view-value';

    public function display($field, $value, $model)
    {
        if (! $value) {
            return;
        }

        $items = app($field['resource'])->find($value);

        if (! $items) {
            return;
        }

        return $items->pluck('name')->map(function ($value) {
            return "<span class='px-2 py-1 text-xs text-white rounded-full bg-primary-500 whitespace-nowrap'>$value</span>";
        })->implode(' ');
    }

    public function get($field, $value)
    {
        if (! $value) {
            return;
        }

        return json_decode($value, true);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Select Many',
                'name' => 'Select Many',
                'type' => 'Eminiarts\\Aura\\Fields\\Tab',
                'slug' => 'select-many',
                'style' => [],
            ],
            [
                'label' => 'Resource',
                'name' => 'Resource',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'resource',
            ],
        ]);
    }

    public function set($value)
    {
        return json_encode($value);
    }

    public function api($request)
    {
        // Get $searchable from $request->model
        $searchableFields = app($request->model)->getSearchable();


        return app($request->model)->searchIn($searchableFields, $request->search)->take(5)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
            ];
        })->toArray();
    }


    public function values($model)
    {
        return app($model)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
            ];
        })->toArray();


        // app($model)->pluck('title', 'id')->map(fn($name, $key) => ['value' => $key, 'label' => $name])->values()->toArray()
    }

    public function selectedValues($model, $values)
    {
        if (! $values) {
            return;
        }

        // if $values is a string, convert it to an array
        if (!is_array($values)) {
            $values = [ $values];
        }

        return app($model)->find($values)->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
            ];
        })->toArray();


        // app($model)->pluck('title', 'id')->map(fn($name, $key) => ['value' => $key, 'label' => $name])->values()->toArray()
    }
}