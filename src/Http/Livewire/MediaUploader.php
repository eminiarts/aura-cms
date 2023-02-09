<?php

namespace Eminiarts\Aura\Http\Livewire;

use Eminiarts\Aura\Resources\Attachment;
use Livewire\Component;
use Livewire\WithFileUploads;

class MediaUploader extends Component
{
    use WithFileUploads;

    public $media = [];

    public Attachment $post;

    public function render()
    {
        return view('aura::livewire.media-uploader');
    }

    public function mount(Attachment $post)
    {
        $this->post = $post;
    }

    public function updatedMedia()
    {
        // dd('media updated');

        $this->validate([
            'media.*' => 'required|max:102400', // 100MB Max, for now
        ]);

        foreach ($this->media as $media) {
            $url = $media->store('media', 'public');

            // dd($media);

            Attachment::create([
                'url' => $url,
                'name' => $media->getClientOriginalName(),
                'title' => $media->getClientOriginalName(),
                'size' => $media->getSize(),
                'mime_type' => $media->getMimeType(),
            ]);
        }

        $this->emit('refreshTable');
    }
}
