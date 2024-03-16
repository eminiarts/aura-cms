<?php

namespace Aura\Base\Fields;

class ID extends Field
{
    public $component = 'aura::fields.text';

    public bool $on_forms = false;

    public string $type = 'input';

    public $view = 'aura::fields.view-value';
}
