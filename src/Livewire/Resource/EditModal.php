<?php

namespace Aura\Base\Livewire\Resource;

class EditModal extends Edit
{
    public $resource;

    public $type;

    public function mount($id) {}

    public function render()
    {
        return view('aura::livewire.resource.edit-modal');
    }
}
