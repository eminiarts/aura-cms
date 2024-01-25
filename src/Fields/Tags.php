<?php

namespace Eminiarts\Aura\Fields;

use Illuminate\Support\Str;

class Tags extends Field
{
    public $component = 'aura::fields.tags';

    public string $type = 'input';

    // public $view = 'components.fields.tags';

    public function display($field, $value, $model)
    {
        // Get Taxonomy Name from Model, e.g. 'Tag' from 'App\Models\Tag'
        $taxonomy = Str::afterLast($field['resource'], '\\');

        return $model->taxonomy($taxonomy)->pluck('title')->map(function ($value) {
            return "<span class='px-2 py-1 text-xs text-white rounded-full bg-primary-500 whitespace-nowrap'>$value</span>";
        })->implode(' ');
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }

    public function set($value, $field)
    {
        ray('set tags', $value, $field);

        if (is_string($value)) {
            $value = json_decode($value, true);
        }

        // Assuming $model is an instance of the model that has the tags relationship
        // and Tag is the model name for tags

        $value = collect($value)->map(function ($tagName) use ($field) {
            $tag = app($field['resource'])->where('title', $tagName)->first();

            if($tag) {
                return $tag->id;
            } else {
                $tag = app($field['resource'])->create([
                    'title' => $tagName,
                    'slug' => Str::slug($tagName),
                ]);

                return $tag->id;
            }
        })->toArray();

        ray('values', $value);

        return $value;
    }
}
