<?php

namespace Aura\Base\Fields;

class HasOne extends AdvancedSelect
{
    public bool $api = true;

    public bool $group = false;

    public bool $multiple = false;

    public $component = 'aura::fields.has-one';

    public $optionGroup = 'Relationship Fields';

    public bool $searchable = true;

    public string $type = 'relation';
}
