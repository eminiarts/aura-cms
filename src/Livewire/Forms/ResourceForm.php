<?php

namespace Eminiarts\Aura\Livewire\Forms;

use Livewire\Attributes\Validate;
use Livewire\Form;

class ResourceForm extends Form
{
    public $fields = [];

    public function setFields($fields)
    {
        $this->fields = $fields;
    }
}