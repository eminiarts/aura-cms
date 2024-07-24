<?php

namespace Aura\Base\Fields;

class HorizontalLine extends Field
{
    public $edit = 'aura::fields.hr';

    public $optionGroup = 'Layout Fields';

    // public $view = 'components.fields.hr';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
