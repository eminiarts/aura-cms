<?php

namespace Eminiarts\Aura\Fields;

class Tabs extends Field
{
    public string $component = 'aura::fields.tabs';

    public bool $group = true;

    public string $type = 'tabs';

    protected string $view = 'components.fields.tabs';
}
