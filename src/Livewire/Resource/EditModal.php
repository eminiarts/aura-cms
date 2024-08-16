<?php

namespace Aura\Base\Livewire\Resource;

use LivewireUI\Modal\ModalComponent;
use Aura\Base\Livewire\Resource\Edit;

class EditModal extends Edit
{
    public $resource;

    public $type;

    public function mount($resource, $type) {}

    public function render()
    {
        return view('aura::livewire.resource.edit-modal');
    }
}
