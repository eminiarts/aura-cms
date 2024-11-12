<?php

namespace Aura\Base\Fields;

class Embed extends Field
{
    public $edit = 'aura::fields.embed';

    public $view = 'aura::fields.embed';

    public function getFields()
    {
        return array_merge(parent::getFields(), [

        ]);
    }
}
