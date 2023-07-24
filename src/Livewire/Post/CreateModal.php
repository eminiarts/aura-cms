<?php

namespace Eminiarts\Aura\Livewire\Post;

use LivewireUI\Modal\ModalComponent;

class CreateModal extends ModalComponent
{
    public $params;

    public $post;

    public $type;

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }

    public function render()
    {
        return view('aura::livewire.post.create-modal');
    }
}
