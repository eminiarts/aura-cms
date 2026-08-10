<?php

namespace Aura\Base\Livewire;

use Aura\Base\Livewire\Media\InvalidMediaOwnerContext;
use Aura\Base\Livewire\Media\InvalidMediaOwnerToken;
use Aura\Base\Livewire\Media\MediaAuthorization;
use Aura\Base\Livewire\Media\MediaDetailsBroker;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Resource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

/**
 * The Details Panel: shows a single attachment's preview and editable
 * metadata. Rendered as a drawer on the Media Library page
 * ($surface = 'index') and as a sidebar inside the Media Picker
 * ($surface = 'picker', no destructive actions).
 */
class AttachmentDetails extends Component
{
    public string $altText = '';

    #[Locked]
    public ?int $attachmentId = null;

    #[Locked]
    public ?string $correlationComponentId = null;

    #[Locked]
    public ?string $fieldSlug = null;

    #[Locked]
    public ?string $ownerToken = null;

    #[Locked]
    public ?string $ownerTokenDigest = null;

    /**
     * Ordered ids of the currently listed attachments, for prev/next.
     *
     * @var array<int, int>
     */
    #[Locked]
    public array $rowIds = [];

    #[Locked]
    public string $surface = 'index';

    public string $title = '';

    public function close(): void
    {
        $this->attachmentId = null;

        $this->dispatch('attachment-details-closed');
    }

    public function deleteAttachment(): void
    {
        if ($this->surface !== 'index' || ! ($attachment = $this->attachment())) {
            return;
        }

        Gate::authorize('delete', $attachment);

        $next = $this->siblingId(1) ?? $this->siblingId(-1);

        $attachment->delete();

        $this->rowIds = array_values(array_diff($this->rowIds, [$this->attachmentId]));

        $this->dispatch('refreshTable');
        $this->dispatch('notify', message: __('Attachment deleted'), type: 'success');

        if ($next) {
            $this->show($next);
        } else {
            $this->close();
        }
    }

    public function hydrate(): void
    {
        $this->authorizePickerContext();
        $this->authorizeCurrentAttachment();
    }

    public function mount(
        string $surface = 'index',
        ?string $ownerToken = null,
        ?string $correlationComponentId = null,
        ?string $fieldSlug = null,
        ?int $attachmentId = null,
    ): void {
        $this->surface = $surface;
        $this->ownerToken = $ownerToken;
        $this->correlationComponentId = $correlationComponentId;
        $this->fieldSlug = $fieldSlug;
        $this->ownerTokenDigest = is_string($ownerToken)
            ? app(MediaOwnerTokenBroker::class)->digest($ownerToken)
            : null;

        if ($attachmentId !== null) {
            $this->show($attachmentId);
        }
    }

    public function next(): void
    {
        if ($id = $this->siblingId(1)) {
            $this->show($id);
        }
    }

    #[On('open-attachment-details')]
    public function open(
        int|string|null $id = null,
        array $ids = [],
        ?string $ownerToken = null,
        ?string $componentId = null,
        ?string $fieldSlug = null,
        ?string $detailsToken = null,
    ): void {
        if ($this->surface === 'picker') {
            $actor = $this->actor();

            if (! is_string($this->ownerToken)
                || ! is_string($this->ownerTokenDigest)
                || ! is_string($this->correlationComponentId)
                || ! is_string($this->fieldSlug)
                || ! is_string($detailsToken)) {
                return;
            }

            try {
                $details = app(MediaDetailsBroker::class)->consume(
                    $detailsToken,
                    $this->ownerToken,
                    $this->correlationComponentId,
                    $this->fieldSlug,
                    $actor,
                );
                app(MediaAuthorization::class)->authorizeOwner($this->ownerToken, $actor, expectedSlug: $this->fieldSlug);
                app(MediaAuthorization::class)->authorizeAttachments($details['row_ids'], $actor);
                app(MediaAuthorization::class)->authorizeAttachments($details['selection_ids'], $actor);
            } catch (InvalidMediaOwnerContext|InvalidMediaOwnerToken) {
                return;
            }

            $id = $details['attachment_id'];
            $ids = $details['row_ids'];
        }

        if ($id === null) {
            return;
        }

        $this->rowIds = array_map('intval', (array) $ids);

        $this->show((int) $id);
    }

    public function previous(): void
    {
        if ($id = $this->siblingId(-1)) {
            $this->show($id);
        }
    }

    public function render(): View
    {
        $this->authorizePickerContext();

        return view('aura::livewire.attachment-details', [
            'attachment' => $this->attachment(),
        ]);
    }

    public function updatedAltText(): void
    {
        $this->validate(['altText' => 'nullable|string|max:500']);

        $this->persist(['alt_text' => $this->altText]);
    }

    public function updatedTitle(): void
    {
        $this->validate(['title' => 'required|string|max:255']);

        $this->persist(['name' => $this->title]);
    }

    protected function attachment(): ?Resource
    {
        if (! $this->attachmentId) {
            return null;
        }

        return $this->authorizedAttachment($this->attachmentId);
    }

    protected function persist(array $attributes): void
    {
        if (! ($attachment = $this->attachment())) {
            return;
        }

        Gate::authorize('update', $attachment);

        $attachment->update($attributes);

        $this->dispatch('attachment-details-saved');
        $this->dispatch('refreshTable');
    }

    protected function show(int $id): void
    {
        $attachment = $this->authorizedAttachment($id);

        $this->attachmentId = $attachment->id;
        // Note: `?? ''` would silently yield '' here — Resource meta attributes
        // resolve through __get but do not implement __isset.
        $this->title = (string) $attachment->__get('name');
        $this->altText = (string) $attachment->__get('alt_text');

        $this->resetErrorBag();
    }

    protected function siblingId(int $offset): ?int
    {
        if (! $this->attachmentId || $this->rowIds === []) {
            return null;
        }

        $index = array_search($this->attachmentId, $this->rowIds, true);

        if ($index === false) {
            return null;
        }

        return $this->rowIds[$index + $offset] ?? null;
    }

    private function actor(): Authenticatable
    {
        $actor = auth()->user();

        if (! $actor instanceof Authenticatable) {
            abort(403);
        }

        return $actor;
    }

    private function authorizeCurrentAttachment(): void
    {
        if ($this->attachmentId !== null) {
            $this->authorizedAttachment($this->attachmentId);
        }
    }

    private function authorizedAttachment(int $id): Resource
    {
        try {
            $attachment = app(MediaAuthorization::class)
                ->authorizeAttachments([$id], $this->actor())
                ->first();
        } catch (InvalidMediaOwnerContext) {
            abort(403);
        }

        if (! $attachment instanceof Resource) {
            abort(403);
        }

        return $attachment;
    }

    private function authorizePickerContext(): void
    {
        if ($this->surface !== 'picker') {
            return;
        }

        if (! is_string($this->ownerToken)
            || ! is_string($this->ownerTokenDigest)
            || ! is_string($this->correlationComponentId)
            || $this->correlationComponentId === ''
            || ! is_string($this->fieldSlug)
            || ! hash_equals($this->ownerTokenDigest, app(MediaOwnerTokenBroker::class)->digest($this->ownerToken))) {
            abort(403);
        }

        app(MediaAuthorization::class)->authorizeOwner(
            $this->ownerToken,
            $this->actor(),
            expectedSlug: $this->fieldSlug,
        );
    }
}
