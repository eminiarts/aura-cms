<?php

namespace Aura\Base\Fields;

class HasOne extends AdvancedSelect
{
    public bool $api = true;

    public $component = 'aura::fields.has-one';

    public bool $group = false;

    public bool $multiple = false;

    public $optionGroup = 'Relationship Fields';

    public bool $searchable = true;

    public string $type = 'relation';
}
