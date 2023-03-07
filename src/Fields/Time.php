<?php

namespace Eminiarts\Aura\Fields;

class Time extends Field
{
    public string $component = 'aura::fields.time';

    // public $view = 'components.fields.time';

    public function getFields()
    {
        return array_merge(parent::getFields(), []);
    }
}
