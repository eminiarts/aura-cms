<?php

namespace Aura\Base\Fields;

class Repeater extends Field
{
    public $edit = 'aura::fields.repeater';

    public bool $group = true;

    public $optionGroup = 'Structure Fields';

    public string $type = 'input';

    // public bool $showChildrenOnIndex = false;
    // TODO: $showChildrenOnIndex should be applied to children

    // public $view = 'components.fields.repeater';

    public function get($class, $value, $field = null)
    {
        // $fields = $this->getFields();
        if (is_array($value)) {
            return $value;
        }

        return json_decode($value, true);
    }

    public function getFields()
    {
        $fields = collect(parent::getFields())->filter(function ($field) {
            // check if the slug of the field starts with "on", if yes filter it out
            return ! str_starts_with($field['slug'], 'on_');
        })->toArray();

        return array_merge($fields, [

            [
                'name' => 'Min Entries',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'slug' => 'min',
                'default' => 0,
                'style' => [
                    'width' => '50',
                ],
            ],
            [
                'name' => 'Max Entries',
                'type' => 'Aura\\Base\\Fields\\Number',
                'validation' => '',
                'slug' => 'max',
                'default' => 0,
                'style' => [
                    'width' => '50',
                ],
            ],

        ]);
    }

    public function set($post, $field, $value)
    {
        return json_encode($value);
    }

    public function transform($field, $values)
    {
        $fields = $field['fields'];
        $slug = $field['slug'];

        $new = collect();

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
