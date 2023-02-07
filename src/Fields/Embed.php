<?php

namespace App\Aura\Fields;

class Embed extends Field
{
    public string $component = 'fields.embed';

    protected string $view = 'components.fields.embed';

    public function getFields()
    {
        return array_merge(parent::getFields(), [

        ]);
    }
}
