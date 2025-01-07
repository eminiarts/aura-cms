<?php

namespace Aura\Base\Fields;

class AdvancedSelect extends Field
{
    public $edit = 'aura::fields.advanced-select';

    public $filter = 'aura::fields.filters.advanced-select';

    public $index = 'aura::fields.advanced-select-index';

    public $optionGroup = 'JS Fields';

    public $view = 'aura::fields.advanced-select-view';

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

    public function filter()
    {
        if ($this->filter) {
            return $this->filter;
        }
    }

    public function filterOptions()
    {
        return [
            'contains' => __('contains'),
        ];
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

            if (empty($value)) {
                return;
            }

            if (is_string($value)) {
                $decoded = json_decode($value, true);
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $decoded;
                }

                return $value;
            }

            return $value;
        }

        if (! ($field['multiple'] ?? true) && ! ($field['polymorphic_relation'] ?? false)) {
            if (is_int($value)) {
                return [$value];
            }
            if ($value instanceof \Illuminate\Database\Eloquent\Model) {
                // ray($value)->green();
                return [$value->id];
            }
            if ($value instanceof \Illuminate\Support\Collection) {
                if ($value->isEmpty()) {
                    return [];
                }
                $first = $value->first();

                return [$first instanceof \Illuminate\Database\Eloquent\Model ? $first->id : $first];
            }

            return is_numeric($value) ? [(int) $value] : [];
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
                'name' => 'Return Type',
                'type' => 'Aura\\Base\\Fields\\Radio',
                'validation' => '',
                'slug' => 'return_type',
                'options' => [
                    'id' => 'Ids',
                    'object' => 'Objects',

                ],
                'default' => 'id',
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
                'name' => 'Polymorphic Relation',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => '',
                'slug' => 'polymorphic_relation',
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

        if (optional($field)['return_type'] == 'id') {
            return $model->fields[$field['slug']];
        }

        if (optional($field)['multiple'] === false) {
            return $this->relationship($model, $field)->first();
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
        if (! ($field['polymorphic_relation'] ?? true)) {
            $resourceClass = $field['resource'];
            if ($field['multiple'] ?? true) {
                $values = $model->meta()->where('key', $field['slug'])->value('value');
                $ids = json_decode($values, true) ?: [];

                return $resourceClass::whereIn('id', $ids);
            } else {
                $value = $model->meta()->where('key', $field['slug'])->value('value');

                return $resourceClass::where('id', $value);
            }
        }

        $morphClass = $field['resource'];

        if ($field['reverse'] ?? false) {
            return $model
                ->morphToMany($morphClass, 'related', 'post_relations', 'related_id', 'resource_id')
                ->withTimestamps()
                ->withPivot('resource_type', 'related_type', 'slug', 'order')
                ->wherePivot('related_type', get_class($model))
                ->wherePivot('resource_type', $morphClass)
                ->wherePivot('slug', $field['slug'])
                ->orderBy('post_relations.order');
        }

        return $model
            ->morphToMany($morphClass, 'resource', 'post_relations', 'resource_id', 'related_id')
            ->withTimestamps()
            ->withPivot('resource_type', 'related_type', 'slug', 'order')
            ->wherePivot('related_type', $morphClass)
            ->wherePivot('resource_type', get_class($model))
            ->wherePivot('slug', $field['slug'])
            ->orderBy('post_relations.order');
    }

    public function saved($post, $field, $value)
    {
        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        $ids = $value;

        if (optional($field)['polymorphic_relation'] === false) {
            $value = is_array($ids) ? json_encode($ids) : $ids;
            $post->meta()->updateOrCreate(['key' => $field['slug']], ['value' => $value ?? null]);

            return;
        }

        $pivotData = [];

        if (is_int($ids)) {
            $ids = [$ids];
        }

        foreach ($ids as $index => $item) {
            $id = is_array($item) ? ($item['id'] ?? null) : $item;
            if ($id !== null && (is_string($id) || is_int($id))) {
                if ($field['reverse'] ?? false) {
                    $pivotData[$id] = [
                        'resource_type' => $field['resource'],
                        'related_type' => get_class($post),
                        'slug' => $field['slug'],
                        'order' => $index + 1,
                    ];
                } else {
                    $pivotData[$id] = [
                        'resource_type' => get_class($post),
                        'related_type' => $field['resource'],
                        'slug' => $field['slug'],
                        'order' => $index + 1,
                    ];
                }
            }
        }

        $relation = $post->{$field['slug']}();

        // Clear existing relations for this field
        $relation->wherePivot('slug', $field['slug'])->detach();

        // Attach new relations
        if (! empty($pivotData)) {
            $relation->attach($pivotData);
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
        // Check if 'multiple' key exists in $field array and set default to true
        $isMultiple = $field['multiple'] ?? true;

        if (optional($field)['multiple'] === false) {
            $isMultiple = false;
        }

        if ($isMultiple) {
            return json_encode($value);
        }

        if (! $isMultiple && ! ($field['polymorphic_relation'] ?? false)) {
            // if (is_array($value) && ! empty($value)) {
            //     return $value[0];
            // }
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
