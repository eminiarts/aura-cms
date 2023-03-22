<?php

namespace Eminiarts\Aura\Fields;

class Repeater extends Field
{
    public $component = 'aura::fields.repeater';

    public bool $group = true;

    public string $type = 'repeater';

    // public $view = 'components.fields.repeater';

    public function get($field, $value)
    {
        return json_decode($value, true);
    }

    public function getFields()
    {
        $fields = collect(parent::getFields())->filter(function ($field) {
            // check if the slug of the field starts with "on", if yes filter it out
            return ! str_starts_with($field['slug'], 'on_');
        })->toArray();

        return array_merge($fields, [

        ]);
    }

    public function set($value)
    {
        return json_encode($value);
    }

    public function transform($field, $values)
    {
        $fields = $field['fields'];
        $slug = $field['slug'];

        $new = collect();

        // dd($values, $fields, $field, $slug);
        if (! $values) {
            return $fields;
        }

        foreach ($values as $key => $value) {
            $new[] = collect($fields)->map(function ($item) use ($slug, $key) {
                $item['slug'] = $slug.'.'.$key.'.'.$item['slug'];

                return $item;
            });
        }

        return $new;

        return $new->flatten(1);
    }
}
