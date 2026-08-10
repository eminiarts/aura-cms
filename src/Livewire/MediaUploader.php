<?php

namespace Aura\Base\Livewire;

use Aura\Base\Resources\Attachment;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
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

    #[Locked]
    public $button = false;

    #[Locked]
    public $disabled = false;

    #[Locked]
    public $field;

    #[Locked]
    public ?string $fieldSlug = null;

    #[Locked]
    public $for;

    public $media = [];

    #[Locked]
    public $model;

    #[Locked]
    public $namespace = Attachment::class;

    #[Locked]
    public ?string $resource = null;

    public $selected;

    #[Locked]
    public $table = true;

    #[Locked]
    public $upload = false;

    public array $uploadResult = [
        'successful' => false,
        'message' => '',
        'ids' => [],
    ];

    public function hydrate(): void
    {
        $this->authorizeRequest();
    }

    public function mount(): void
    {
        $this->initializeAuthoritativeContext();
        $this->authorizeRequest();
    }

    public function render(): View
    {
        $this->authorizeRequest();

        return view('aura::livewire.media-uploader', [
            'uploadPolicy' => $this->uploadPolicy(),
        ]);
    }

    #[On('selectedMediaUpdated')]
    public function selectedMediaUpdated(array $data): void
    {
        $this->authorizeRequest();

        if (! is_string($data['slug'] ?? null) || ! is_array($data['value'] ?? null)) {
            abort(422, 'The selected media are invalid.');
        }

        if ($this->fieldSlug !== null && hash_equals($this->fieldSlug, $data['slug'])) {
            $this->selected = $data['value'];
            $this->authorizeRequest();
        }
    }

    public function updatedMedia(): void
    {
        $this->authorizeRequest(authorizeCreate: true);
        $this->resetValidation();
        $this->uploadResult = [
            'successful' => false,
            'message' => '',
            'ids' => [],
        ];

        try {
            $this->validate([
                'media' => [
                    'array',
                    'max:'.self::MAX_FILES,
                ],
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

        $attachment = $this->model;

        if (! $attachment instanceof Model) {
            abort(422, 'The configured attachment resource is invalid.');
        }

        $attachments = [];

        foreach ($this->media as $key => $media) {
            // Additional security check: verify file extension
            $extension = strtolower($media->getClientOriginalExtension());
            if (in_array($extension, self::BLOCKED_EXTENSIONS, true)) {
                unset($this->media[$key]);

                continue;
            }

            $url = $media->store(
                config('aura.media.path', 'media'),
                config('aura.media.disk', 'public'),
            );

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

            $attachments[] = $attachment->newQuery()->create($payload);

            // Unset the processed file
            unset($this->media[$key]);
        }

        // Only the inline field uploader commits the field value directly. In the
        // picker ($table) an upload merely joins the selection — the value is
        // committed when the user confirms with Select. Dispatching updateField
        // here would re-render the form under the open picker and tear it down.
        if ($this->field && ! $this->table) {
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

    private function authorizeRequest(bool $authorizeCreate = false): void
    {
        $authorization = app(MediaFieldAuthorization::class);

        if ($this->resource === null && $this->fieldSlug === null) {
            if ($this->field !== null || $this->for !== null) {
                abort(422, 'The media uploader resource field is invalid.');
            }

            $attachment = $authorization->authorizeLibrary($authorizeCreate);
            $this->model = $attachment;
            $this->namespace = $attachment::class;

            return;
        }

        if ($this->resource === null || $this->fieldSlug === null || ! is_array($this->selected)) {
            abort(422, 'The media uploader resource field is invalid.');
        }

        $authorized = $authorization->authorizeField(
            $this->resource,
            $this->fieldSlug,
            $this->selected,
            $authorizeCreate,
        );
        $this->resource = $authorized['resource_slug'];
        $this->field = $authorized['field'];
        $this->selected = $authorized['selected'];
        $this->model = $authorized['attachment'];
        $this->namespace = $authorized['attachment']::class;
        $this->for = $authorized['resource_slug'];

        if (($this->field['disabled'] ?? false) && $authorizeCreate) {
            abort(403, 'Uploads are disabled for this field.');
        }
    }

    private function initializeAuthoritativeContext(): void
    {
        if ($this->selected === null || $this->selected === '') {
            $this->selected = [];
        }

        if ($this->resource === null && is_string($this->for) && is_array($this->field)) {
            $fieldSlug = $this->field['slug'] ?? null;

            if (! is_string($fieldSlug) || $fieldSlug === '') {
                abort(422, 'The media uploader resource field is invalid.');
            }

            $this->resource = app(MediaFieldAuthorization::class)->normalizeResourceReference($this->for, null);
            $this->fieldSlug = $fieldSlug;
        }
    }
}
