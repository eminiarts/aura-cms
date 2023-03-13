<?php

namespace Eminiarts\Aura\Fields;

class SelectMany extends Field
{
    public $component = 'aura::fields.select-many';

    public $view = 'aura::fields.view-value';

    public function display($field, $value, $model)
    {
        if (! $value) {
            return;
        }

        $items = app($field['posttype'])->find($value);

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
                'label' => 'Posttype',
                'name' => 'Posttype',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'posttype',
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

        sleep(2);

        return app($request->model)->searchIn($searchableFields, $request->search)->take(20)->get()->map(function ($item) {
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
        return app($model)->find($values)->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
            ];
        })->toArray();


        // app($model)->pluck('title', 'id')->map(fn($name, $key) => ['value' => $key, 'label' => $name])->values()->toArray()
    }
}
