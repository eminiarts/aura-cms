<?php

namespace Eminiarts\Aura\Fields;

class Heading extends Field
{
    public string $component = 'aura::fields.heading';

    // public $view = 'components.fields.heading';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
