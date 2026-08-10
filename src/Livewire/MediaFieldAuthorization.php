<?php

namespace Aura\Base\Livewire;

use Aura\Base\BaseResource;
use Aura\Base\Facades\Aura;
use Aura\Base\Fields\File;
use Aura\Base\Fields\Image;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;

final class MediaFieldAuthorization
{
    private const MAX_SELECTED_MEDIA = 500;

    /**
     * @param  array<int, mixed>  $selected
     * @return array{attachment: Model, field: array<string, mixed>, resource: BaseResource|resource, resource_slug: string, selected: list<string>}
     */
    public function authorizeField(
        string $resourceSlug,
        string $fieldSlug,
        array $selected = [],
        bool $authorizeCreate = false,
    ): array {
        $resource = $this->registeredResourceBySlug($resourceSlug);
        $field = $resource->fieldBySlug($fieldSlug);
        $fieldType = is_array($field) ? ($field['type'] ?? null) : null;

        if (
            ! is_array($field)
            || ($field['slug'] ?? null) !== $fieldSlug
            || ! is_string($fieldType)
            || (! is_a($fieldType, Image::class, true) && ! is_a($fieldType, File::class, true))
        ) {
            abort(422, 'The media manager resource field is invalid.');
        }

        Gate::authorize('viewAny', $resource);
        $attachment = $this->authorizeAttachment($authorizeCreate);
        $ids = $this->normalizeSelected($selected);

        if ($ids !== []) {
            $attachments = $attachment->newQuery()->whereKey($ids)->get();

            if ($attachments->count() !== count($ids)) {
                abort(422, 'The selected media are invalid.');
            }

            $attachments->each(static fn (Model $record) => Gate::authorize('view', $record));
        }

        return [
            'attachment' => $attachment,
            'field' => $field,
            'resource' => $resource,
            'resource_slug' => $resource->getSlug(),
            'selected' => $ids,
        ];
    }

    public function authorizeLibrary(bool $authorizeCreate = false): Model
    {
        return $this->authorizeAttachment($authorizeCreate);
    }

    public function normalizeResourceReference(?string $resource, ?string $legacyModel): string
    {
        if (($resource === null) === ($legacyModel === null)) {
            abort(422, 'The media manager resource is invalid.');
        }

        if ($legacyModel !== null) {
            $registeredClasses = Aura::getResources();

            if (! in_array($legacyModel, $registeredClasses, true)) {
                abort(422, 'The media manager resource is invalid.');
            }

            $registered = app($legacyModel);

            if (! $registered instanceof Resource && ! $registered instanceof BaseResource) {
                abort(422, 'The media manager resource is invalid.');
            }

            $slug = $registered->getSlug();

            if (! is_string($slug) || $slug === '') {
                abort(422, 'The media manager resource is invalid.');
            }

            return $slug;
        }

        return $this->registeredResourceBySlug($resource)->getSlug();
    }

    private function authorizeAttachment(bool $authorizeCreate): Model
    {
        $attachmentClass = config('aura.resources.attachment');

        if (! is_string($attachmentClass) || ! is_a($attachmentClass, Model::class, true)) {
            abort(422, 'The configured attachment resource is invalid.');
        }

        Gate::authorize('viewAny', $attachmentClass);

        if ($authorizeCreate) {
            Gate::authorize('create', $attachmentClass);
        }

        return app($attachmentClass);
    }

    /** @return list<string> */
    private function normalizeSelected(array $selected): array
    {
        if (count($selected) > self::MAX_SELECTED_MEDIA) {
            abort(422, 'The selected media are invalid.');
        }

        $normalized = [];

        foreach ($selected as $id) {
            if ((! is_int($id) && ! is_string($id)) || (string) $id === '') {
                abort(422, 'The selected media are invalid.');
            }

            $key = (string) $id;

            if (array_key_exists($key, $normalized)) {
                abort(422, 'The selected media are invalid.');
            }

            $normalized[$key] = $key;
        }

        return array_values($normalized);
    }

    private function registeredResourceBySlug(string $resourceSlug): BaseResource|Resource
    {
        $resource = Aura::findResourceBySlug($resourceSlug);

        if (
            (! $resource instanceof Resource && ! $resource instanceof BaseResource)
            || ! in_array($resource::class, Aura::getResources(), true)
            || $resource->getSlug() !== $resourceSlug
        ) {
            abort(422, 'The media manager resource field is invalid.');
        }

        return $resource;
    }
}
