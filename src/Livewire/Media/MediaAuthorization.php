<?php

namespace Aura\Base\Livewire\Media;

use Aura\Base\Aura;
use Aura\Base\Fields\File;
use Aura\Base\Fields\Image;
use Aura\Base\Resource;
use Aura\Base\Resources\Attachment;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use ReflectionClass;

class MediaAuthorization
{
    public function __construct(
        private readonly Aura $aura,
        private readonly Gate $gate,
        private readonly Guard $guard,
        private readonly MediaOwnerTokenBroker $owners,
    ) {}

    public function authorizeAttachmentCreate(Authenticatable $actor): Resource
    {
        $this->assertCurrentActor($actor);
        $attachment = $this->attachmentPrototype();
        $this->gate->forUser($actor)->authorize('create', $attachment);

        return $attachment;
    }

    /**
     * @param  list<int|string>  $ids
     * @return Collection<int, resource>
     */
    public function authorizeAttachments(array $ids, Authenticatable $actor): Collection
    {
        $this->assertCurrentActor($actor);
        $normalized = $this->normalizeIds($ids);
        $prototype = $this->attachmentPrototype();
        $actorGate = $this->gate->forUser($actor);
        $actorGate->authorize('viewAny', $prototype);

        if ($normalized === []) {
            return new Collection;
        }

        $attachmentClass = $prototype::class;
        $found = $attachmentClass::query()->whereKey($normalized)->get()
            ->keyBy(fn (Resource $attachment): string => (string) $attachment->getKey());

        if ($found->count() !== count($normalized)) {
            throw new InvalidMediaOwnerContext('One or more media attachments are unavailable.');
        }

        $ordered = new Collection;

        foreach ($normalized as $id) {
            $attachment = $found->get($id);

            if (! $attachment instanceof Resource) {
                throw new InvalidMediaOwnerContext('One or more media attachments are unavailable.');
            }

            $actorGate->authorize('view', $attachment);
            $ordered->push($attachment);
        }

        return $ordered;
    }

    /**
     * @param  class-string<resource>|null  $expectedModel
     */
    public function authorizeOwner(
        string $ownerToken,
        Authenticatable $actor,
        ?string $expectedModel = null,
        ?string $expectedSlug = null,
        ?string $expectedFieldType = null,
    ): AuthorizedMediaOwner {
        $this->assertCurrentActor($actor);
        $context = $this->owners->resolve($ownerToken, $actor);

        if (($expectedModel !== null && ! hash_equals($context->modelClass, $expectedModel))
            || ($expectedSlug !== null && ! hash_equals($context->slug, $expectedSlug))
            || ($expectedFieldType !== null && (! is_string($context->fieldType)
                || ! hash_equals($context->fieldType, $expectedFieldType)))
            || ! $this->isRegisteredResource($context->modelClass)) {
            throw new InvalidMediaOwnerContext('The media owner context does not match a registered resource field.');
        }

        $prototype = app($context->modelClass);

        if (! $prototype instanceof Resource || $prototype::class !== $context->modelClass) {
            throw new InvalidMediaOwnerContext('The media owner resource is unavailable.');
        }

        if ($context->action === 'library') {
            if ($context->modelClass !== $this->attachmentPrototype()::class
                || $context->slug !== '__library__' || $context->fieldType !== null) {
                throw new InvalidMediaOwnerContext('The media library owner context is invalid.');
            }

            $this->gate->forUser($actor)->authorize('viewAny', $prototype);

            return new AuthorizedMediaOwner($context, $prototype, ['slug' => '__library__']);
        }

        if (! $this->isMediaFieldType($context->fieldType)) {
            throw new InvalidMediaOwnerContext('The media owner field is unavailable.');
        }

        $field = $prototype->fieldBySlug($context->slug);

        if ($field !== null && (! is_array($field)
            || ($field['slug'] ?? null) !== $context->slug
            || ! is_string($field['type'] ?? null)
            || ! hash_equals($context->fieldType, $field['type']))) {
            throw new InvalidMediaOwnerContext('The media owner field is unavailable.');
        }

        $field ??= ['slug' => $context->slug, 'type' => $context->fieldType];

        $actorGate = $this->gate->forUser($actor);

        if ($context->action === 'create') {
            $actorGate->authorize('create', $prototype);

            return new AuthorizedMediaOwner($context, $prototype, $field);
        }

        $resource = $context->modelClass::query()->find($context->modelKey);

        if (! $resource instanceof Resource) {
            throw new InvalidMediaOwnerContext('The media owner record is unavailable.');
        }

        $actorGate->authorize('update', $resource);

        return new AuthorizedMediaOwner($context, $resource, $field);
    }

    private function assertCurrentActor(Authenticatable $actor): void
    {
        $current = $this->guard->user();

        if (! $current instanceof Authenticatable
            || (string) $current->getAuthIdentifier() !== (string) $actor->getAuthIdentifier()) {
            throw new InvalidMediaOwnerContext('The authenticated media actor changed.');
        }
    }

    private function attachmentPrototype(): Resource
    {
        $attachmentClass = config('aura.resources.attachment', Attachment::class);

        if (! is_string($attachmentClass) || ! class_exists($attachmentClass)
            || ! is_subclass_of($attachmentClass, Resource::class)
            || (new ReflectionClass($attachmentClass))->getName() !== ltrim($attachmentClass, '\\')) {
            throw new InvalidMediaOwnerContext('The configured attachment resource is invalid.');
        }

        $attachment = app($attachmentClass);

        if (! $attachment instanceof Resource) {
            throw new InvalidMediaOwnerContext('The configured attachment resource is unavailable.');
        }

        return $attachment;
    }

    private function isMediaFieldType(?string $fieldType): bool
    {
        return is_string($fieldType)
            && class_exists($fieldType)
            && (is_a($fieldType, Image::class, true) || is_a($fieldType, File::class, true))
            && (new ReflectionClass($fieldType))->getName() === $fieldType;
    }

    /** @param class-string<resource> $modelClass */
    private function isRegisteredResource(string $modelClass): bool
    {
        if (in_array($modelClass, $this->aura->getResources(), true)) {
            return true;
        }

        $configuredResources = config('aura.resources', []);

        if (is_array($configuredResources) && in_array($modelClass, $configuredResources, true)) {
            return true;
        }

        $resource = $this->aura->findResourceBySlug($modelClass);

        return $resource instanceof Resource && $resource::class === $modelClass;
    }

    /**
     * @param  list<int|string>  $ids
     * @return list<string>
     */
    private function normalizeIds(array $ids): array
    {
        if (! array_is_list($ids)) {
            throw new InvalidArgumentException('Media attachment IDs must be a list.');
        }

        $normalized = [];

        foreach ($ids as $id) {
            if ((! is_int($id) && ! is_string($id)) || (string) $id === '') {
                throw new InvalidArgumentException('Media attachment IDs must contain non-empty integer or string values.');
            }

            $normalized[] = (string) $id;
        }

        if (count($normalized) !== count(array_unique($normalized, SORT_STRING))) {
            throw new InvalidArgumentException('Media attachment IDs must not contain duplicates.');
        }

        return $normalized;
    }
}
