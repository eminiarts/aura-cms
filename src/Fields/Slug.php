<?php

namespace App\Aura\Fields;

class Slug extends Field
{
    public string $component = 'fields.slug';

    protected string $view = 'components.fields.slug';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
