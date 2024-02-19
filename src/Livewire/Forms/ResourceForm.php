<?php

namespace Aura\Base\Livewire\Forms;

use Livewire\Form;

class ResourceForm extends Form
{
    public $fields = [];

    public function setFields($fields)
    {
        $this->fields = $fields;
    }
}
