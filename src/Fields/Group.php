<?php

namespace Eminiarts\Aura\Fields;

class Group extends Field
{
    public $component = 'aura::fields.group';

    public bool $group = true;

    public string $type = 'group';

    // public $view = 'components.fields.group';

    // public function get($field, $value)
    // {
    //     return $value;

    //     return json_decode($value, true);
    // }

    // public function getFields()
    // {
    //     $fields = collect(parent::getFields())->filter(function ($field) {
    //         // check if the slug of the field starts with "on", if yes filter it out
    //         return ! str_starts_with($field['slug'], 'on_');
    //     })->toArray();

    //     return array_merge($fields, [

    //     ]);
    // }

    // public function set($value)
    // {
    //     return $value;
    //     // dd('set group', $value);
    //     return json_encode($value);
    // }

    // public function transform($fields, $values)
    // {
    //     $slug = $this->attributes['slug'];

    //     // Create a collection of $fields, then map over it and add the slug to the item slug
    //     $fields = collect($fields)->map(function ($item) use ($slug) {
    //         $item['slug'] = $slug.'.'.$item['slug'];

    //         return $item;
    //     })->toArray();

    //     return $fields;
    // }
}
