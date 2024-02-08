<?php

namespace Aura\Base\Fields;

class Slug extends Field
{
    public $component = 'aura::fields.slug';

    public $view = 'aura::fields.view-value';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
