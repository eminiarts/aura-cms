<?php

namespace Aura\Base\ResourcePersistence;

use Aura\Base\Contracts\FieldValueContext;
use Aura\Base\Resource;
use Illuminate\Support\Facades\Gate;
use LogicException;

final class ResourceWriter
{
    public function __construct(private readonly ResourceWriteSchema $schema) {}

    /** @param array<string, mixed> $fields */
    public function create(Resource $prototype, array $fields): Resource
    {
        Gate::authorize('create', $prototype);

        $resource = $prototype->newInstance($prototype->getAttributes());
        $resource->setConnection($prototype->getConnectionName());

        return $this->saveWithFields($resource, $fields, FieldValueContext::Create);
    }

    /** @param array<string, mixed> $fields */
    public function createGlobal(Resource $prototype, array $fields): Resource
    {
        $validated = $this->schema->validate($prototype, $fields, FieldValueContext::Create);
        $attributes = $prototype->usesCustomTable() ? $validated : ['fields' => $validated];

        return $prototype::createGlobal($attributes, $prototype->getConnection());
    }

    /** @param array<string, mixed> $fields */
    public function moveGlobalToTeam(Resource $resource, int|string $teamId, array $fields): Resource
    {
        $validated = $this->schema->validate($resource, $fields, FieldValueContext::Edit);
        $attributes = $resource->usesCustomTable() ? $validated : ['fields' => $validated];

        if (! $resource->moveGlobalToTeam((int) $teamId, $attributes)) {
            throw new LogicException('The global resource could not be moved to the selected team.');
        }

        return $resource->refresh();
    }

    /** @param array<string, mixed> $fields */
    public function promoteToGlobal(Resource $resource, array $fields): Resource
    {
        $validated = $this->schema->validate($resource, $fields, FieldValueContext::Edit);
        $attributes = $resource->usesCustomTable() ? $validated : ['fields' => $validated];

        if (! $resource->promoteToGlobal($attributes)) {
            throw new LogicException('The resource could not be promoted globally.');
        }

        return $resource->refresh();
    }

    /**
     * Explicitly preserve Aura field normalization and meta persistence when
     * callers intentionally suppress Eloquent model events.
     *
     * @param  array<string, mixed>  $fields
     */
    public function saveWithFields(Resource $resource, array $fields, FieldValueContext $context): Resource
    {
        $validated = $this->schema->validate($resource, $fields, $context);

        return $resource->getConnection()->transaction(function () use ($resource, $validated): Resource {
            $resource->prepareFieldsForExplicitWrite($validated);
            $eventsMuted = $resource->resourceModelEventsAreMuted();

            if (! $resource->save()) {
                throw new LogicException('The resource write was vetoed.');
            }

            if ($eventsMuted) {
                $resource->persistPreparedMetaFields();
            }

            return $resource->refresh();
        });
    }

    /** @param array<string, mixed> $fields */
    public function update(Resource $resource, array $fields): Resource
    {
        Gate::authorize('update', $resource);

        return $this->saveWithFields($resource, $fields, FieldValueContext::Edit);
    }
}
