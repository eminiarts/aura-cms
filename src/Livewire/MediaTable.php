<?php

namespace Aura\Base\Livewire;

use Aura\Base\Livewire\Media\MediaAuthorization;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Resource;
use Aura\Base\Resources\Attachment;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;
use Livewire\Attributes\Computed;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

class MediaTable extends Table
{
    #[Locked]
    public $field;

    #[Locked]
    public $model;

    #[Locked]
    public string $ownerToken = '';

    #[Locked]
    public string $ownerTokenDigest = '';

    #[Locked]
    public $query;

    public function action($data)
    {
        if (! is_array($data)
            || ! is_string($data['action'] ?? null)
            || (! is_int($data['id'] ?? null) && ! is_string($data['id'] ?? null))) {
            abort(403, 'This media action is not allowed.');
        }

        $action = $data['action'];
        $attachment = $this->authorizedAttachment($data['id']);

        if ($action === 'view') {
            Gate::authorize('view', $attachment);

            return redirect()->route('aura.'.$attachment->getSlug().'.view', ['id' => $attachment->getKey()]);
        }

        if ($action === 'edit') {
            Gate::authorize('update', $attachment);

            return redirect()->route('aura.'.$attachment->getSlug().'.edit', ['id' => $attachment->getKey()]);
        }

        if (! array_key_exists($action, (array) $attachment->getActions()) || ! method_exists($attachment, $action)) {
            abort(403, 'This media action is not allowed.');
        }

        Gate::authorize($this->actionAbility($action), $attachment);

        return $attachment->{$action}();
    }

    public function allTableRows(): array
    {
        return $this->visibleRows($this->query()->get())->modelKeys();
    }

    public function getAllTableRows(): array
    {
        return $this->allTableRows();
    }

    public function hydrate(): void
    {
        $this->authorizeContext();
    }

    #[On('media-uploaded')]
    public function mediaUploaded(mixed $ids = [], mixed $ownerToken = null): void
    {
        $this->authorizeContext();

        if (! is_array($ids)
            || ! is_string($ownerToken)
            || ! hash_equals($this->ownerTokenDigest, app(MediaOwnerTokenBroker::class)->digest($ownerToken))) {
            return;
        }

        app(MediaAuthorization::class)->authorizeAttachments($ids, $this->actor());
        parent::mediaUploaded($ids);
    }

    public function mount(): void
    {
        $this->ownerTokenDigest = app(MediaOwnerTokenBroker::class)->digest($this->ownerToken);
        $this->authorizeContext();
        parent::mount();
    }

    public function render(): View
    {
        $this->authorizeContext();

        return parent::render();
    }

    public function selectFieldRows($value, $slug): void
    {
        app(MediaAuthorization::class)->authorizeAttachments((array) $value, $this->actor());
        parent::selectFieldRows($value, $slug);
    }

    public function selectRow($id): void
    {
        $this->authorizedAttachment($id);
        parent::selectRow($id);
    }

    public function updateCardStatus($cardId, $newStatus): never
    {
        abort(403, 'Media records cannot be mutated through the Kanban endpoint.');
    }

    public function updatedSelected(): void
    {
        app(MediaAuthorization::class)->authorizeAttachments((array) $this->selected, $this->actor());
        parent::updatedSelected();
    }

    protected function query()
    {
        $this->authorizeContext();

        return parent::query();
    }

    #[Computed]
    protected function rows(): LengthAwarePaginator
    {
        $rows = parent::rows();
        $rows->setCollection($this->visibleRows($rows->getCollection()));

        return $rows;
    }

    private function actionAbility(string $action): string
    {
        $normalized = strtolower($action);

        if (str_contains($normalized, 'forcedelete')) {
            return 'forceDelete';
        }

        if (str_contains($normalized, 'restore')) {
            return 'restore';
        }

        if (str_contains($normalized, 'delete') || str_contains($normalized, 'trash')) {
            return 'delete';
        }

        return 'update';
    }

    private function actor(): Authenticatable
    {
        $actor = auth()->user();

        if (! $actor instanceof Authenticatable) {
            abort(403);
        }

        return $actor;
    }

    private function authorizeContext(): void
    {
        $actor = $this->actor();
        $broker = app(MediaOwnerTokenBroker::class);

        if (! hash_equals($this->ownerTokenDigest, $broker->digest($this->ownerToken))) {
            abort(403);
        }

        $owner = app(MediaAuthorization::class)->authorizeOwner($this->ownerToken, $actor);
        $attachmentClass = ltrim((string) config('aura.resources.attachment', Attachment::class), '\\');

        if (! $this->model instanceof Resource || $attachmentClass !== $this->model::class || $this->query !== null) {
            abort(403);
        }

        if ($owner->context->action === 'library') {
            if ($this->field !== null) {
                abort(403);
            }

            return;
        }

        if (! is_array($this->field)
            || ($this->field['slug'] ?? null) !== $owner->field['slug']
            || ($this->field['type'] ?? null) !== $owner->field['type']) {
            abort(403);
        }
    }

    private function authorizedAttachment(int|string $id): Resource
    {
        $attachment = app(MediaAuthorization::class)
            ->authorizeAttachments([$id], $this->actor())
            ->first();

        if (! $attachment instanceof Resource) {
            abort(403);
        }

        return $attachment;
    }

    /** @param Collection<int, resource> $attachments */
    private function visibleRows(Collection $attachments): Collection
    {
        return app(MediaAuthorization::class)->visibleAttachments($attachments, $this->actor());
    }
}
