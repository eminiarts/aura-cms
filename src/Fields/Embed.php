<?php

namespace Eminiarts\Aura\Fields;

class Embed extends Field
{
    public string $component = 'aura::fields.embed';

    // public $view = 'components.fields.embed';

    public function getFields()
    {
        return array_merge(parent::getFields(), [

        ]);
    }
}
