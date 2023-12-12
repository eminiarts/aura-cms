<?php

namespace Eminiarts\Aura\Fields;

class ID extends Field
{
    public $component = 'aura::fields.text';

    public $on_forms = false;

    public string $type = 'input';

    public $view = 'aura::fields.view-value';
}
