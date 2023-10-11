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
        $searchableFields = app($request->model)->getSearchableFields()->pluck('slug');

        $metaFields = $searchableFields->filter(function ($field) use ($request) {
            // check if it is a meta field
            return app($request->model)->isMetaField($field);
        });

        if (app($request->model)->usesCustomTable()) {
            $results = app($request->model)->searchIn($searchableFields->toArray(), $request->search)->take(20)->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title(),
                ];
            })->toArray();

        } else {

            $results = app($request->model)->select('posts.*')
                ->leftJoin('post_meta', function ($join) use ($metaFields) {
                    $join->on('posts.id', '=', 'post_meta.post_id')
                        ->whereIn('post_meta.key', $metaFields);
                })
                ->where(function ($query) use ($request) {
                    $query->where('posts.title', 'like', '%'.$request->search.'%')
                        ->orWhere(function ($query) use ($request) {
                            $query->where('post_meta.value', 'LIKE', '%'.$request->search.'%');
                        });
                })
                ->distinct()
                ->take(20)
                ->get()->map(function ($item) {
                    return [
                        'id' => $item->id,
                        'title' => $item->title(),
                    ];
                })->toArray();

        }

        // Fetch the model instance using the ID from $request->value
        if ($request->id) {

            $modelInstance = app($request->model)->find($request->id);

            // Append the model instance to the results
            $results[] = [
                'id' => $modelInstance->id,
                'title' => $modelInstance->title(),
            ];

        }

        // $results = app($request->model)->searchIn($searchableFields, $request->search)->take(20)->get();

        return collect($results)->unique('id')->values()->toArray();

        // dd($searchableFields, $request->model, $request->search);

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

            // return $value;

            return "<a class='font-semibold' href='".route('aura.post.edit', [$model, $value])."'>".optional(app($field['resource'])::find($value))->title().'</a>';
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

    public function valuesForApi($model, $currentId)
    {
        $results = app($model)->take(20)->get()->map(function ($item) {
            return [
                'id' => $item->id,
                'title' => $item->title(),
            ];
        })->toArray();

        // Fetch the model instance using the ID from $request->value
        if ($currentId) {

            $modelInstance = app($model)->find($currentId);

            if (! $modelInstance) {
                return $results;
            }

            // Append the model instance to the results
            $results[] = [
                'id' => $modelInstance->id,
                'title' => $modelInstance->title(),
            ];

        }

        return collect($results)->unique('id')->values()->toArray();
    }
}
