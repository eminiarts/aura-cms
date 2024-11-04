<?php

namespace Aura\Base\Fields;

class Tabs extends Field
{
    public $edit = 'aura::fields.tabs';

    public bool $group = true;

    public string $type = 'tabs';

    public $view = 'aura::fields.tabs';

    public bool $sameLevelGrouping = false;
}
