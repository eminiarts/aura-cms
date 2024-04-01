<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\Attachment;
use Livewire\Component;
use Livewire\WithFileUploads;

class MediaUploader extends Component
{
    use WithFileUploads;

    public $button = false;

    public $field;

    public $for;

    public $media = [];

    public $model;

    public $namespace = Attachment::class;

    public $selected;

    public $table = true;

    public $upload = false;

    // listener selectedMediaUpdated
    protected $listeners = ['selectedMediaUpdated' => 'selectedMediaUpdated'];

    public function mount()
    {
        // ray('mount media uploader', $this->model, $this->field, $this->selected);
        $this->model = app($this->namespace);
    }

    public function render()
    {
        return view('aura::livewire.media-uploader');
    }

    public function selectedMediaUpdated($data)
    {
        if ($this->field && ($this->field['slug'] == $data['slug'])) {
            $this->selected = $data['value'];
        }
    }

    public function updatedMedia()
    {
        $this->validate([
            'media.*' => 'required|max:102400', // 100MB Max, for now
        ]);

        $attachments = [];

        foreach ($this->media as $key => $media) {
            $url = $media->store('media', 'public');

            $attachments[] = app(config('aura.resources.attachment'))::create([
                'url' => $url,
                'name' => $media->getClientOriginalName(),
                'title' => $media->getClientOriginalName(),
                'size' => $media->getSize(),
                'mime_type' => $media->getMimeType(),
            ]);

            // Unset the processed file
            unset($this->media[$key]);
        }

        if ($this->field) {
            // Emit update Field
            $this->dispatch('updateField', [
                'slug' => $this->field['slug'],
                // merge the new attachments with the old ones
                'value' => optional($this)->selected ? array_merge($this->selected, collect($attachments)->pluck('id')->toArray()) : collect($attachments)->pluck('id')->toArray(),
            ]);

            $this->selected = optional($this)->selected ? array_merge($this->selected, collect($attachments)->pluck('id')->toArray()) : collect($attachments)->pluck('id')->toArray();
        }

        $this->dispatch('refreshTable');
    }
}
