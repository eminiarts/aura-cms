<?php

namespace Aura\Base\Fields;

class Datetime extends Field
{
    public $component = 'aura::fields.datetime';

    public $optionGroup = 'Input Fields';

    // public $view = 'components.fields.datetime';

    public $tableColumnType = 'timestamp';

    public function getFields()
    {
        return array_merge(parent::getFields(), []);
    }
}
