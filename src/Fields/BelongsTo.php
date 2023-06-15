<?php

namespace Eminiarts\Aura\Fields;

use Illuminate\Support\Str;

class BelongsTo extends Field
{
    public $component = 'aura::fields.belongsto';

    public bool $group = false;

    // public $view = 'components.fields.belongsto';

    public function api($request)
    {
        // Get $searchable from $request->model
        $searchableFields = app($request->model)->getSearchable();

        return app($request->model)->searchIn($searchableFields, $request->search)->take(20)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
            ];
        })->toArray();
    }

    public function display($field, $value, $model)
    {
        if ($field['resource'] && $value) {
            // Get Str after last backslash from $field['resource']
            $model = Str::afterLast($field['resource'], '\\');

            return $value;

            return "<a class='font-bold' href='".route('aura.post.edit', [$model, $value])."'>".optional(app($field['resource'])::find($value))->title().'</a>';
        }

        return $value;
    }

    public function get($field, $value)
    {
        return json_decode($value, true);
    }

    public function queryFor($model)
    {
        return function ($query) use ($model) {
            return $query->where('user_id', $model->id);
        };
    }

     public function set($value)
     {
         // Set the value to the id of the model
         return $value;
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
