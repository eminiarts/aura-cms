<?php

namespace Aura\Base\Fields;

class ID extends Field
{
    public $edit = 'aura::fields.text';

    public bool $on_forms = false;

    public $tableColumnType = 'bigIncrements';

    public $tableNullable = false;

    public string $type = 'input';

    public $view = 'aura::fields.view-value';
}
