<?php

namespace Aura\Base\Fields;

class Wysiwyg extends Field
{
    public $component = 'aura::fields.wysiwyg';

    public $optionGroup = 'JS Fields';

    public $tableColumnType = 'text';

    public $view = 'aura::fields.view-value';
}
