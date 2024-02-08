<?php

namespace Aura\Base\Fields;

class Time extends Field
{
    public $component = 'aura::fields.time';

    // public $view = 'components.fields.time';

    public function getFields()
    {
        return array_merge(parent::getFields(), []);
    }
}
