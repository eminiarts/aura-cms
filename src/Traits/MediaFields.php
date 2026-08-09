<?php

namespace Aura\Base\Traits;

use Aura\Base\Livewire\Media\InvalidMediaSelectionRequest;
use Aura\Base\Livewire\Media\MediaAuthorization;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\Media\MediaSelectionBroker;
use Aura\Base\Resource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;

trait MediaFields
{
    /** @var array<string, string> */
    #[Locked]
    public array $mediaOwnerTokenDigests = [];

    #[On('aura-media-selection-requested')]
    public function applyMediaSelection(
        string $ownerToken,
        string $requestToken,
        string $slug,
        array $value,
    ): void {
        $expectedDigest = $this->mediaOwnerTokenDigests[$slug] ?? null;
        $actor = auth()->user();

        if (! is_string($expectedDigest) || ! $actor instanceof Authenticatable
            || ! hash_equals($expectedDigest, app(MediaOwnerTokenBroker::class)->digest($ownerToken))) {
            return;
        }

        try {
            $record = app(MediaSelectionBroker::class)->processForOwner(
                requestToken: $requestToken,
                ownerToken: $ownerToken,
                ownerComponentId: $this->getId(),
                slug: $slug,
                value: $value,
                actor: $actor,
                mutation: function () use ($ownerToken, $slug, $value, $actor): void {
                    if (! isset($this->model) || ! $this->model instanceof Resource) {
                        throw new InvalidArgumentException('The media owner component has no Resource model.');
                    }

                    $authorization = app(MediaAuthorization::class);
                    $authorization->authorizeOwner($ownerToken, $actor, $this->model::class, $slug);
                    $attachments = $authorization->authorizeAttachments($value, $actor);

                    $this->updateField([
                        'slug' => $slug,
                        'value' => $attachments
                            ->map(fn (Resource $attachment): string => (string) $attachment->getKey())
                            ->all(),
                    ]);
                },
            );
        } catch (InvalidArgumentException|InvalidMediaSelectionRequest) {
            return;
        }

        $outcome = $record->state === 'succeeded' ? 'succeeded' : 'failed';
        $errorCode = $outcome === 'succeeded' ? null : ($record->errorCode ?? 'selection_rejected');

        $this->dispatch(
            'aura-media-selection-acknowledged',
            ownerToken: $ownerToken,
            requestToken: $requestToken,
            outcome: $outcome,
            errorCode: $errorCode,
        );
    }

    /**
     * @param  list<int|string>  $ids
     * @return Collection<int, resource>
     */
    public function authorizedMediaForField(array $ids): Collection
    {
        $actor = auth()->user();

        if (! $actor instanceof Authenticatable) {
            abort(403);
        }

        return app(MediaAuthorization::class)->authorizeAttachments($ids, $actor);
    }

    public function getField($slug)
    {
        return $this->form['fields'][$slug];
    }

    public function mediaOwnerToken(string $slug): string
    {
        $actor = auth()->user();

        if (! $actor instanceof Authenticatable || ! isset($this->model) || ! $this->model instanceof Resource) {
            abort(403);
        }

        $modelClass = $this->model::class;
        $modelKey = $this->model->exists ? (string) $this->model->getKey() : null;
        $action = $this->model->exists ? 'update' : 'create';
        $broker = app(MediaOwnerTokenBroker::class);
        $token = $broker->issue(
            ownerComponentId: $this->getId(),
            modelClass: $modelClass,
            modelKey: $modelKey,
            action: $action,
            slug: $slug,
            actor: $actor,
        );

        app(MediaAuthorization::class)->authorizeOwner($token, $actor, $modelClass, $slug);
        $this->mediaOwnerTokenDigests[$slug] = $broker->digest($token);

        return $token;
    }

    public function removeMediaFromField($slug, $id)
    {
        $field = $this->getField($slug);

        $field = collect($field)->filter(function ($value) use ($id) {
            return $value != $id;
        })->values()->toArray();

        $this->updateField([
            'slug' => $slug,
            'value' => $field,
        ]);

        // Emit Event selectedMediaUpdated
        $this->dispatch('selectedMediaUpdated', [
            'slug' => $slug,
            'value' => $field,
        ]);
    }

    public function reorderMedia($slug, $ids)
    {
        $ids = collect($ids)->map(function ($id) {
            return Str::after($id, '_file_');
        })->toArray();

        // emit update Field
        $this->updateField([
            'slug' => $slug,
            'value' => $ids,
        ]);
    }

    #[On('updateField')]
    public function updateField($data)
    {
        $this->form['fields'][$data['slug']] = $data['value'];

        $this->dispatch('fieldUpdated', [
            'slug' => $data['slug'],
            'value' => $data['value'],
        ]);

        $this->dispatch('selectedMediaUpdated', [
            'slug' => $data['slug'],
            'value' => $data['value'],
        ]);
    }
}
