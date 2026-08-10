<?php

namespace Aura\Base\Services;

use Aura\Base\Contracts\DefinesFields;
use Aura\Base\Contracts\ProvidesEmbeddedAuthorizationAttributes;
use Aura\Base\Exceptions\InvalidEmbeddedAuthorizationAttributes;
use Aura\Base\Exceptions\InvalidEmbeddedComponentParameters;
use Aura\Base\Exceptions\MissingEmbeddedResourceIncarnationGuard;
use Aura\Base\Exceptions\OccupiedEmbeddedResourceKey;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Gate;
use JsonException;

final class EmbeddedComponentContextCodec
{
    private const ABILITIES = ['create', 'update', 'view'];

    private const MAX_AUTHORIZATION_ATTRIBUTE_LENGTH = 1024;

    private const MAX_AUTHORIZATION_ATTRIBUTE_NAME_LENGTH = 191;

    private const MAX_AUTHORIZATION_ATTRIBUTES = 16;

    private const PAYLOAD_KEYS = [
        'version',
        'issued_at',
        'expires_at',
        'config_revision',
        'resource_class',
        'resource_key',
        'persisted',
        'resource_incarnation',
        'resource_incarnation_version',
        'resource_fingerprint',
        'resource_authorization_attributes',
        'ability',
        'surface',
        'field_slug',
        'component_alias',
        'parameters',
    ];

    private const VERSION = 4;

    public function __construct(
        private readonly EmbeddedComponentContextStore $store,
        private readonly EmbeddedComponentParameterValidator $parameterValidator,
    ) {}

    /**
     * @param  array<string, mixed>  $context
     */
    public function authorize(array $context, bool $fresh = false): AuthorizedEmbeddedComponentContext
    {
        $payload = $this->verifiedPayload($context);

        abort_unless($payload !== null, 403);

        $providedSignature = $context['signature'];
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

        if (! $persisted) {
            $resourceKey = $resource->getKey();

            if ((is_int($resourceKey) || is_string($resourceKey))
                && $this->store->physicallyExists($resource, $resourceKey)
            ) {
                throw new OccupiedEmbeddedResourceKey(sprintf(
                    'Cannot issue an embedded create context because [%s.%s] is already occupied.',
                    $resource->getTable(),
                    $resourceKey,
                ));
            }
        }

        if ($persisted) {
            $resource = $this->store->canonical($resource);
            abort_unless($resource instanceof Model, 500);
        }

        $resourceIncarnation = $persisted ? $this->store->token($resource) : null;
        $resourceIncarnationVersion = $persisted ? $this->store->version($resource) : null;

        $issuedAt = now()->getTimestamp();
        $payload = [
            'version' => self::VERSION,
            'issued_at' => $issuedAt,
            'expires_at' => $issuedAt + $this->contextTtl(),
            'config_revision' => $this->configRevision(),
            'resource_class' => $resource::class,
            'resource_key' => $resource->getKey(),
            'persisted' => $persisted,
            'resource_incarnation' => $resourceIncarnation,
            'resource_incarnation_version' => $resourceIncarnationVersion,
            'resource_fingerprint' => $persisted ? $this->resourceFingerprint($resource) : null,
            'resource_authorization_attributes' => $persisted
                ? []
                : $this->authorizationAttributes($resource),
            'ability' => $ability,
            'surface' => $surface->value,
            'field_slug' => $fieldSlug,
            'component_alias' => $componentAlias,
            'parameters' => $parameters,
        ];

        abort_unless($this->isValidPayloadShape($payload), 500);

        $signature = $this->signature($payload);
        $this->store->remember($signature, $resource);

        return [...$payload, 'signature' => $signature];
    }

    /**
     * Prime canonical owners and incarnation tokens once for every embedded
     * component included in a bundled Livewire update request.
     *
     * @param  array<int, mixed>  $requestPayload
     */
    public function primeLivewireRequest(array $requestPayload): void
    {
        $identities = [];

        foreach ($requestPayload as $componentPayload) {
            if (! is_array($componentPayload)
                || ! is_string($componentPayload['snapshot'] ?? null)
            ) {
                continue;
            }

            try {
                $snapshot = json_decode(
                    $componentPayload['snapshot'],
                    associative: true,
                    flags: JSON_THROW_ON_ERROR,
                );
            } catch (JsonException) {
                continue;
            }

            if (! is_array($snapshot)) {
                continue;
            }

            $context = $this->unwrapSnapshotValue(
                $snapshot['data']['auraEmbeddedContext'] ?? null,
            );

            if (! is_array($context)) {
                continue;
            }

            $payload = $this->verifiedPayload($context);

            if ($payload === null || ! $payload['persisted']) {
                continue;
            }

            $identities[] = [
                'resource_class' => $payload['resource_class'],
                'resource_key' => $payload['resource_key'],
            ];
        }

        try {
            $this->store->primeIdentities($identities);
        } catch (MissingEmbeddedResourceIncarnationGuard) {
            abort(403);
        }
    }

    /**
     * @return array<string, bool|float|int|string|null>
     */
    private function authorizationAttributes(Model $resource): array
    {
        $attributes = $resource->getAttributes();
        $keyName = $resource->getKeyName();

        if (! $resource instanceof ProvidesEmbeddedAuthorizationAttributes) {
            unset($attributes[$keyName]);

            if ($attributes !== []) {
                throw new InvalidEmbeddedAuthorizationAttributes(sprintf(
                    '%s must declare bounded embedded authorization attributes.',
                    $resource::class,
                ));
            }

            return [];
        }

        $names = $resource->embeddedAuthorizationAttributeNames();

        if (! array_is_list($names)
            || count($names) > self::MAX_AUTHORIZATION_ATTRIBUTES
            || count($names) !== count(array_unique($names))
        ) {
            throw new InvalidEmbeddedAuthorizationAttributes('Embedded authorization attribute names must be a bounded unique list.');
        }

        $authorizationAttributes = [];

        foreach ($names as $name) {
            if (! is_string($name)
                || $name === ''
                || mb_strlen($name) > self::MAX_AUTHORIZATION_ATTRIBUTE_NAME_LENGTH
                || $name === $keyName
            ) {
                throw new InvalidEmbeddedAuthorizationAttributes('Embedded authorization attribute names must be non-key strings.');
            }

            if (! array_key_exists($name, $attributes)) {
                continue;
            }

            $value = $attributes[$name];

            if ((! is_null($value) && ! is_bool($value) && ! is_float($value) && ! is_int($value) && ! is_string($value))
                || (is_float($value) && ! is_finite($value))
                || (is_string($value) && mb_strlen($value) > self::MAX_AUTHORIZATION_ATTRIBUTE_LENGTH)
            ) {
                throw new InvalidEmbeddedAuthorizationAttributes('Embedded authorization attributes must contain bounded scalar values.');
            }

            $authorizationAttributes[$name] = $value;
        }

        return $authorizationAttributes;
    }

    private function configRevision(): string
    {
        $revision = config('aura.embedded_components.context_revision', '1');

        abort_unless(is_string($revision) || is_int($revision), 500);

        $revision = (string) $revision;
        abort_unless($revision !== '', 500);

        return $revision;
    }

    private function contextTtl(): int
    {
        $ttl = config('aura.embedded_components.context_ttl', 3600);

        abort_unless(is_int($ttl) || (is_string($ttl) && ctype_digit($ttl)), 500);

        $ttl = (int) $ttl;
        abort_unless($ttl > 0, 500);

        return $ttl;
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

    private function hasValidAuthorizationAttributes(mixed $attributes): bool
    {
        if (! is_array($attributes)
            || array_is_list($attributes)
            || count($attributes) > self::MAX_AUTHORIZATION_ATTRIBUTES
        ) {
            return $attributes === [];
        }

        foreach ($attributes as $name => $value) {
            if (! is_string($name)
                || $name === ''
                || mb_strlen($name) > self::MAX_AUTHORIZATION_ATTRIBUTE_NAME_LENGTH
                || (! is_null($value) && ! is_bool($value) && ! is_float($value) && ! is_int($value) && ! is_string($value))
                || (is_float($value) && ! is_finite($value))
                || (is_string($value) && mb_strlen($value) > self::MAX_AUTHORIZATION_ATTRIBUTE_LENGTH)
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isCurrentPayload(array $payload): bool
    {
        $now = now()->getTimestamp();

        return $payload['config_revision'] === $this->configRevision()
            && $payload['expires_at'] === $payload['issued_at'] + $this->contextTtl()
            && $payload['issued_at'] <= $now
            && $payload['expires_at'] >= $now;
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function isValidPayloadShape(array $payload): bool
    {
        if (array_keys($payload) !== self::PAYLOAD_KEYS
            || ($payload['version'] ?? null) !== self::VERSION
            || ! is_int($payload['issued_at'] ?? null)
            || ! is_int($payload['expires_at'] ?? null)
            || ! is_string($payload['config_revision'] ?? null)
            || $payload['config_revision'] === ''
            || ! is_string($payload['resource_class'] ?? null)
            || ! is_bool($payload['persisted'] ?? null)
            || ! in_array($payload['ability'] ?? null, self::ABILITIES, true)
            || EmbeddedComponentSurface::tryFrom($payload['surface'] ?? '') === null
            || ! is_string($payload['field_slug'] ?? null)
            || ! is_string($payload['component_alias'] ?? null)
            || ! is_array($payload['parameters'] ?? null)
        ) {
            return false;
        }

        try {
            $this->parameterValidator->validate($payload['parameters']);
        } catch (InvalidEmbeddedComponentParameters) {
            return false;
        }

        $resourceKey = $payload['resource_key'];
        $persisted = $payload['persisted'];
        $incarnation = $payload['resource_incarnation'];
        $incarnationVersion = $payload['resource_incarnation_version'];
        $fingerprint = $payload['resource_fingerprint'];
        $authorizationAttributes = $payload['resource_authorization_attributes'];
        $surface = EmbeddedComponentSurface::from($payload['surface']);

        if ((! is_int($resourceKey) && ! is_string($resourceKey) && $resourceKey !== null)
            || $payload['expires_at'] <= $payload['issued_at']
        ) {
            return false;
        }

        if ($persisted) {
            return $resourceKey !== null
                && is_string($incarnation)
                && preg_match('/^[a-f0-9-]{36}$/', $incarnation) === 1
                && is_int($incarnationVersion)
                && $incarnationVersion > 0
                && is_string($fingerprint)
                && preg_match('/^[a-f0-9]{64}$/', $fingerprint) === 1
                && $authorizationAttributes === []
                && (($surface === EmbeddedComponentSurface::Edit && $payload['ability'] === 'update')
                    || ($surface !== EmbeddedComponentSurface::Edit && $payload['ability'] === 'view'));
        }

        return $incarnation === null
            && $incarnationVersion === null
            && $fingerprint === null
            && $this->hasValidAuthorizationAttributes($authorizationAttributes)
            && $surface === EmbeddedComponentSurface::Edit
            && $payload['ability'] === 'create';
    }

    /**
     * @throws JsonException
     */
    private function resourceFingerprint(Model $resource): string
    {
        $attributes = $resource->getRawOriginal();
        ksort($attributes);

        return hash(
            'sha256',
            json_encode($attributes, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION),
        );
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
            return ! $resource->exists
                && ! $resource->wasRecentlyCreated
                && $this->authorizationAttributes($resource) === $payload['resource_authorization_attributes'];
        }

        try {
            return ($resource->exists || $resource->wasRecentlyCreated)
                && hash_equals($payload['resource_incarnation'], $this->store->token($resource))
                && $payload['resource_incarnation_version'] === $this->store->version($resource)
                && hash_equals($payload['resource_fingerprint'], $this->resourceFingerprint($resource));
        } catch (JsonException|MissingEmbeddedResourceIncarnationGuard) {
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
            $resource->forceFill($payload['resource_authorization_attributes']);

            if ($payload['resource_key'] !== null) {
                abort_if($this->store->physicallyExists($resource, $payload['resource_key']), 403);
                $resource->setAttribute($resource->getKeyName(), $payload['resource_key']);
            }

            return $resource;
        }

        $restoredResource = $this->store->canonicalIdentity(
            $resourceClass,
            $payload['resource_key'],
        );

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

    private function unwrapSnapshotValue(mixed $value, int $depth = 0): mixed
    {
        if ($depth > 20 || ! is_array($value)) {
            return $value;
        }

        if (array_is_list($value)
            && count($value) === 2
            && is_array($value[1])
            && array_key_exists('s', $value[1])
        ) {
            $value = $value[0];
        }

        if (! is_array($value)) {
            return $value;
        }

        foreach ($value as $key => $nestedValue) {
            $value[$key] = $this->unwrapSnapshotValue($nestedValue, $depth + 1);
        }

        return $value;
    }

    /**
     * @param  array<string, mixed>  $context
     * @return array<string, mixed>|null
     */
    private function verifiedPayload(array $context): ?array
    {
        if (! $this->hasExactContextKeys($context)) {
            return null;
        }

        $payload = array_intersect_key($context, array_flip(self::PAYLOAD_KEYS));

        if (! $this->isValidPayloadShape($payload)) {
            return null;
        }

        try {
            $expectedSignature = $this->signature($payload);
        } catch (JsonException) {
            return null;
        }

        if (! hash_equals($expectedSignature, $context['signature'])
            || ! $this->isCurrentPayload($payload)
        ) {
            return null;
        }

        $resourceClass = $payload['resource_class'];

        if (! class_exists($resourceClass)
            || ! is_subclass_of($resourceClass, Model::class)
            || ! is_subclass_of($resourceClass, DefinesFields::class)
        ) {
            return null;
        }

        return $payload;
    }
}
