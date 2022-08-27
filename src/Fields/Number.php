<?php

namespace Eminiarts\Aura\Fields;

class Number extends Field
{
    protected string $view = 'components.fields.number';

    public string $component = 'fields.number';

    public function value($value)
    {
        return (int) $value;
    }
}
