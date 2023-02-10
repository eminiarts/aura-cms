<?php

namespace Eminiarts\Aura\Fields;

class Email extends Field
{
    public string $component = 'aura::fields.email';

    protected string $view = 'components.fields.email';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
