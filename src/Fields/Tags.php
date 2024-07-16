<?php

namespace Aura\Base\Fields;

use Illuminate\Support\Str;

class Tags extends Field
{
    public $component = 'aura::fields.tags';

    public bool $taxonomy = true;

    public string $type = 'relation';

    public $view = 'aura::fields.view-value';

    public function display($field, $value, $model)
    {
        return $value;

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        //  dd($value, app($field['resource'])->whereIn('id',$value)->pluck('title')->map(function ($value) {
        //     return "<span class='px-2 py-1 text-xs text-white whitespace-nowrap rounded-full bg-primary-500'>$value</span>";
        // })->implode(' '));

        if (! is_array($value) || count($value) === 0) {
            return '';
        }

        return app($field['resource'])->query()->whereIn('id', $value)->pluck('title')->map(function ($v) {
            return "<span class='px-2 py-1 text-xs text-white whitespace-nowrap rounded-full bg-primary-500'>$v</span>";
        })->implode(' ');
    }

    public function saved($post, $field, $value)
    {
        ray('saved tags', $value, $field);

        // $value are the ids
        // $field['resource'] is the type



        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        // Assuming $model is an instance of the model that has the tags relationship
        // and Tag is the model name for tags

        $ids = collect($value)->map(function ($tagName) use ($field) {
            $tag = app($field['resource'])->where('title', $tagName)->first();

            if ($tag) {
                return $tag->id;
            } else {
                $tag = app($field['resource'])->create([
                    'title' => $tagName,
                    'slug' => Str::slug($tagName),
                ]);

                return $tag->id;
            }
        })->toArray();

        if (is_array($ids)) {
            ray('sync here', $post, $ids)->red();
            $post->tags()->sync($ids);
        } else {
            $post->tags()->sync([]);
        }
    }

    // public function get($field, $value)
    // {
    //     // dd($value);
    //     if (! $value) {
    //         return;
    //     }

    //     if (is_string($value)) {
    //         return json_decode($value, true);
    //     }

    //     return $value;
    // }

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

        ]);
    }
}
