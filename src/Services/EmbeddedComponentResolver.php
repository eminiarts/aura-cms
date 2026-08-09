<?php

namespace Aura\Base\Services;

use Aura\Base\Contracts\DefinesFields;
use Aura\Base\Contracts\EmbeddedLivewireComponent;
use Aura\Base\Contracts\MapsEmbeddedComponentParameters;
use Aura\Base\Traits\AuthorizesEmbeddedComponent;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Gate;
use JsonException;
use Livewire\Livewire;
use Throwable;

final class EmbeddedComponentResolver
{
    private const MAX_PARAMETER_DEPTH = 10;

    public function __construct(
        private readonly DefaultEmbeddedComponentParameterMapper $defaultMapper,
        private readonly EmbeddedComponentContextCodec $contextCodec,
    ) {}

    /**
     * @param  array<string, mixed>  $field
     */
    public function resolve(
        array $field,
        ?Model $resource,
        EmbeddedComponentSurface $surface,
        mixed $value = null,
    ): ?ResolvedEmbeddedComponent {
        $resource = $this->resolveOwningResource($field, $resource, $surface);

        if (! $resource) {
            return null;
        }

        $ability = $this->ability($resource, $surface);

        if (Gate::denies($ability, $resource)) {
            return null;
        }

        $alias = $this->resolveAlias($field, $surface);

        if (! $alias) {
            return null;
        }

        $context = new EmbeddedComponentContext($surface, $resource, $field, $value);
        $parameters = $this->parameters($field, $context);

        if ($parameters === null) {
            return null;
        }

        try {
            $parameters['auraEmbeddedContext'] = $this->contextCodec->issue(
                resource: $resource,
                ability: $ability,
                surface: $surface,
                fieldSlug: (string) ($field['slug'] ?? ''),
                componentAlias: $alias,
            );

            $key = $this->componentKey($field, $resource, $surface, $alias);
        } catch (JsonException) {
            return null;
        }

        return new ResolvedEmbeddedComponent($alias, $parameters, $key);
    }

    private function ability(Model $resource, EmbeddedComponentSurface $surface): string
    {
        if ($surface === EmbeddedComponentSurface::Edit) {
            return $resource->exists ? 'update' : 'create';
        }

        return 'view';
    }

    /**
     * @param  array<string, mixed>  $field
     *
     * @throws JsonException
     */
    private function componentKey(
        array $field,
        Model $resource,
        EmbeddedComponentSurface $surface,
        string $alias,
    ): string {
        $identity = [
            'surface' => $surface->value,
            'resource_class' => $resource::class,
            'resource_key' => $resource->getKey(),
            'field_slug' => (string) ($field['slug'] ?? ''),
            'field_id' => $field['_id'] ?? null,
            'parent_id' => $field['_parent_id'] ?? null,
            'component_identity' => $field['component_identity'] ?? null,
            'alias' => $alias,
        ];

        return 'aura-embedded-'.hash(
            'sha256',
            json_encode($identity, JSON_THROW_ON_ERROR | JSON_PRESERVE_ZERO_FRACTION),
        );
    }

    private function crossesEmbeddedBoundary(mixed $alias): bool
    {
        if (! is_string($alias)
            || ! preg_match('/^[a-zA-Z0-9][a-zA-Z0-9._:-]*$/', $alias)
        ) {
            return false;
        }

        try {
            $component = Livewire::new($alias);
        } catch (Throwable) {
            return false;
        }

        return $component instanceof EmbeddedLivewireComponent
            && in_array(AuthorizesEmbeddedComponent::class, class_uses_recursive($component), true);
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
     * @param  array<string, mixed>  $field
     */
    private function parameters(array $field, EmbeddedComponentContext $context): ?array
    {
        $parameters = $this->defaultMapper->map($context);
        $mapperClass = $field['parameter_mapper'] ?? null;

        if ($mapperClass !== null && $mapperClass !== '') {
            if (! is_string($mapperClass)
                || ! class_exists($mapperClass)
                || ! is_subclass_of($mapperClass, MapsEmbeddedComponentParameters::class)
            ) {
                return null;
            }

            try {
                $mapper = app($mapperClass);
                $parameters = array_replace($parameters, $mapper->map($context));
            } catch (Throwable) {
                return null;
            }
        }

        if (array_key_exists('auraEmbeddedContext', $parameters)
            || ! $this->hasOnlySerializableValues($parameters)
        ) {
            return null;
        }

        return $parameters;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function resolveAlias(array $field, EmbeddedComponentSurface $surface): ?string
    {
        $configured = Arr::get($field, 'component_aliases.'.$surface->value);

        if ($surface === EmbeddedComponentSurface::Edit && ! is_string($configured)) {
            $configured = $field['component'] ?? null;
        }

        $fallback = Arr::get($field, 'component_aliases.fallback');

        $previousAlias = null;

        foreach ([$configured, $fallback] as $alias) {
            if ($alias === $previousAlias) {
                continue;
            }

            $previousAlias = $alias;

            if ($this->crossesEmbeddedBoundary($alias)) {
                return $alias;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $field
     */
    private function resolveOwningResource(
        array $field,
        ?Model $resource,
        EmbeddedComponentSurface $surface,
    ): ?Model {
        if ($resource instanceof DefinesFields) {
            return $resource;
        }

        if ($resource !== null || $surface !== EmbeddedComponentSurface::Edit) {
            return null;
        }

        $resourceClass = $field['owner_resource'] ?? null;

        if (! is_string($resourceClass)
            || ! class_exists($resourceClass)
            || ! is_subclass_of($resourceClass, Model::class)
            || ! is_subclass_of($resourceClass, DefinesFields::class)
        ) {
            return null;
        }

        return new $resourceClass;
    }
}
