<?php

namespace Eminiarts\Aura\Fields;

class Phone extends Field
{
    public string $component = 'fields.phone';

    protected string $view = 'components.fields.phone';

    public function getFields()
    {
        return array_merge(parent::getFields(), []);
    }
}
