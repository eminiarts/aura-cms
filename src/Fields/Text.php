<?php

namespace Eminiarts\Aura\Fields;

class Text extends Field
{
    public $component = 'aura::fields.text';

    public $view = 'aura::view.text';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
