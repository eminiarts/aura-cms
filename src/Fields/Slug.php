<?php

namespace Eminiarts\Aura\Fields;

class Slug extends Field
{
    public $component = 'aura::fields.slug';

    // public $view = 'components.fields.slug';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
