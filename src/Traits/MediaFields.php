<?php

namespace Aura\Base\Traits;

use Aura\Base\Fields\File;
use Aura\Base\Fields\Image;
use Aura\Base\Livewire\Media\InvalidMediaSelectionRequest;
use Aura\Base\Livewire\Media\MediaAuthorization;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\Media\MediaSelectionBroker;
use Aura\Base\Livewire\Media\MediaSelectionMutation;
use Aura\Base\Resource;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;
use InvalidArgumentException;
use Livewire\Attributes\Locked;
use Livewire\Attributes\On;
use ReflectionClass;

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
                mutation: function () use ($ownerToken, $slug, $value, $actor): MediaSelectionMutation {
                    if (! isset($this->model) || ! $this->model instanceof Resource) {
                        throw new InvalidArgumentException('The media owner component has no Resource model.');
                    }

                    $field = $this->mediaFieldBySlug($slug);
                    $authorization = app(MediaAuthorization::class);
                    $authorization->authorizeOwnerSelection(
                        $ownerToken,
                        $value,
                        $actor,
                        $this->model::class,
                        $slug,
                        $field['type'],
                    );
                    $attachments = $authorization->authorizeAttachments($value, $actor);

                    $authorizedValue = $attachments
                        ->map(fn (Resource $attachment): string => (string) $attachment->getKey())
                        ->all();
                    $originalForm = $this->form;

                    return new MediaSelectionMutation(
                        apply: function () use ($slug, $authorizedValue): void {
                            $this->updateField([
                                'slug' => $slug,
                                'value' => $authorizedValue,
                            ]);
                        },
                        rollback: function () use ($originalForm): void {
                            $this->form = $originalForm;
                        },
                    );
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
        $field = $this->mediaFieldBySlug($slug);
        $broker = app(MediaOwnerTokenBroker::class);
        $token = $broker->issue(
            ownerComponentId: $this->getId(),
            modelClass: $modelClass,
            modelKey: $modelKey,
            action: $action,
            slug: $slug,
            fieldType: $field['type'],
            actor: $actor,
            ownerComponentClass: $this::class,
        );

        app(MediaAuthorization::class)->authorizeOwner($token, $actor, $modelClass, $slug, $field['type']);
        $this->mediaOwnerTokenDigests[$slug] = $broker->digest($token);

        return $token;
    }

    public function removeMediaFromField($slug, $id)
    {
        $this->mediaOwnerToken((string) $slug);
        $field = $this->getField($slug);

        $field = collect($field)->filter(function ($value) use ($id) {
            return $value != $id;
        })->values()->toArray();

        $authorized = $this->authorizedMediaForField($field)
            ->map(fn (Resource $attachment): string => (string) $attachment->getKey())
            ->all();

        $this->updateField([
            'slug' => $slug,
            'value' => $authorized,
        ]);

        // Emit Event selectedMediaUpdated
        $this->dispatch('selectedMediaUpdated', [
            'slug' => $slug,
            'value' => $authorized,
        ]);
    }

    public function reorderMedia($slug, $ids)
    {
        $this->mediaOwnerToken((string) $slug);
        $ids = collect($ids)->map(function ($id) {
            return Str::after($id, '_file_');
        })->toArray();
        $ids = $this->authorizedMediaForField($ids)
            ->map(fn (Resource $attachment): string => (string) $attachment->getKey())
            ->all();

        // emit update Field
        $this->updateField([
            'slug' => $slug,
            'value' => $ids,
        ]);
    }

    protected function updateField($data)
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

    /** @return array<string, mixed> */
    private function mediaFieldBySlug(string $slug): array
    {
        $field = method_exists($this, 'fieldBySlug')
            ? $this->fieldBySlug($slug)
            : $this->model->fieldBySlug($slug);
        $fieldType = is_array($field) ? ($field['type'] ?? null) : null;

        if (! is_array($field) || ($field['slug'] ?? null) !== $slug
            || ! is_string($fieldType) || ! class_exists($fieldType)
            || (! is_a($fieldType, Image::class, true) && ! is_a($fieldType, File::class, true))
            || (new ReflectionClass($fieldType))->getName() !== $fieldType) {
            throw new InvalidArgumentException('The media owner field must be an existing Image or File field.');
        }

        return $field;
    }
}
