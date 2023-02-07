<?php

namespace Eminiarts\Aura\Fields;

class Datetime extends Field
{
    public string $component = 'fields.datetime';

    protected string $view = 'components.fields.datetime';

    public function getFields()
    {
        return array_merge(parent::getFields(), []);
    }
}
