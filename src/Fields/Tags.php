<?php

namespace Aura\Base\Fields;

use Illuminate\Support\Str;
use InvalidArgumentException;

class Tags extends Field
{
    public $edit = 'aura::fields.tags';

    public $filter = 'aura::fields.filters.tags';

    public bool $taxonomy = true;

    public string $type = 'input';

    public $view = 'aura::fields.view-value';

    public function display($field, $value, $model)
    {

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        if (! is_array($value) || count($value) === 0) {
            return '';
        }

        $resource = app($field['resource'])->query()->whereIn('id', $value)->get();

        return $resource->map(function ($item) {
            $title = $item->title ?? $item->title();

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

        ray('saved', $value)->green();

        $ids = collect($value)->map(function ($tagName) use ($field) {

            if (is_int($tagName)) {
                return $tagName;
            } else {
                $tag = app($field['resource'])->create([
                    'title' => $tagName,
                    'slug' => Str::slug($tagName),
                ]);

                return $tag->id;
            }
        })->toArray();

        if (is_array($ids)) {
            $post->{$field['slug']}()->syncWithPivotValues($ids, [
                'resource_type' => $field['resource'],
                'slug' => $field['slug'],
            ]);
        } else {
            $post->{$field['slug']}()->sync([]);
        }
    }
}
