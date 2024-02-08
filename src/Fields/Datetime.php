<?php

namespace Aura\Base\Fields;

class Datetime extends Field
{
    public $component = 'aura::fields.datetime';

    // public $view = 'components.fields.datetime';

    public function getFields()
    {
        return array_merge(parent::getFields(), []);
    }
}
