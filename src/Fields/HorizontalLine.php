<?php

namespace Aura\Base\Fields;

class HorizontalLine extends Field
{
    public $component = 'aura::fields.hr';

    // public $view = 'components.fields.hr';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
