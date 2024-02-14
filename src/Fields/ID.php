<?php

namespace Aura\Base\Fields;

class ID extends Field
{
    public $component = 'aura::fields.text';

    

    public $on_forms = false;

    public string $type = 'index';

    public $view = 'aura::fields.view-value';
}
