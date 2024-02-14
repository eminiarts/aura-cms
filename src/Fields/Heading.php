<?php

namespace Aura\Base\Fields;

class Heading extends Field
{
    public $component = 'aura::fields.heading';

    public $optionGroup = 'Layout Fields';

    // public $view = 'components.fields.heading';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
