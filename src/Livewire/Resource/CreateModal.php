<?php

namespace Aura\Base\Livewire\Resource;

use LivewireUI\Modal\ModalComponent;

class CreateModal extends ModalComponent
{
    public $params;

    public $resource;

    public $type;

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }

    public function render()
    {
        return view('aura::livewire.resource.create-modal');
    }
}
