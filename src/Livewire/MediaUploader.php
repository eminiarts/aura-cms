<?php

namespace Aura\Base\Livewire;

use Aura\Base\Livewire\Media\MediaAuthorization;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Resource;
use Aura\Base\Resources\Attachment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;
use Livewire\WithFileUploads;
use Throwable;

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
    public ?string $detailsComponentId = null;

    #[Locked]
    public $disabled = false;

    #[Locked]
    public $field;

    #[Locked]
    public $for;

    public $media = [];

    #[Locked]
    public $model;

    #[Locked]
    public $namespace = Attachment::class;

    #[Locked]
    public string $ownerToken = '';

    #[Locked]
    public string $ownerTokenDigest = '';

    #[Locked]
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
        $this->authorizeContext();
    }

    public function mount(?string $ownerToken = null): void
    {
        $actor = $this->actor();
        $this->namespace = config('aura.resources.attachment', Attachment::class);
        $this->model = app($this->namespace);
        $broker = app(MediaOwnerTokenBroker::class);
        $this->ownerToken = $ownerToken ?: $broker->issueLibrary($this->getId(), $actor);
        $this->ownerTokenDigest = $broker->digest($this->ownerToken);
        $this->authorizeContext();
    }

    public function render(): View
    {
        $this->authorizeContext();

        return view('aura::livewire.media-uploader', [
            'uploadPolicy' => $this->uploadPolicy(),
        ]);
    }

    #[On('selectedMediaUpdated')]
    public function selectedMediaUpdated(array $data): void
    {
        if ($this->field && ($this->field['slug'] == $data['slug'])) {
            $actor = $this->authorizeContext();
            $this->selected = app(MediaAuthorization::class)
                ->authorizeAttachments(is_array($data['value'] ?? null) ? $data['value'] : [], $actor)
                ->map(fn (Resource $attachment): string => (string) $attachment->getKey())
                ->all();
        }
    }

    public function updatedMedia(): void
    {
        $actor = $this->authorizeContext();
        $attachmentPrototype = app(MediaAuthorization::class)->authorizeAttachmentCreate($actor);
        $this->resetValidation();
        $this->uploadResult = [
            'successful' => false,
            'message' => '',
            'ids' => [],
        ];

        try {
            $this->validate([
                'media' => ['required', 'array', 'max:'.self::MAX_FILES],
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

            $disk = config('aura.media.disk', 'public');
            $url = null;

            try {
                $url = $media->store(
                    config('aura.media.path', 'media'),
                    $disk,
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

                $attachmentClass = $attachmentPrototype::class;
                $attachments[] = $attachmentPrototype->getConnection()->transaction(
                    fn (): Resource => $attachmentClass::create($payload),
                );
            } catch (Throwable) {
                if (is_string($url) && $url !== '') {
                    Storage::disk($disk)->delete($url);
                }

                $this->addError("media.{$key}", __('Upload failed. Please try again.'));
                $this->uploadResult['message'] = __('Upload failed. Please try again.');
                unset($this->media[$key]);

                continue;
            }

            // Unset the processed file
            unset($this->media[$key]);
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
            $this->dispatch('media-uploaded', ids: $ids, ownerToken: $this->ownerToken);
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

    private function actor(): Authenticatable
    {
        $actor = auth()->user();

        if (! $actor instanceof Authenticatable) {
            abort(403);
        }

        return $actor;
    }

    private function authorizeContext(): Authenticatable
    {
        $actor = $this->actor();

        if (! hash_equals($this->ownerTokenDigest, app(MediaOwnerTokenBroker::class)->digest($this->ownerToken))) {
            abort(403);
        }

        $owner = app(MediaAuthorization::class)->authorizeOwner($this->ownerToken, $actor);

        if ($this->field && ($this->field['slug'] ?? null) !== $owner->context->slug) {
            abort(403);
        }

        if (is_string($this->for) && $this->for !== '' && $this->for !== $owner->context->modelClass) {
            abort(403);
        }

        if (is_array($this->selected)) {
            app(MediaAuthorization::class)->authorizeAttachments($this->selected, $actor);
        }

        return $actor;
    }
}
