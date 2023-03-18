<?php

namespace Eminiarts\Aura\Fields;

class HasOne extends AdvancedSelect
{
    public bool $api = true;
    //public $component = 'aura::fields.has-one';

    public bool $group = false;

    public bool $multiple = false;

    public bool $searchable = true;

    public string $type = 'relation';
}
