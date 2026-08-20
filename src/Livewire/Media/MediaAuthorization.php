<?php

namespace Aura\Base\Livewire\Media;

use Aura\Base\Contracts\ScopesMediaVisibility;
use Aura\Base\Resource;
use Aura\Base\Resources\Attachment;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Auth\Guard;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use InvalidArgumentException;
use ReflectionClass;

/**
 * Lightweight media authorization for attachment visibility and selection.
 *
 * Intentionally excludes owner-token brokers, selection state machines, and
 * cache/PDO identity fortresses from the experimental mega-branch.
 */
class MediaAuthorization
{
    public function __construct(
        private readonly Gate $gate,
        private readonly Guard $guard,
    ) {}

    public function applyAttachmentVisibility(Builder $query, Authenticatable $actor): Builder
    {
        $this->assertCurrentActor($actor);
        $prototype = $this->attachmentPrototype();
        $actorGate = $this->gate->forUser($actor);
        $actorGate->authorize('viewAny', $prototype);
        $policy = $this->gate->getPolicyFor($prototype);

        if (! $policy instanceof ScopesMediaVisibility) {
            return $query->whereRaw('1 = 0');
        }

        return $policy->scopeMediaVisibility($query, $actor, $prototype);
    }

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

        if ($normalized === []) {
            $actorGate->authorize('viewAny', $prototype);

            return new Collection;
        }

        $attachmentClass = $prototype::class;
        $query = $this->applyAttachmentVisibility($attachmentClass::query(), $actor);
        $found = $query->whereKey($normalized)->get()
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
     * @param  Collection<int, resource>  $attachments
     * @return Collection<int, resource>
     */
    public function visibleAttachments(Collection $attachments, Authenticatable $actor): Collection
    {
        $this->assertCurrentActor($actor);
        $prototype = $this->attachmentPrototype();
        $actorGate = $this->gate->forUser($actor);
        $actorGate->authorize('viewAny', $prototype);

        return $attachments
            ->filter(function ($attachment) use ($actorGate, $prototype): bool {
                if (! $attachment instanceof Resource || $attachment::class !== $prototype::class) {
                    throw new InvalidMediaOwnerContext('The media attachment listing is invalid.');
                }

                return $actorGate->allows('view', $attachment);
            })
            ->values();
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
