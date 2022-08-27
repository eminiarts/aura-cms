<?php

namespace Eminiarts\Aura\Fields;

class Boolean extends Field
{
    protected string $view = 'components.fields.boolean';

    public string $component = 'fields.boolean';

    public function value($value)
    {
        return (bool) $value;
    }
}
