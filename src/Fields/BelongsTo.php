<?php

namespace Aura\Base\Fields;

use Aura\Base\Contracts\PreloadsTableDisplay;
use Aura\Base\Models\Meta;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Collection;

class BelongsTo extends Field implements PreloadsTableDisplay
{
    public $edit = 'aura::fields.belongsto';

    public bool $group = false;

    public $optionGroup = 'Relationship Fields';

    public $tableColumnType = 'bigInteger';

    public string $type = 'input';

    public $view = 'aura::fields.view-value';

    // public function get($class, $model, $field)
    // {
    //     $relationshipQuery = $this->relationship($model, $field);

    //     return $relationshipQuery->get();
    // }

    public function api($request)
    {
        $model = app($request->model);

        // Get $searchable from $request->model
        $searchableFields = $model->getSearchableFields()->pluck('slug');

        $metaFields = $searchableFields->filter(function ($field) use ($model) {
            // check if it is a meta field
            return $model->isMetaField($field);
        });

        if ($model->usesCustomTable()) {
            $results = $model->searchIn($searchableFields->toArray(), $request->search, $model)->take(50)->get()->map(function ($item) {
                return [
                    'id' => $item->id,
                    'title' => $item->title(),
                ];
            })->toArray();

        } else {

            $results = $model->select($model->getTable().'.*')
                ->leftJoin('meta', function ($join) use ($metaFields, $model) {
                    $join->on($model->getQualifiedKeyName(), '=', 'meta.metable_id')
                        ->where('meta.metable_type', $model->getMorphClass())
                        ->whereIn('meta.key', $metaFields);
                })
                ->where(function ($query) use ($model, $request) {
                    $query->where($model->getTable().'.title', 'like', '%'.$request->search.'%')
                        ->orWhere(function ($query) use ($request) {
                            $query->where('meta.value', 'LIKE', '%'.$request->search.'%');
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

            $modelInstance = $model->find($request->id);

            // Append the model instance to the results
            $results[] = [
                'id' => $modelInstance->id,
                'title' => $modelInstance->title(),
            ];

        }

        // $results = app($request->model)->searchIn($searchableFields, $request->search)->take(20)->get();

        return collect($results)->unique('id')->values()->toArray();
    }

    public function display($field, $value, $model)
    {
        if (optional($field)['display_view']) {
            return view($field['display_view'], ['row' => $model, 'field' => $field, 'value' => $value])->render();
        }

        if ($field['resource'] && $value) {

            $resourceClass = $field['resource'];

            $slug = $resourceClass::getSlug();

            $related = $this->resolveDisplayModel($field, $value, $model);

            return "<a class='font-semibold' href='".route('aura.'.$slug.'.edit', $value)."'>".optional($related)->title().'</a>';
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

    public function preloadTableDisplay(Collection $rows, array $field): void
    {
        // display_view and field-level display closures take precedence over
        // the class display() and never issue the batched lookup, so skip.
        if (empty($field['resource']) || ! empty($field['display_view']) || ! empty($field['display'])) {
            return;
        }

        $slug = $field['slug'];
        $resourceClass = $field['resource'];

        $ids = [];

        foreach ($rows as $row) {
            if (! $row instanceof Resource) {
                continue;
            }

            $id = $this->tableDisplayForeignId($row, $slug);

            if ($id !== null && $id !== '') {
                $ids[$id] = $id;
            }
        }

        $related = collect();

        if (! empty($ids)) {
            $keyName = (new $resourceClass)->getKeyName();

            // Scoped query: keep TeamScope/TypeScope/ScopedScope intact so that
            // rows the viewer may not see resolve to null, not to a foreign title.
            $related = $resourceClass::query()
                ->whereKey(array_values($ids))
                ->get()
                ->keyBy($keyName);
        }

        foreach ($rows as $row) {
            if (! $row instanceof Resource) {
                continue;
            }

            $id = $this->tableDisplayForeignId($row, $slug);
            $model = ($id !== null && $id !== '') ? $related->get($id) : null;

            $row->setTableDisplayValue($slug, $model);
        }
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
                'value',     // Foreign key on the meta table
                'id',        // Foreign key on the resource table
                'id',        // Local key on the model table
                'metable_id' // Local key on the meta table
            )->where('meta.key', $field['relation'])
                ->where('meta.metable_type', $model->getMorphClass());
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

    protected function resolveDisplayModel($field, $value, $model)
    {
        $slug = $field['slug'] ?? null;

        // Use the value primed by preloadTableDisplay() when rendering inside a
        // table; array_key_exists semantics mean a scoped-out row resolves to
        // null (no query, no foreign title) rather than re-querying.
        if ($slug && $model instanceof Resource && $model->hasTableDisplayValue($slug)) {
            return $model->getTableDisplayValue($slug);
        }

        return $field['resource']::find($value);
    }

    protected function tableDisplayForeignId(Resource $row, string $slug)
    {
        $value = $row->fields[$slug] ?? null;

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return $value;
    }
}
