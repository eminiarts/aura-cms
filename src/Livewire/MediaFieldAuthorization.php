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
     * @param  array<int, array<string, mixed>>  $fields
     */
    public function authorizeDeclaredField(array $fields, string $fieldSlug, mixed $selected): void
    {
        $field = collect($fields)->first(
            static fn (mixed $field): bool => is_array($field)
                && ($field['slug'] ?? null) === $fieldSlug,
        );

        if (! is_array($field) || ! $this->isMediaField($field)) {
            abort(422, 'The media field is invalid.');
        }

        $ids = $this->normalizeDeclaredSelection($selected);

        if ($ids === []) {
            return;
        }

        $attachment = $this->authorizeAttachment(false);
        $this->authorizeSelectedAttachments($attachment, $ids);
    }

    /**
     * @param  array<int, array<string, mixed>>  $fields
     * @param  array<string, mixed>  $values
     */
    public function authorizeDeclaredFields(array $fields, array $values): void
    {
        $selected = [];

        foreach ($fields as $field) {
            if (! is_array($field) || ! $this->isMediaField($field)) {
                continue;
            }

            $fieldSlug = $field['slug'] ?? null;

            if (! is_string($fieldSlug) || $fieldSlug === '') {
                abort(422, 'The media field is invalid.');
            }

            foreach ($this->normalizeDeclaredSelection($values[$fieldSlug] ?? null) as $id) {
                $selected[$id] = $id;
            }
        }

        if ($selected === []) {
            return;
        }

        $attachment = $this->authorizeAttachment(false);
        $this->authorizeSelectedAttachments($attachment, array_values($selected));
    }

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
        if (
            ! is_array($field)
            || ($field['slug'] ?? null) !== $fieldSlug
            || ! $this->isMediaField($field)
        ) {
            abort(422, 'The media manager resource field is invalid.');
        }

        Gate::authorize('viewAny', $resource);
        $attachment = $this->authorizeAttachment($authorizeCreate);
        $ids = $this->normalizeSelected($selected);

        $this->authorizeSelectedAttachments($attachment, $ids);

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

    /** @param list<string> $ids */
    private function authorizeSelectedAttachments(Model $attachment, array $ids): void
    {
        if ($ids === []) {
            return;
        }

        $attachments = $attachment->newQuery()->whereKey($ids)->get();

        if ($attachments->count() !== count($ids)) {
            abort(422, 'The selected media are invalid.');
        }

        $attachments->each(static fn (Model $record) => Gate::authorize('view', $record));
    }

    /** @param array<string, mixed> $field */
    private function isMediaField(array $field): bool
    {
        $fieldType = $field['type'] ?? null;

        return is_string($fieldType)
            && (is_a($fieldType, Image::class, true) || is_a($fieldType, File::class, true));
    }

    /** @return list<string> */
    private function normalizeDeclaredSelection(mixed $selected): array
    {
        if ($selected === null || $selected === '') {
            return [];
        }

        if (is_int($selected) || is_string($selected)) {
            return $this->normalizeSelected([$selected]);
        }

        if (! is_array($selected)) {
            abort(422, 'The selected media are invalid.');
        }

        return $this->normalizeSelected($selected);
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
