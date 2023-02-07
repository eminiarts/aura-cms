<?php

namespace Eminiarts\Aura\Aura\Fields;

class SelectMany extends Field
{
    public string $component = 'fields.select-many';

    protected string $view = 'components.fields.select-many';

    public function display($field, $value, $model)
    {
        if (! $value) {
            return;
        }

        $items = app($field['posttype'])->find($value);

        if (! $items) {
            return;
        }

        return $items->pluck('name')->map(function ($value) {
            return "<span class='px-2 py-1 text-xs text-white rounded-full bg-primary-500 whitespace-nowrap'>$value</span>";
        })->implode(' ');
    }

    public function get($field, $value)
    {
        if (! $value) {
            return;
        }

        return json_decode($value, true);
    }

    public function getFields()
    {
        return array_merge(parent::getFields(), [
            [
                'label' => 'Select Many',
                'name' => 'Select Many',
                'type' => 'App\\Aura\\Fields\\Tab',
                'slug' => 'select-many',
                'style' => [],
            ],
            [
                'label' => 'Posttype',
                'name' => 'Posttype',
                'type' => 'App\\Aura\\Fields\\Text',
                'validation' => '',
                'slug' => 'posttype',
            ],
        ]);
    }

    public function set($value)
    {
        return json_encode($value);
    }
}
