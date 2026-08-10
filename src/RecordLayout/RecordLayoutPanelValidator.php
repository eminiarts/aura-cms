<?php

namespace Aura\Base\RecordLayout;

use Aura\Base\Resource;
use Livewire\Component;
use ReflectionClass;
use ReflectionNamedType;

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

        foreach (['model', 'inModal'] as $input) {
            if ($reflection->hasProperty($input) && $reflection->getProperty($input)->isPublic()) {
                continue;
            }

            $parameter = collect($mount?->getParameters() ?? [])
                ->first(fn ($parameter): bool => $parameter->getName() === $input);

            if ($parameter === null) {
                $this->fail($source, $panel, "a public [{$input}] property or mount parameter");
            }

            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()
                && $input === 'model' && ! is_a(Resource::class, $type->getName(), true)) {
                $this->fail($source, $panel, 'a model input accepting Aura resources');
            }

            if ($type instanceof ReflectionNamedType && $type->isBuiltin()
                && $input === 'inModal' && ! in_array($type->getName(), ['bool', 'mixed'], true)) {
                $this->fail($source, $panel, 'a boolean inModal input');
            }
        }

        foreach ($mount?->getParameters() ?? [] as $parameter) {
            if (! in_array($parameter->getName(), ['model', 'inModal'], true)
                && ! $parameter->isOptional() && ! $parameter->isVariadic()) {
                $this->fail($source, $panel, "no unsupported required mount input [{$parameter->getName()}]");
            }
        }
    }

    private function fail(string $source, RecordLayoutPanel $panel, string $requirement): never
    {
        throw new InvalidRecordLayoutPanel(
            "Record layout panel [{$source}:{$panel->key}] component [{$panel->component}] requires {$requirement}."
        );
    }
}
