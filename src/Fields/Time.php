<?php

namespace Eminiarts\Aura\Aura\Fields;

class Time extends Field
{
    public string $component = 'fields.time';

    protected string $view = 'components.fields.time';

    public function getFields()
    {
        return array_merge(parent::getFields(), []);
    }
}
