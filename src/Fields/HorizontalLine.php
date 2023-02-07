<?php

namespace Eminiarts\Aura\Fields;

class HorizontalLine extends Field
{
    public string $component = 'fields.hr';

    protected string $view = 'components.fields.hr';

    public function getFields()
    {
        return array_merge(parent::getFields(), [
        ]);
    }
}
