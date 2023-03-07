<?php

namespace Eminiarts\Aura\Fields;

class Phone extends Field
{
    public $component = 'aura::fields.phone';

    // public $view = 'components.fields.phone';

    public function getFields()
    {
        return array_merge(parent::getFields(), []);
    }
}
