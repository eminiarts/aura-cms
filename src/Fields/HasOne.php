<?php

namespace Eminiarts\Aura\Fields;

class HasOne extends AdvancedSelect
{
    //public $component = 'aura::fields.has-one';

    public bool $group = false;

    public bool $api = true;

    public bool $multiple = false;

    public bool $searchable = true;

    public string $type = 'relation';
}
