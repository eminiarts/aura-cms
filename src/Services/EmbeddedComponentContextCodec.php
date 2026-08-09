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

    private const MAX_PARAMETER_DEPTH = 10;

    private const PAYLOAD_KEYS = [
        'version',
        'resource_class',
        'resource_key',
        'persisted',
        'resource_fingerprint_keys',
        'resource_fingerprint',
        'ability',
        'surface',
        'field_slug',
        'component_alias',
        'parameters',
    ];

    private const VERSION = 2;

    public function __construct(
        private readonly EmbeddedComponentContextStore $store,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function authorize(array $context, bool $fresh = false): AuthorizedEmbeddedComponentContext
    {
        abort_unless($this->hasExactContextKeys($context), 403);

        $payload = Arr::only($context, self::PAYLOAD_KEYS);

        abort_unless($this->isValidPayload($payload), 403);

        try {
            $expectedSignature = $this->signature($payload);
        } catch (JsonException) {
            abort(403);
        }

        $providedSignature = $context['signature'];

        abort_unless(hash_equals($expectedSignature, $providedSignature), 403);

        $resource = $fresh ? null : $this->store->find($providedSignature);
        $resource ??= $this->restoreResource($payload);

        abort_unless($this->resourceMatchesPayload($resource, $payload), 403);

        Gate::authorize($payload['ability'], $resource);

        return new AuthorizedEmbeddedComponentContext(
            surface: EmbeddedComponentSurface::from($payload['surface']),
            resource: $resource,
            fieldSlug: $payload['field_slug'],
            componentAlias: $payload['component_alias'],
            parameters: $payload['parameters'],
        );
    }

    /**
     * @param  array<string, mixed>  $parameters
     * @return array<string, mixed>
     *
     * @throws JsonException
     */
    public function issue(
        Model $resource,
        string $ability,
        EmbeddedComponentSurface $surface,
        string $fieldSlug,
        string $componentAlias,
        array $parameters,
    ): array {
        $persisted = $resource->exists || $resource->wasRecentlyCreated;
        $resourceFingerprintKeys = $persisted ? $this->resourceFingerprintKeys($resource) : [];
        $payload = [
            'version' => self::VERSION,
            'resource_class' => $resource::class,
            'resource_key' => $resource->getKey(),
            'persisted' => $persisted,
            'resource_fingerprint_keys' => $resourceFingerprintKeys,
            'resource_fingerprint' => $persisted
                ? $this->resourceFingerprint($resource, $resourceFingerprintKeys)
                : null,
            'ability' => $ability,
            'surface' => $surface->value,
            'field_slug' => $fieldSlug,
            'component_alias' => $componentAlias,
            'parameters' => $parameters,
        ];

        abort_unless($this->isValidPayload($payload), 500);

        $signature = $this->signature($payload);
        $this->store->remember($signature, $resource);

        return [...$payload, 'signature' => $signature];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function hasExactContextKeys(array $context): bool
    {
        $actualKeys = array_keys($context);
        $expectedKeys = [...self::PAYLOAD_KEYS, 'signature'];

        sort($actualKeys);
        sort($expectedKeys);

        return $actualKeys === $expectedKeys
            && is_string($context['signature'] ?? null);
    }

    private function hasOnlySerializableValues(mixed $value, int $depth = 0): bool
    {
        if ($depth > self::MAX_PARAMETER_DEPTH) {
            return false;
        }

        if ($value === null || is_string($value) || is_int($value) || is_bool($value)) {
            return true;
        }

        if (is_float($value)) {
            return is_finite($value);
        }

        if (! is_array($value)) {
            return false;
        }

        foreach ($value as $key => $nestedValue) {
            if ((! is_int($key) && ! is_string($key))
                || ! $this->hasOnlySerializableValues($nestedValue, $depth + 1)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<int|string, mixed>  $keys
     */
    private function hasValidFingerprintKeys(array $keys): bool
    {
        if (! array_is_list($keys)) {
            return false;
        }

        foreach ($keys as $key) {
            if (! is_string($key) || $key === '') {
                return false;
            }
        }

        return count($keys) === count(array_unique($keys));
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isValidPayload(array $payload): bool
    {
        if (array_keys($payload) !== self::PAYLOAD_KEYS
            || ($payload['version'] ?? null) !== self::VERSION
            || ! is_string($payload['resource_class'] ?? null)
            || ! is_bool($payload['persisted'] ?? null)
            || ! is_array($payload['resource_fingerprint_keys'] ?? null)
            || ! in_array($payload['ability'] ?? null, self::ABILITIES, true)
            || EmbeddedComponentSurface::tryFrom($payload['surface'] ?? '') === null
            || ! is_string($payload['field_slug'] ?? null)
            || ! is_string($payload['component_alias'] ?? null)
            || ! is_array($payload['parameters'] ?? null)
            || ! $this->hasOnlySerializableValues($payload['parameters'])
        ) {
            return false;
        }

        $resourceClass = $payload['resource_class'];
        $resourceKey = $payload['resource_key'];
        $persisted = $payload['persisted'];
        $fingerprintKeys = $payload['resource_fingerprint_keys'];
        $fingerprint = $payload['resource_fingerprint'];
        $surface = EmbeddedComponentSurface::from($payload['surface']);

        if (! class_exists($resourceClass)
            || ! is_subclass_of($resourceClass, Model::class)
            || ! is_subclass_of($resourceClass, DefinesFields::class)
            || (! is_int($resourceKey) && ! is_string($resourceKey) && $resourceKey !== null)
            || ! $this->hasValidFingerprintKeys($fingerprintKeys)
        ) {
            return false;
        }

        if ($persisted) {
            return $resourceKey !== null
                && $fingerprintKeys !== []
                && is_string($fingerprint)
                && preg_match('/^[a-f0-9]{64}$/', $fingerprint) === 1
                && (($surface === EmbeddedComponentSurface::Edit && $payload['ability'] === 'update')
                    || ($surface !== EmbeddedComponentSurface::Edit && $payload['ability'] === 'view'));
        }

        return $fingerprintKeys === []
            && $fingerprint === null
            && $surface === EmbeddedComponentSurface::Edit
            && $payload['ability'] === 'create';
    }

    /**
     * @param  list<string>  $keys
     *
     * @throws JsonException
     */
    private function resourceFingerprint(Model $resource, array $keys): string
    {
        $attributes = Arr::only($resource->getRawOriginal(), $keys);
        ksort($attributes);

        return hash(
            'sha256',
            json_encode($attributes, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION),
        );
    }

    /**
     * @return list<string>
     */
    private function resourceFingerprintKeys(Model $resource): array
    {
        $keys = array_keys($resource->getRawOriginal());
        sort($keys);

        return $keys;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function resourceMatchesPayload(Model $resource, array $payload): bool
    {
        if ($resource::class !== $payload['resource_class']
            || $resource->getKey() !== $payload['resource_key']
        ) {
            return false;
        }

        if (! $payload['persisted']) {
            return ! $resource->exists && ! $resource->wasRecentlyCreated;
        }

        try {
            return ($resource->exists || $resource->wasRecentlyCreated)
                && hash_equals(
                    $payload['resource_fingerprint'],
                    $this->resourceFingerprint(
                        $resource,
                        $payload['resource_fingerprint_keys'],
                    ),
                );
        } catch (JsonException) {
            return false;
        }
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function restoreResource(array $payload): Model
    {
        $resourceClass = $payload['resource_class'];

        /** @var Model $resource */
        $resource = new $resourceClass;

        if (! $payload['persisted']) {
            if ($payload['resource_key'] !== null) {
                abort_if($resource->newQuery()->whereKey($payload['resource_key'])->exists(), 403);
                $resource->setAttribute($resource->getKeyName(), $payload['resource_key']);
            }

            return $resource;
        }

        $restoredResource = $resource->newQuery()->find($payload['resource_key']);

        abort_unless($restoredResource instanceof Model, 403);

        return $restoredResource;
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
