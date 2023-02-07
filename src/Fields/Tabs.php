<?php

namespace Eminiarts\Aura\Aura\Fields;

class Tabs extends Field
{
    public string $component = 'fields.tabs';

    public bool $group = true;

    public string $type = 'tabs';

    protected string $view = 'components.fields.tabs';
}
