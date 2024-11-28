<?php

namespace Aura\Base\Fields;

class Panel extends Field
{
    public $edit = 'aura::fields.panel';

    public bool $group = true;

    public bool $sameLevelGrouping = true;

    public $optionGroup = 'Structure Fields';

    // Type Panel is used for grouping fields. A Panel can't be nested inside another Panel or other grouped Fields.
    public string $type = 'panel';

    public function getFields()
    {
        $fields = collect(parent::getFields())->filter(function ($field) {
            // Filter out fields with slug starting with "on_" or equal to "searchable"
            if ($field['slug'] === 'searchable') {
                return false;
            }
            return ! str_starts_with($field['slug'], 'on_');
        })->toArray();

        return array_merge($fields, [

        ]);
    }
}
