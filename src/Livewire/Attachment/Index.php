<?php

namespace Eminiarts\Aura\Livewire\Attachment;

use Eminiarts\Aura\Livewire\Post\Index as PostIndex;

class Index extends PostIndex
{
    public function mount($slug = 'Attachment')
    {
        // Call parent mount method
        parent::mount($slug);
    }

    public function render()
    {
        return view('aura::livewire.attachment.index')->layout('aura::components.layout.app');
    }
}
