<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\Attachment;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;

class MediaUploader extends Component
{
    use WithFileUploads;

    private const ALLOWED_EXTENSIONS = 'jpg,jpeg,png,gif,webp,pdf,doc,docx,xls,xlsx,ppt,pptx,txt,csv,zip,mp4,mov,avi,mp3,wav';

    private const BLOCKED_EXTENSIONS = ['php', 'phtml', 'php3', 'php4', 'php5', 'phar', 'sh', 'exe', 'bat', 'cmd', 'com', 'scr', 'vbs', 'js', 'jar', 'svg'];

    private const MAX_FILE_SIZE_KILOBYTES = 102400;

    private const MAX_FILES = 20;

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

    public array $uploadResult = [
        'successful' => false,
        'message' => '',
        'ids' => [],
    ];

    public function mount(): void
    {
        $this->model = app($this->namespace);
    }

    public function render(): View
    {
        return view('aura::livewire.media-uploader', [
            'uploadPolicy' => $this->uploadPolicy(),
        ]);
    }

    #[On('selectedMediaUpdated')]
    public function selectedMediaUpdated(array $data): void
    {
        if ($this->field && ($this->field['slug'] == $data['slug'])) {
            $this->selected = $data['value'];
        }
    }

    public function updatedMedia(): void
    {
        $this->resetValidation();
        $this->uploadResult = [
            'successful' => false,
            'message' => '',
            'ids' => [],
        ];

        try {
            $this->validate([
                'media.*' => [
                    'required',
                    'max:'.self::MAX_FILE_SIZE_KILOBYTES,
                    // SVG intentionally excluded: SVGs can embed <script> and are served
                    // inline from the public disk, enabling stored XSS.
                    'mimes:'.self::ALLOWED_EXTENSIONS,
                    'not_in:'.implode(',', self::BLOCKED_EXTENSIONS),
                ],
            ]);
        } catch (ValidationException $exception) {
            foreach ($exception->errors() as $key => $messages) {
                foreach ($messages as $message) {
                    $this->addError($key, $message);
                }
            }

            $this->uploadResult['message'] = (string) collect($exception->errors())->flatten()->first();
            $this->media = [];

            return;
        }

        $attachments = [];

        foreach ($this->media as $key => $media) {
            // Additional security check: verify file extension
            $extension = strtolower($media->getClientOriginalExtension());
            if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
                unset($this->media[$key]);

                continue;
            }

            $url = $media->store('media', 'public');

            $payload = [
                'url' => $url,
                'name' => $media->getClientOriginalName(),
                'title' => $media->getClientOriginalName(),
                'size' => $media->getSize(),
                'mime_type' => $media->getMimeType(),
            ];

            if (str_starts_with((string) $media->getMimeType(), 'image/')
                && ($dimensions = @getimagesize($media->getRealPath()))) {
                $payload['width'] = $dimensions[0];
                $payload['height'] = $dimensions[1];
            }

            $attachments[] = app(config('aura.resources.attachment'))::create($payload);

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

        // Notify consumers (grid highlight, picker auto-select) about the freshly
        // created attachments. Only dispatch when at least one was created.
        if (! empty($attachments)) {
            $ids = collect($attachments)->pluck('id')->all();

            $this->uploadResult = [
                'successful' => true,
                'message' => '',
                'ids' => $ids,
            ];
            $this->dispatch('media-uploaded', ids: $ids);
        }

        $this->dispatch('refreshTable');
    }

    /**
     * @return array{max_files: int, max_size_bytes: int, blocked_extensions: array<int, string>}
     */
    public function uploadPolicy(): array
    {
        return [
            'max_files' => self::MAX_FILES,
            'max_size_bytes' => self::MAX_FILE_SIZE_KILOBYTES * 1024,
            'blocked_extensions' => self::BLOCKED_EXTENSIONS,
        ];
    }
}
