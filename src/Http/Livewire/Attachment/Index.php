<?php

namespace Eminiarts\Aura\Http\Livewire\Attachment;

use Eminiarts\Aura\Http\Livewire\Post\Index as PostIndex;

class Index extends PostIndex
{
    public function mount($slug = 'Attachment')
    {
        // Call parent mount method
        parent::mount($slug);
    }

    public function render()
    {
        return view('aura::livewire.attachment.index');
    }
}
