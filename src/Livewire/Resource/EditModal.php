<?php

namespace Aura\Base\Livewire\Resource;

use LivewireUI\Modal\ModalComponent;

class EditModal extends ModalComponent
{
    public $resource;

    public $type;

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }

    public function mount($resource, $type) {}

    public function render()
    {
        return view('aura::livewire.resource.edit-modal');
    }
}
