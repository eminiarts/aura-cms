<?php

namespace Eminiarts\Aura\Fields;

class Repeater extends Field
{
    protected string $view = 'components.fields.repeater';

    public string $component = 'fields.repeater';

    public string $type = 'repeater';

    public bool $group = true;

    public function transform($fields, $values)
    {
        $slug = $this->attributes['slug'];

        $new = collect();

        //dd($fields, $values);

        foreach ($values as $key => $value) {
            $new[] = collect($fields)->map(function ($item) use ($slug, $key) {
                $item['slug'] = $slug.'.'.$key.'.'.$item['slug'];

                return $item;
            });
        }

        return $new;

        return $new->flatten(1);
    }

    public function set($value)
    {
        return json_encode($value);
    }

    public function get($field, $value)
    {
        return json_decode($value, true);
    }
}
