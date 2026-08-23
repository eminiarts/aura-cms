<?php

namespace Aura\Base\Fields;

use Aura\Base\Contracts\ProvidesTableEagerLoad;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;
use InvalidArgumentException;

class Tags extends Field implements ProvidesTableEagerLoad
{
    public $edit = 'aura::fields.tags';

    public $filter = 'aura::fields.filters.tags';

    public bool $taxonomy = true;

    public string $type = 'input';

    public $view = 'aura::fields.view-value';

    public function display($field, $value, $model)
    {
        $slug = $field['slug'] ?? null;

        // Reuse the relation eager-loaded by the table (ProvidesTableEagerLoad)
        // instead of re-querying once per row.
        if ($slug && $model instanceof Model && $model->relationLoaded($slug)) {
            $resource = $model->getRelation($slug);
        } else {
            if (is_string($value)) {
                $value = json_decode($value, true);
            }

            if (! is_array($value) || count($value) === 0) {
                return '';
            }

            $resource = app($field['resource'])->query()->whereIn('id', $value)->get();
        }

        if ($resource->isEmpty()) {
            return '';
        }

        return $resource->map(function ($item) {
            // The tag title is database-backed and this producer emits markup
            // that view-value renders raw, so escape it here.
            $title = e($item->title ?? $item->title());

            return "<span class='px-2 py-1 text-xs text-white whitespace-nowrap rounded-full bg-primary-500'>$title</span>";
        })->implode(' ');
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
            'does_not_contain' => __('does not contain'),
        ];
    }

    public function get($class, $value, $field = null)
    {
        if (is_array($value)) {
            return array_column($value, 'id');
        } elseif (is_object($value) && method_exists($value, 'pluck')) {
            return $value->pluck('id')->toArray();
        } else {
            return [];
        }
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'name' => 'Tags',
                'type' => 'Aura\\Base\\Fields\\Tab',
                'slug' => 'tags-tab',
                'style' => [],
            ],
            [
                'name' => 'Create',
                'type' => 'Aura\\Base\\Fields\\Boolean',
                'validation' => 'required',
                'instructions' => 'Allow new creations of Tags',
                'slug' => 'create',
                'default' => false,
            ],
            [
                'name' => 'Resource',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => '',
                'slug' => 'resource',
            ],
            [
                'name' => 'Max Tags',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'slug' => 'max_tags',
            ],

        ]);
    }

    public function getRelation($model, $field)
    {

        if (! $model->exists) {
            return collect();
        }

        $slug = $field['slug'] ?? null;

        // Reuse the eager-loaded relation when the table primed it.
        if ($slug && $model instanceof Model && $model->relationLoaded($slug)) {
            return $model->getRelation($slug);
        }

        $relationshipQuery = $this->relationship($model, $field);

        return $relationshipQuery->get();

    }

    public function isRelation()
    {
        return true;
    }

    public function relationship($model, $field)
    {
        // Check if resource is set
        if (! isset($field['resource']) || empty($field['resource'])) {
            throw new InvalidArgumentException("The 'resource' key is not set or is empty in the field configuration.");
        }

        $morphClass = $field['resource'];

        // If it's a meta field
        return $model
            ->morphToMany($field['resource'], 'related', 'post_relations', 'related_id', 'resource_id')
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

        $ids = $this->resolveIds($field, $value);

        if (is_array($ids) && count($ids) > 0) {
            // Prepare pivot data for each ID
            $pivotData = [];
            foreach ($ids as $index => $id) {
                $pivotData[$id] = [
                    'resource_type' => $field['resource'],
                    'slug' => $field['slug'],
                    'order' => $index + 1,
                ];
            }

            $post->{$field['slug']}()->sync($pivotData);
        } else {
            $post->{$field['slug']}()->sync([]);
        }
    }

    public function tableEagerLoad(array $field): string|array|null
    {
        if (empty($field['resource']) || empty($field['slug'])) {
            return null;
        }

        return $field['slug'];
    }

    /**
     * A submitted value is an id when it is an int or a digit-only string.
     * Everything else is a free-text label typed by the user.
     */
    protected function isId($value): bool
    {
        return is_int($value) || (is_string($value) && ctype_digit($value));
    }

    /**
     * Resolve the submitted values into ids that may be attached.
     *
     * Ids are re-read through the resource's own (globally scoped) query so
     * unknown or out-of-team ids are dropped instead of being attached blindly.
     * Labels only become records when the field allows creating and the user is
     * authorized to create on the target resource; otherwise they are dropped
     * without failing the save.
     *
     * @param  mixed  $value
     * @return array<int, int>
     */
    protected function resolveIds($field, $value): array
    {
        $values = collect($value)
            ->filter(fn ($item) => is_int($item) || is_string($item))
            ->values();

        if ($values->isEmpty()) {
            return [];
        }

        $submittedIds = $values
            ->filter(fn ($item) => $this->isId($item))
            ->map(fn ($item) => (int) $item);

        $existingIds = $submittedIds->isEmpty()
            ? collect()
            : app($field['resource'])->newQuery()
                ->whereIn('id', $submittedIds->all())
                ->pluck('id')
                ->map(fn ($id) => (int) $id);

        $canCreate = ($field['create'] ?? true) !== false
            && Gate::allows('create', app($field['resource']));

        return $values
            ->map(function ($item) use ($field, $existingIds, $canCreate) {
                if ($this->isId($item)) {
                    return $existingIds->contains((int) $item) ? (int) $item : null;
                }

                return $canCreate ? $this->resolveLabel($field, $item) : null;
            })
            ->filter()
            ->unique()
            ->values()
            ->all();
    }

    /**
     * Turn a free-text label into a record id, reusing a tag that already
     * exists in the current scope instead of duplicating it on every save.
     */
    protected function resolveLabel($field, string $label): ?int
    {
        $label = trim($label);
        $slug = Str::slug($label);

        if ($label === '' || $slug === '') {
            return null;
        }

        $tag = app($field['resource'])->newQuery()->firstOrCreate(
            ['slug' => $slug],
            ['title' => $label]
        );

        return $tag->id;
    }
}
