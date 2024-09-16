<?php

namespace Aura\Base\Fields;

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

    public function get($class, $value, $field = null)
    {

          if (isset($field['polymorphic_relation']) && $field['polymorphic_relation'] === false) {
            // ray('save meta', $field['slug'], $ids);

                // ray('get ........', $class, $value, $field)->blue();


            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }
                return $value;
            }
           

            // ray('save meta', $post->meta()->get());
            return;
        }

        if (!($field['multiple'] ?? true) && !($field['polymorphic_relation'] ?? false)) {
            // dd('hier');
            // ray('hier before return int', $field['slug'], $value)->red();
            if ($value instanceof \Illuminate\Support\Collection) {
                if ($value->isEmpty()) {
                    return [];
                } else {
                    return [(int) $value->first()];
                }
            }
            return [(int) $value];
        }

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

        return $this->relationship($model, $field)->get();
    }

    public function isRelation($field = null)
    {
        if (optional($field)['polymorphic_relation'] === false) {
            return false;
        }

        return true;
    }

    public function relationship($model, $field)
    {
        if (!($field['multiple'] ?? true) && !($field['polymorphic_relation'] ?? true)) {
            $resourceClass = $field['resource'];
            $value = $model->meta()->where('key', $field['slug'])->value('value');
            return $resourceClass::where('id', $value);
        }

        $morphClass = $field['resource'];

        return $model
            ->morphToMany($morphClass, 'related', 'post_relations', 'related_id', 'resource_id')
            ->withTimestamps()
            ->withPivot('resource_type', 'slug', 'order')
            ->wherePivot('resource_type', $morphClass)
            ->wherePivot('slug', $field['slug'])
            ->orderBy('post_relations.order');
    }

    public function saved($post, $field, $value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $ids = $value;

        //ray('saved', $post, $field, $value, $ids);

        if (isset($field['polymorphic_relation']) && $field['polymorphic_relation'] === false) {
            ray('save meta', $field['slug'], $ids);
            // Save as meta
            $post->meta()->updateOrCreate(['key' => $field['slug']], ['value' => $ids ?? null]);

            // ray('save meta', $post->meta()->get());
            return;
        }

        $pivotData = [];

        if (empty($ids)) {
            return;
        }

        if (is_int($ids)) {
            return;
        }

        // Temporary fix for the issue
        if (is_string($ids) && json_decode($ids) !== null) {
            $ids = json_decode($ids, true);
        }

        // ray('ids', $ids);

        foreach ($ids as $index => $item) {
            $id = is_array($item) ? ($item['id'] ?? null) : $item;
            if ($id !== null && (is_string($id) || is_int($id))) {
                $pivotData[$id] = [
                    'resource_type' => $field['resource'],
                    'slug' => $field['slug'],
                    'order' => $index + 1,
                ];
            }
        }

        // Get the current relations for this specific field
        $currentRelations = $post->{$field['slug']}()
            ->wherePivot('slug', $field['slug'])
            ->pluck('resource_id')
            ->toArray();

        // Detach only the relations for this specific field that are not in the new set
        $toDetach = array_diff($currentRelations, array_keys($pivotData));
        if (!empty($toDetach)) {
            $post->{$field['slug']}()->wherePivot('slug', $field['slug'])->detach($toDetach);
        }

        // ray('pivotData', $pivotData);

        // ray('1 ' . $field['slug'], $post->{$field['slug']}()->get());

        // Attach or update the new relations
        foreach ($pivotData as $id => $data) {
            $post->{$field['slug']}()->syncWithoutDetaching([$id => $data]);
        }

        // ray('2 ' . $field['slug'], $post->{$field['slug']}()->get());
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
        // Add logging or debugging at the start of the method
        // dd('AdvancedSelect set method called', ['field' => $field, 'value' => $value]);

        // Check if 'multiple' key exists in $field array
        $isMultiple = $field['multiple'] ?? false;

        if ($isMultiple) {
            return json_encode($value);
        }

        if (!$isMultiple && !($field['polymorphic_relation'] ?? false)) {
            if (is_array($value) && !empty($value)) {
                return $value[0];
            }
        }

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
