<?php

namespace Eminiarts\Aura\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;

class ImageUpload extends Component
{
    use WithFileUploads;

    public $photos = [];

    public function remove($index)
    {
        array_splice($this->photos, $index, 1);
    }

    public function render()
    {
        return view('aura::livewire.image-upload');
    }

    public function save()
    {
        $this->validate([
            'photos.*' => 'image|max:10240',
        ]);

        foreach ($this->photos as $photo) {
            $photo->store('photos', 'public');
        }
        $this->photos = [];
        session()->flash('message', 'File Uploaded !');
    }

    public function updatedPhoto()
    {
        $this->validate([
            'photo' => 'image|max:10240',
        ]);
    }
}
