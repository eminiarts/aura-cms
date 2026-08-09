<?php

namespace Aura\Base\Livewire;

use Aura\Base\Livewire\Media\InvalidMediaSelectionRequest;
use Aura\Base\Livewire\Media\MediaAuthorization;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\Media\MediaSelectionBroker;
use Aura\Base\Livewire\Media\MediaSelectionRecord;
use Aura\Base\Resource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\View\View;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use Livewire\Component;

class MediaManager extends Component
{
    #[Locked]
    public array $field = [];

    #[Locked]
    public string $fieldSlug = '';

    public bool $initialSelectionDone = false;

    /** @var array{persistent: bool, modalClasses: string, slideOver: bool} */
    #[Locked]
    public array $modalAttributes = [
        'persistent' => false,
        'modalClasses' => 'max-w-7xl',
        'slideOver' => false,
    ];

    #[Locked]
    public string $model = '';

    #[Locked]
    public string $ownerToken = '';

    #[Locked]
    public string $ownerTokenDigest = '';

    #[Locked]
    public bool $pending = false;

    #[Locked]
    public ?string $pendingRequestDigest = null;

    #[Locked]
    public ?int $pendingSince = null;

    public array $rowIds = [];

    public ?array $selected = null;

    public ?string $selectionError = null;

    #[Locked]
    public ?string $settledRequestDigest = null;

    #[Locked]
    public string $slug = '';

    #[On('aura-media-selection-acknowledged')]
    public function acknowledgeMediaSelection(
        string $ownerToken,
        string $requestToken,
        string $outcome,
        ?string $errorCode = null,
    ): void {
        if (! $this->matchesPendingTokens($ownerToken, $requestToken)) {
            return;
        }

        $actor = $this->authorizeContext();

        try {
            $record = app(MediaSelectionBroker::class)->forManager(
                $requestToken,
                $ownerToken,
                $this->getId(),
                $actor,
            );
        } catch (InvalidMediaSelectionRequest) {
            return;
        }

        if ($record->state === 'succeeded') {
            if ($outcome !== 'succeeded' || $errorCode !== null || $record->errorCode !== null) {
                return;
            }

            $this->settleSuccessfulRequest($record);

            return;
        }

        if (! in_array($record->state, ['failed', 'expired'], true)
            || $outcome !== 'failed'
            || $errorCode !== $record->errorCode) {
            return;
        }

        $this->settleFailedRequest($record);
    }

    public function expireMediaSelection(string $requestToken): void
    {
        if (! $this->pending || ! is_string($this->pendingRequestDigest)
            || ! hash_equals($this->pendingRequestDigest, hash('sha256', $requestToken))) {
            return;
        }

        $actor = $this->authorizeContext();

        try {
            $record = app(MediaSelectionBroker::class)->expireForManager(
                $requestToken,
                $this->ownerToken,
                $this->getId(),
                $actor,
            );
        } catch (InvalidMediaSelectionRequest) {
            return;
        }

        if ($record->state === 'succeeded') {
            $this->settleSuccessfulRequest($record);
        } elseif (in_array($record->state, ['failed', 'expired'], true)) {
            $this->settleFailedRequest($record);
        }
    }

    public function hydrate(): void
    {
        $this->authorizeContext();
    }

    public static function modalClasses(): string
    {
        return 'max-w-7xl';
    }

    public function mount(
        string $model,
        string $slug,
        ?array $selected,
        string $ownerToken,
        array $modalAttributes,
    ): void {
        $this->model = $model;
        $this->slug = $slug;
        $this->fieldSlug = $slug;
        $this->ownerToken = $ownerToken;
        $this->modalAttributes = $modalAttributes;

        $actor = $this->actor();
        $owner = app(MediaAuthorization::class)->authorizeOwner($ownerToken, $actor, $model, $slug);
        $this->field = $owner->field;
        $this->ownerTokenDigest = app(MediaOwnerTokenBroker::class)->digest($ownerToken);
        $this->selected = app(MediaAuthorization::class)
            ->authorizeAttachments($selected ?? [], $actor)
            ->map(fn (Resource $attachment): string => (string) $attachment->getKey())
            ->all();
    }

    public function render(): View
    {
        $this->authorizeContext();

        return view('aura::livewire.media-manager');
    }

    public function requestMediaSelection(array $value): void
    {
        if ($this->pending) {
            return;
        }

        $actor = $this->authorizeContext();
        $selected = app(MediaAuthorization::class)->authorizeAttachments($value, $actor)
            ->map(fn (Resource $attachment): string => (string) $attachment->getKey())
            ->all();
        $request = app(MediaSelectionBroker::class)->begin(
            ownerToken: $this->ownerToken,
            managerComponentId: $this->getId(),
            value: $selected,
            actor: $actor,
        );

        $this->selected = $selected;
        $this->pending = true;
        $this->pendingRequestDigest = $request->digest;
        $this->pendingSince = $request->record->issuedAt;
        $this->selectionError = null;
        $this->settledRequestDigest = null;

        $this->dispatch(
            'aura-media-selection-requested',
            ownerToken: $this->ownerToken,
            requestToken: $request->token,
            slug: $this->slug,
            value: $selected,
        );
        $this->dispatch(
            'aura-media-selection-timer-started',
            requestToken: $request->token,
            timeoutMilliseconds: max(1000, ($request->record->deadline - now()->getTimestamp()) * 1000),
        );
    }

    /** @deprecated Use requestMediaSelection(). */
    public function select($selectedValues = null): void
    {
        $this->requestMediaSelection(is_array($selectedValues) ? $selectedValues : ($this->selected ?? []));
    }

    #[On('selectedRows')]
    public function selectAttachment($ids): void
    {
        if (! $this->initialSelectionDone && is_array($ids)) {
            $this->selected = collect($ids)->map(fn ($id): string => (string) $id)->values()->toArray();
            $this->initialSelectionDone = true;
        }
    }

    #[On('tableMounted')]
    public function tableMounted(): void
    {
        if ($this->selected && ! $this->initialSelectionDone) {
            $this->dispatch('selectedRows', collect($this->selected)->map(fn ($id): string => (string) $id)->values()->toArray());
            $this->initialSelectionDone = true;
        }
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
        app(MediaAuthorization::class)->authorizeOwner(
            $this->ownerToken,
            $actor,
            $this->model,
            $this->slug,
        );
        app(MediaAuthorization::class)->authorizeAttachments([], $actor);

        if (! hash_equals($this->ownerTokenDigest, app(MediaOwnerTokenBroker::class)->digest($this->ownerToken))) {
            abort(403);
        }

        return $actor;
    }

    private function matchesPendingTokens(string $ownerToken, string $requestToken): bool
    {
        return $this->pending
            && is_string($this->pendingRequestDigest)
            && hash_equals($this->ownerTokenDigest, app(MediaOwnerTokenBroker::class)->digest($ownerToken))
            && hash_equals($this->pendingRequestDigest, hash('sha256', $requestToken));
    }

    private function settleFailedRequest(MediaSelectionRecord $record): void
    {
        $this->pending = false;
        $this->pendingRequestDigest = null;
        $this->pendingSince = null;
        $this->selectionError = $record->errorCode ?? 'selection_rejected';
        $this->settledRequestDigest = $record->requestDigest;
    }

    private function settleSuccessfulRequest(MediaSelectionRecord $record): void
    {
        if ($this->settledRequestDigest !== null && hash_equals($this->settledRequestDigest, $record->requestDigest)) {
            return;
        }

        $this->pending = false;
        $this->pendingRequestDigest = null;
        $this->pendingSince = null;
        $this->selectionError = null;
        $this->settledRequestDigest = $record->requestDigest;
        $this->dispatch('closeModal');
    }
}
