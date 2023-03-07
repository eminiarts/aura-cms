<?php

namespace Eminiarts\Aura\Fields;

class Email extends Field
{
    public $component = 'aura::fields.email';

    // public $view = 'components.fields.email';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
