<?php

namespace Aura\Base\Fields;

class Panel extends Field
{
    public $component = 'aura::fields.panel';

    public bool $group = true;

    public $optionGroup = 'Structure Fields';

    // Type Panel is used for grouping fields. A Panel can't be nested inside another Panel or other grouped Fields.
    public string $type = 'panel';

    public function getFields()
    {
        $fields = collect(parent::getFields())->filter(function ($field) {
            // check if the slug of the field starts with "on", if yes filter it out
            return ! str_starts_with($field['slug'], 'on_');
        })->toArray();

        return array_merge($fields, [

        ]);
    }
}
