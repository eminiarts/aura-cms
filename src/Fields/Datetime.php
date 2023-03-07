<?php

namespace Eminiarts\Aura\Fields;

class Datetime extends Field
{
    public string $component = 'aura::fields.datetime';

    // public $view = 'components.fields.datetime';

    public function getFields()
    {
        return array_merge(parent::getFields(), []);
    }
}
