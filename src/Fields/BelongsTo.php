<?php

namespace Aura\Base\Fields;

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Contracts\PreloadsTableDisplay;
use Aura\Base\Models\Meta;
use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Resource;
use Aura\Base\Support\FieldDisplayValue;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Route;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;
use Throwable;

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
        return $this->presentValue(
            $value,
            is_array($field) ? $field : [],
            $model instanceof Model ? $model : null,
        );
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

    /**
     * Return the relation's plain-text label without presentation markup.
     *
     * @param  array<string, mixed>  $field
     */
    public function label(
        array $field,
        mixed $value,
        ?Model $model = null,
        FieldValueContext $context = FieldValueContext::Index,
    ): mixed {
        return $this->resolveLabel($value, $field, $model, $context);
    }

    /**
     * Resolve an authorized destination for the related record.
     *
     * @param  array<string, mixed>  $field
     */
    public function linkDestination(
        Model $related,
        array $field,
        ?Model $model = null,
        FieldValueContext $context = FieldValueContext::Index,
        mixed $value = null,
    ): ?string {
        $canView = $this->canAccessDestination('view', $related);
        $canUpdate = $this->canAccessDestination('update', $related);

        if (! $canView && ! $canUpdate) {
            return null;
        }

        $resolver = $field['link_resolver'] ?? null;

        if (is_callable($resolver)) {
            $destination = $resolver($related, $field, $model, $context, $value);

            if (! is_string($destination) || $destination === '') {
                return null;
            }

            if ($this->isSafeLinkDestination($destination)) {
                return $destination;
            }
        }

        $slug = method_exists($related, 'getSlug') ? $related->getSlug() : null;

        if (! is_string($slug) || $slug === '') {
            return null;
        }

        foreach (['view', 'edit'] as $ability) {
            $policyAbility = $ability === 'edit' ? 'update' : $ability;

            if (($policyAbility === 'view' && ! $canView) || ($policyAbility === 'update' && ! $canUpdate)) {
                continue;
            }

            $routeName = 'aura.'.$slug.'.'.$ability;

            if (! Route::has($routeName)) {
                continue;
            }

            try {
                $destination = route($routeName, ['id' => $related->getKey()]);

                return $this->isSafeLinkDestination($destination) ? $destination : null;
            } catch (Throwable) {
                return null;
            }
        }

        return null;
    }

    /**
     * Prime relation presentation for any collection-based surface.
     *
     * @param  iterable<int, mixed>  $rows
     * @param  array<string, mixed>  $field
     */
    public function preloadPresentation(iterable $rows, array $field): void
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

    public function preloadTableDisplay(Collection $rows, array $field): void
    {
        $this->preloadPresentation($rows, $field);
    }

    public function presentValue(
        mixed $value,
        array $field,
        ?Model $model,
        FieldValueContext $context = FieldValueContext::Index,
    ): mixed {
        if (optional($field)['display_view']) {
            return FieldDisplayValue::sanitizedHtml(
                view($field['display_view'], ['row' => $model, 'field' => $field, 'value' => $value])->render(),
            );
        }

        if (empty($field['resource']) || $value === null || $value === '') {
            return $value;
        }

        $related = $this->resolveDisplayModel($field, $value, $model);
        $label = $this->resolveRelationLabel($value, $field, $model, $related, $context);

        if ($context === FieldValueContext::Export || ! $related instanceof Model) {
            return FieldDisplayValue::secure($label);
        }

        $destination = $this->linkDestination($related, $field, $model, $context, $value);

        if ($destination === null) {
            return FieldDisplayValue::secure($label);
        }

        $href = e($destination);
        $title = e((string) $label);

        return new HtmlString("<a class='font-semibold' href='{$href}'>{$title}</a>");
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

    public function resolveLabel(
        mixed $value,
        array $field,
        ?Model $model = null,
        FieldValueContext $context = FieldValueContext::Index,
    ): mixed {
        if (empty($field['resource']) || $value === null || $value === '') {
            return $value;
        }

        $related = $this->resolveDisplayModel($field, $value, $model);

        return $this->resolveRelationLabel($value, $field, $model, $related, $context);
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

    protected function canAccessDestination(string $ability, Model $related): bool
    {
        if (Gate::allows($ability, $related)) {
            return true;
        }

        $user = auth()->user();

        if ($user === null || ! $related instanceof Resource) {
            return false;
        }

        $policy = Gate::getPolicyFor($related);

        if ($policy !== null) {
            return false;
        }

        $policy = app(ResourcePolicy::class);

        return method_exists($policy, $ability) && (bool) $policy->{$ability}($user, $related);
    }

    protected function isSafeLinkDestination(string $destination): bool
    {
        if (
            $destination === ''
            || trim($destination) !== $destination
            || preg_match('//u', $destination) !== 1
            || preg_match('/[\x00-\x20\x7F\p{Z}]/u', $destination) === 1
            || Str::startsWith($destination, '//')
            || Str::contains($destination, '\\')
            || html_entity_decode($destination, ENT_QUOTES | ENT_HTML5, 'UTF-8') !== $destination
        ) {
            return false;
        }

        if (preg_match('/^([a-z][a-z0-9+.-]*):/i', $destination, $matches) !== 1) {
            return true;
        }

        if (! in_array(Str::lower($matches[1]), ['http', 'https'], true)) {
            return false;
        }

        $components = parse_url($destination);

        return is_array($components)
            && is_string($components['host'] ?? null)
            && $components['host'] !== '';
    }

    /**
     * @param  array<string, mixed>  $field
     */
    protected function loadedRelationName(array $field, mixed $model): ?string
    {
        if (! $model instanceof Model) {
            return null;
        }

        $candidates = [
            $field['presentation_relation'] ?? null,
            is_string($field['relation'] ?? null) ? $field['relation'] : null,
            isset($field['slug']) ? Str::beforeLast((string) $field['slug'], '_id') : null,
        ];

        foreach ($candidates as $candidate) {
            if (is_string($candidate) && $candidate !== '' && $model->relationLoaded($candidate)) {
                return $candidate;
            }
        }

        return null;
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

        $relationName = $this->loadedRelationName($field, $model);

        if ($relationName !== null) {
            $related = $model->getRelation($relationName);

            if ($related instanceof Model) {
                return $related;
            }
        }

        $resource = $field['resource'] ?? null;

        if (! is_string($resource) || ! is_subclass_of($resource, Model::class)) {
            return;
        }

        return $resource::query()->whereKey($value)->first();
    }

    /**
     * @param  array<string, mixed>  $field
     */
    protected function resolveRelationLabel(
        mixed $value,
        array $field,
        ?Model $model,
        mixed $related,
        FieldValueContext $context,
    ): mixed {
        $currentLabel = $value;

        if ($related instanceof Model) {
            $title = method_exists($related, 'title')
                ? $related->title()
                : $related->getAttribute('title');

            if ($title !== null && $title !== '') {
                $currentLabel = $title;
            }
        }

        $resolver = $field['label_resolver'] ?? null;

        if (! is_callable($resolver)) {
            return $currentLabel;
        }

        return $resolver($this->rawValue($value), $currentLabel, $model, $context, $field, $related)
            ?? $currentLabel;
    }

    protected function tableDisplayForeignId(Resource $row, string $slug)
    {
        if (array_key_exists($slug, $row->getAttributes())) {
            $value = $row->getAttribute($slug);
        } else {
            $fields = $row->fields;
            $value = $fields instanceof \Illuminate\Support\Collection
                ? $fields->get($slug)
                : (is_array($fields) ? ($fields[$slug] ?? null) : null);
        }

        if (is_array($value)) {
            $value = $value[0] ?? null;
        }

        return $value;
    }
}
