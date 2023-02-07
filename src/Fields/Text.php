<?php

namespace Eminiarts\Aura\Aura\Fields;

class Text extends Field
{
    public string $component = 'fields.text';

    protected string $view = 'components.fields.text';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
