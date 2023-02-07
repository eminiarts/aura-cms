<?php

namespace Eminiarts\Aura\Fields;

class Heading extends Field
{
    public string $component = 'fields.heading';

    protected string $view = 'components.fields.heading';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
