<?php

namespace Eminiarts\Aura\Fields;

use Illuminate\Support\Str;

class Tags extends Field
{
    public string $component = 'fields.tags';

    //public string $type = 'taxonomy';

    public bool $taxonomy = true;

    protected string $view = 'components.fields.tags';

    public function display($field, $value, $model)
    {
        // Get Taxonomy Name from Model, e.g. 'Tag' from 'App\Models\Tag'
        $taxonomy = Str::afterLast($field['model'], '\\');

        return $model->taxonomy($taxonomy)->pluck('name')->map(function ($value) {
            return "<span class='px-2 py-1 text-xs text-white rounded-full bg-primary-500 whitespace-nowrap'>$value</span>";
        })->implode(' ');
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
