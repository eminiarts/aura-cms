<?php

namespace Eminiarts\Aura\Http\Livewire\Post;

use LivewireUI\Modal\ModalComponent;

class EditModal extends ModalComponent
{
    public $post;

    public $type;

    public static function modalMaxWidth(): string
    {
        return '7xl';
    }

    public function mount($post, $type)
    {
    }

    public function render()
    {
        return view('aura::livewire.post.edit-modal');
    }
}
