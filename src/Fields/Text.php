<?php

namespace Eminiarts\Aura\Fields;

class Text extends Field
{
    public $component = 'aura::fields.text';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
