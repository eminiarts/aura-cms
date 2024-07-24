<?php

namespace Aura\Base\Fields;

class Phone extends Field
{
    public $edit = 'aura::fields.phone';

    public $optionGroup = 'Input Fields';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), []);
    }
}
