<?php

namespace Aura\Base\Fields;

class Hidden extends Field
{
    public $component = 'aura::fields.hidden';

    public $view = 'aura::fields.view-hidden';

    public function getFields()
    {
        return array_merge(parent::getFields());
    }
}
