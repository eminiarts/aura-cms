<?php

namespace Eminiarts\Aura\Fields;

class Tab extends Field
{
    protected string $view = 'components.fields.tab';

    public string $component = 'fields.tab';

    public string $type = 'tab';

    public bool $group = true;
}
