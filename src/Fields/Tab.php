<?php

namespace Eminiarts\Aura\Fields;

class Tab extends Field
{
    public string $component = 'aura::fields.tab';

    public bool $group = true;

    public bool $sameLevelGrouping = true;

    public string $type = 'tab';

    // public $view = 'components.fields.tab';

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
