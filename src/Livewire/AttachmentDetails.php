<?php

namespace Aura\Base\Livewire;

use Illuminate\Support\Facades\Gate;
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

    public ?int $attachmentId = null;

    /**
     * Ordered ids of the currently listed attachments, for prev/next.
     *
     * @var array<int, int>
     */
    public array $rowIds = [];

    public string $surface = 'index';

    public string $title = '';

    public function close(): void
    {
        $this->attachmentId = null;

        $this->dispatch('attachment-details-closed');
    }

    public function deleteAttachment()
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

    public function next(): void
    {
        if ($id = $this->siblingId(1)) {
            $this->show($id);
        }
    }

    #[On('open-attachment-details')]
    public function open($id, $ids = []): void
    {
        $this->rowIds = array_map('intval', (array) $ids);

        $this->show((int) $id);
    }

    public function previous(): void
    {
        if ($id = $this->siblingId(-1)) {
            $this->show($id);
        }
    }

    public function render()
    {
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

    protected function attachment()
    {
        if (! $this->attachmentId) {
            return;
        }

        return app(config('aura.resources.attachment'))::find($this->attachmentId);
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
        $attachment = app(config('aura.resources.attachment'))::find($id);

        if (! $attachment) {
            $this->close();

            return;
        }

        Gate::authorize('view', $attachment);

        $this->attachmentId = $attachment->id;
        // Note: `?? ''` would silently yield '' here — Resource meta attributes
        // resolve through __get but do not implement __isset.
        $this->title = (string) $attachment->name;
        $this->altText = (string) $attachment->alt_text;

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
}
