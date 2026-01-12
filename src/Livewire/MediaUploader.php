<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\Attachment;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class MediaUploader extends Component
{
    use WithFileUploads;

    public $button = false;

    public $disabled = false;

    public $field;

    public $for;

    public $media = [];

    public $model;

    public $namespace = Attachment::class;

    public $selected;

    public $table = true;

    public $upload = false;

    public function mount()
    {
        $this->model = app($this->namespace);
    }

    public function render()
    {
        return view('aura::livewire.media-uploader');
    }

    #[On('selectedMediaUpdated')]
    public function selectedMediaUpdated($data)
    {
        if ($this->field && ($this->field['slug'] == $data['slug'])) {
            $this->selected = $data['value'];
        }
    }

    public function updatedMedia()
    {
        $this->validate([
            'media.*' => [
                'required',
                'max:102400', // 100MB Max
                'mimes:jpg,jpeg,png,gif,webp,svg,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,mp4,mov,avi,mp3,wav',
                'not_in:php,phtml,php3,php4,php5,phar,sh,exe,bat,cmd,com,scr,vbs,js,jar',
            ],
        ]);

        $attachments = [];

        foreach ($this->media as $key => $media) {
            // Additional security check: verify file extension
            $extension = strtolower($media->getClientOriginalExtension());
            $blockedExtensions = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'sh', 'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js', 'jar'];

            if (in_array($extension, $blockedExtensions)) {
                unset($this->media[$key]);

                continue;
            }

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
            // Emit update Field - use named parameter 'data' to match listener signature
            $this->dispatch('updateField', data: [
                'slug' => $this->field['slug'],
                // merge the new attachments with the old ones
                'value' => optional($this)->selected ? array_merge($this->selected, collect($attachments)->pluck('id')->toArray()) : collect($attachments)->pluck('id')->toArray(),
            ]);

            $this->selected = optional($this)->selected ? array_merge($this->selected, collect($attachments)->pluck('id')->toArray()) : collect($attachments)->pluck('id')->toArray();
        }

        $this->dispatch('refreshTable');
    }
}
