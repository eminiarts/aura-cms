<?php

namespace Aura\Base\Services;

use Aura\Base\Contracts\DefinesFields;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use JsonException;

final class EmbeddedComponentContextCodec
{
    private const ABILITIES = ['create', 'update', 'view'];

    private const VERSION = 1;

    public function __construct(
        private readonly EmbeddedComponentContextStore $store,
    ) {}

    public function authorize(array $context): Model
    {
        $payload = Arr::only($context, [
            'version',
            'resource_class',
            'resource_key',
            'ability',
            'surface',
            'field_slug',
            'component_alias',
        ]);

        abort_unless($this->isValidPayload($payload), 403);

        try {
            $expectedSignature = $this->signature($payload);
        } catch (JsonException) {
            abort(403);
        }

        $providedSignature = $context['signature'] ?? null;

        abort_unless(
            is_string($providedSignature) && hash_equals($expectedSignature, $providedSignature),
            403,
        );

        $resource = $this->store->find($providedSignature)
            ?? $this->restoreResource($payload);

        Gate::authorize($payload['ability'], $resource);

        return $resource;
    }

    /**
     * @return array<string, int|string|null>
     *
     * @throws JsonException
     */
    public function issue(
        Model $resource,
        string $ability,
        EmbeddedComponentSurface $surface,
        string $fieldSlug,
        string $componentAlias,
    ): array {
        $payload = [
            'version' => self::VERSION,
            'resource_class' => $resource::class,
            'resource_key' => $resource->getKey(),
            'ability' => $ability,
            'surface' => $surface->value,
            'field_slug' => $fieldSlug,
            'component_alias' => $componentAlias,
        ];

        $signature = $this->signature($payload);
        $this->store->remember($signature, $resource);

        return [...$payload, 'signature' => $signature];
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isValidPayload(array $payload): bool
    {
        if (count($payload) !== 7
            || ($payload['version'] ?? null) !== self::VERSION
            || ! is_string($payload['resource_class'] ?? null)
            || ! in_array($payload['ability'] ?? null, self::ABILITIES, true)
            || EmbeddedComponentSurface::tryFrom($payload['surface'] ?? '') === null
            || ! is_string($payload['field_slug'] ?? null)
            || ! is_string($payload['component_alias'] ?? null)
        ) {
            return false;
        }

        $resourceClass = $payload['resource_class'];

        return class_exists($resourceClass)
            && is_subclass_of($resourceClass, Model::class)
            && is_subclass_of($resourceClass, DefinesFields::class)
            && (is_int($payload['resource_key'])
                || is_string($payload['resource_key'])
                || $payload['resource_key'] === null);
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function restoreResource(array $payload): Model
    {
        $resourceClass = $payload['resource_class'];

        /** @var Model $resource */
        $resource = new $resourceClass;

        if ($payload['ability'] === 'create' && $payload['resource_key'] === null) {
            return $resource;
        }

        abort_if($payload['resource_key'] === null, 403);

        return $resource->newQuery()->findOrFail($payload['resource_key']);
    }

    /**
     * @param  array<string, mixed>  $payload
     *
     * @throws JsonException
     */
    private function signature(array $payload): string
    {
        $key = config('app.key');

        abort_unless(is_string($key) && $key !== '', 500);

        return hash_hmac(
            'sha256',
            json_encode($payload, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION),
            $key,
        );
    }
}
