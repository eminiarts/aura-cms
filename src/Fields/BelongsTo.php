<?php

namespace Aura\Base\Fields;

use Aura\Base\Models\Meta;
use Illuminate\Support\Str;

class BelongsTo extends Field
{
    public $edit = 'aura::fields.belongsto';

    public bool $group = false;

    public $optionGroup = 'Relationship Fields';

    public $tableColumnType = 'bigInteger';

    public string $type = 'input';

    public $view = 'aura::fields.view-value';

    // public function get($model, $field)
    // {
    //     // ray($field, $model);
    //     ray()->backtrace();
    //     dd($model, $field);

    //     $relationshipQuery = $this->relationship($model, $field);

    //     return $relationshipQuery->get();
    // }

    public function api($request)
    {
        // Get $searchable from $request->model
        $searchableFields = app($request->model)->getSearchableFields()->pluck('slug');

        $metaFields = $searchableFields->filter(function ($field) use ($request) {
            // check if it is a meta field
            return app($request->model)->isMetaField($field);
        });

        if (app($request->model)->usesCustomTable()) {
            $results = app($request->model)->searchIn($searchableFields->toArray(), $request->search)->take(50)->get()->map(function ($item) {
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
        if (optional($field)['display_view']) {
            return view($field['display_view'], ['row' => $model, 'field' => $field, 'value' => $value])->render();
        }

        if ($field['resource'] && $value) {
            // Get Str after last backslash from $field['resource']
            $model = Str::afterLast($field['resource'], '\\');

            // return $value;

            return "<a class='font-semibold' href='".route('aura.resource.edit', [$model, $value])."'>".optional(app($field['resource'])::find($value))->title().'</a>';
        }

        return $value;
    }

    // public function get($field, $value)
    // {
    //     return json_decode($value, true);
    // }

    // public $view = 'components.fields.belongsto';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Belongs To',
                'name' => 'Belongs To',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tab-belongsTo',
                'style' => [],
            ],
            [
                'label' => 'Resource',
                'name' => 'resource',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'resource',
            ],
        ]);
    }

    public function queryFor($model)
    {
        return function ($query) use ($model) {
            return $query->where('user_id', $model->id);
        };
    }

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

    public function set($post, $field, $value)
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
