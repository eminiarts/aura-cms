<?php

namespace App\Aura\Fields;

class Email extends Field
{
    public string $component = 'fields.email';

    protected string $view = 'components.fields.email';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
