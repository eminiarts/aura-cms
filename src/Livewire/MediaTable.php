<?php

namespace Aura\Base\Livewire;

use Aura\Base\Livewire\Media\MediaAuthorization;
use Aura\Base\Livewire\Media\MediaDetailsBroker;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\Table\Table;
use Aura\Base\Livewire\Table\TableMutationDispatcher;
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
    private const MAX_ALL_ROW_SELECTION = 1000;

    #[Locked]
    public ?string $detailsComponentId = null;

    #[Locked]
    public $field;

    #[Locked]
    public ?string $legacyResource = null;

    #[Locked]
    public $model;

    #[Locked]
    public string $ownerToken = '';

    #[Locked]
    public string $ownerTokenDigest = '';

    #[Locked]
    public $query;

    #[Locked]
    public bool $usesLegacyFieldAuthorization = false;

    public function action(array $data, TableMutationDispatcher $mutations): mixed
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

            return redirect()->route('aura.'.$attachment->getSlug().'.view', [$attachment]);
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
        $maximumFiles = is_array($this->field) ? (int) ($this->field['max_files'] ?? 0) : 0;
        $limit = $maximumFiles > 0 ? min($maximumFiles, self::MAX_ALL_ROW_SELECTION) : self::MAX_ALL_ROW_SELECTION;
        $ids = [];

        foreach ($this->query()->lazyById(100) as $attachment) {
            if (! $attachment instanceof Resource) {
                abort(403);
            }

            if ($this->visibleRows(new Collection([$attachment]))->isEmpty()) {
                continue;
            }

            $ids[] = $attachment->getKey();

            if (count($ids) >= $limit) {
                break;
            }
        }

        return $ids;
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
        if (is_array($this->field) && (! is_string($this->detailsComponentId) || $this->detailsComponentId === '')) {
            $this->detailsComponentId = $this->getId();
        }

        $this->ownerTokenDigest = app(MediaOwnerTokenBroker::class)->digest($this->ownerToken);
        $this->authorizeContext();
        parent::mount();
    }

    public function openAttachmentDetails(int|string $id): void
    {
        $actor = $this->actor();
        $owner = app(MediaAuthorization::class)->authorizeOwner($this->ownerToken, $actor);
        $attachment = $this->authorizedAttachment($id);
        $rowIds = (new Collection($this->rows()->items()))
            ->map(fn (Resource $row): string => (string) $row->getKey())
            ->all();
        app(MediaAuthorization::class)->authorizeAttachments($rowIds, $actor);
        app(MediaAuthorization::class)->authorizeAttachments((array) $this->selected, $actor);

        if (! is_string($this->detailsComponentId) || $this->detailsComponentId === '') {
            abort(403);
        }

        $detailsToken = app(MediaDetailsBroker::class)->issue(
            ownerToken: $this->ownerToken,
            componentId: $this->detailsComponentId,
            fieldSlug: $owner->context->slug,
            attachmentId: (string) $attachment->getKey(),
            rowIds: $rowIds,
            selectionIds: (array) $this->selected,
            actor: $actor,
        );

        $this->dispatch('open-attachment-details', detailsToken: $detailsToken);
    }

    public function render(): View
    {
        $this->authorizeContext();

        return parent::render();
    }

    public function selectFieldRows($value, $slug): void
    {
        $this->authorizeSelectionLimit((array) $value);
        app(MediaAuthorization::class)->authorizeAttachments((array) $value, $this->actor());
        parent::selectFieldRows($value, $slug);
    }

    public function selectRow($id): void
    {
        $this->authorizedAttachment($id);
        parent::selectRow($id);
    }

    public function updateCardStatus(
        mixed $cardId,
        mixed $newStatus,
        TableMutationDispatcher $mutations,
    ): void {
        abort(403, 'Media records cannot be mutated through the Kanban endpoint.');
    }

    public function updatedSelected(): void
    {
        $this->authorizeSelectionLimit((array) $this->selected);
        app(MediaAuthorization::class)->authorizeAttachments((array) $this->selected, $this->actor());
        parent::updatedSelected();
    }

    protected function query()
    {
        $this->authorizeContext();

        return app(MediaAuthorization::class)->applyAttachmentVisibility(parent::query(), $this->actor());
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

        if ($this->usesLegacyFieldAuthorization) {
            if ($owner->context->action !== 'library'
                || ! is_string($this->legacyResource)
                || ! is_array($this->field)
                || ! is_string($this->field['slug'] ?? null)) {
                abort(403);
            }

            $authorized = app(MediaFieldAuthorization::class)->authorizeField(
                $this->legacyResource,
                $this->field['slug'],
                (array) $this->selected,
            );

            if (($authorized['field']['type'] ?? null) !== ($this->field['type'] ?? null)) {
                abort(403);
            }

            return;
        }

        if ($owner->context->action === 'library') {
            if ($this->field !== null) {
                abort(403);
            }

            return;
        }

        if (! is_array($this->field)
            || ($this->field['slug'] ?? null) !== $owner->field['slug']
            || ($this->field['type'] ?? null) !== $owner->field['type']
            || ! is_string($this->detailsComponentId)
            || $this->detailsComponentId === '') {
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

    /** @param list<int|string> $ids */
    private function authorizeSelectionLimit(array $ids): void
    {
        $owner = app(MediaAuthorization::class)->authorizeOwner($this->ownerToken, $this->actor());

        if ($owner->context->action !== 'library') {
            app(MediaAuthorization::class)->authorizeOwnerSelection(
                $this->ownerToken,
                $ids,
                $this->actor(),
                expectedSlug: $owner->context->slug,
            );
        }
    }

    /** @param Collection<int, resource> $attachments */
    private function visibleRows(Collection $attachments): Collection
    {
        return app(MediaAuthorization::class)->visibleAttachments($attachments, $this->actor());
    }
}
