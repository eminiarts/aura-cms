<?php

namespace Eminiarts\Aura\Http\Livewire\Post;

use LivewireUI\Modal\ModalComponent;

class CreateModal extends ModalComponent
{
    public $params;

    public $post;

    public $type;

    public function render()
    {
        return view('aura::livewire.post.create-modal');
    }
}
