<?php

namespace Aura\Base\RecordLayout;

use Aura\Base\Resource;
use Livewire\Component;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionNamedType;
use ReflectionType;
use ReflectionUnionType;

final class RecordLayoutPanelValidator
{
    public function validate(string $source, RecordLayoutPanel $panel): void
    {
        if (! class_exists($panel->component)) {
            $this->fail($source, $panel, 'an existing Livewire component class');
        }

        $reflection = new ReflectionClass($panel->component);

        if ($reflection->getName() !== $panel->component
            || ! $reflection->isSubclassOf(Component::class)
            || ! $reflection->isInstantiable()) {
            $this->fail($source, $panel, 'a canonical, concrete Livewire component class');
        }

        if ($reflection->getConstructor()?->getNumberOfRequiredParameters() > 0) {
            $this->fail($source, $panel, 'a constructor with zero required parameters');
        }

        $mount = $reflection->hasMethod('mount') ? $reflection->getMethod('mount') : null;

        if ($mount !== null && (! $mount->isPublic() || $mount->isStatic())) {
            $this->fail($source, $panel, 'a public, non-static mount method');
        }

        foreach (['model', 'inModal'] as $input) {
            if ($reflection->hasProperty($input) && $reflection->getProperty($input)->isPublic()) {
                $property = $reflection->getProperty($input);

                if ($property->isStatic() || $property->isReadOnly()
                    || ! $this->acceptsInput($property->getType(), $input)) {
                    $this->fail($source, $panel, "a writable [{$input}] property with a compatible type");
                }

                continue;
            }

            $parameter = collect($mount?->getParameters() ?? [])
                ->first(fn ($parameter): bool => $parameter->getName() === $input);

            if ($parameter === null) {
                $this->fail($source, $panel, "a public [{$input}] property or mount parameter");
            }

            if (! $this->acceptsInput($parameter->getType(), $input)) {
                $this->fail($source, $panel, "a compatible [{$input}] mount input");
            }
        }

        foreach ($mount?->getParameters() ?? [] as $parameter) {
            if (! in_array($parameter->getName(), ['model', 'inModal'], true)
                && ! $parameter->isOptional() && ! $parameter->isVariadic()) {
                $this->fail($source, $panel, "no unsupported required mount input [{$parameter->getName()}]");
            }
        }
    }

    private function acceptsInput(?ReflectionType $type, string $input): bool
    {
        if ($type === null) {
            return true;
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if ($this->acceptsInput($unionType, $input)) {
                    return true;
                }
            }

            return false;
        }

        if ($type instanceof ReflectionIntersectionType || ! $type instanceof ReflectionNamedType) {
            return false;
        }

        if ($input === 'inModal') {
            return $type->isBuiltin() && in_array($type->getName(), ['bool', 'mixed'], true);
        }

        if ($type->isBuiltin()) {
            return in_array($type->getName(), ['mixed', 'object'], true);
        }

        return is_a(Resource::class, $type->getName(), true);
    }

    private function fail(string $source, RecordLayoutPanel $panel, string $requirement): never
    {
        throw new InvalidRecordLayoutPanel(
            "Record layout panel [{$source}:{$panel->key}] component [{$panel->component}] requires {$requirement}."
        );
    }
}
