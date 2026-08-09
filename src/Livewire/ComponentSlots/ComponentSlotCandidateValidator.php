<?php

namespace Aura\Base\Livewire\ComponentSlots;

use Illuminate\Contracts\Container\Container;
use Illuminate\Contracts\Routing\UrlRoutable;
use Livewire\Attributes\On;
use Livewire\Component;
use ReflectionClass;
use ReflectionIntersectionType;
use ReflectionMethod;
use ReflectionNamedType;
use ReflectionParameter;
use ReflectionType;
use ReflectionUnionType;
use Throwable;

class ComponentSlotCandidateValidator
{
    /**
     * @var array<string, array<string, list<mixed>>>
     */
    private const SLOT_INPUT_VALUES = [
        'global-search' => [],
        'media-manager' => [
            'model' => [\Aura\Base\Resource::class],
            'slug' => ['media'],
            'selected' => [[1, '2'], null],
            'ownerToken' => ['opaque-owner-token'],
            'modalAttributes' => [[
                'persistent' => false,
                'modalClasses' => 'max-w-7xl',
                'slideOver' => false,
            ]],
        ],
    ];

    public function __construct(private readonly Container $container) {}

    /**
     * @return class-string<Component>
     */
    public function validate(string $slot, string $source, mixed $candidate): string
    {
        if (! array_key_exists($slot, self::SLOT_INPUT_VALUES)) {
            $this->fail($slot, $source, $candidate, 'a known component slot');
        }

        if (! is_string($candidate)) {
            $this->fail($slot, $source, $candidate, 'a class string');
        }

        if (str_starts_with($candidate, '\\\\')) {
            $this->fail($slot, $source, $candidate, 'the canonical, case-correct class name with at most one leading slash');
        }

        $canonicalCandidate = str_starts_with($candidate, '\\')
            ? substr($candidate, 1)
            : $candidate;

        if ($canonicalCandidate === '' || ! $this->symbolExists($canonicalCandidate)) {
            $this->fail($slot, $source, $candidate, 'an existing class string');
        }

        $reflection = new ReflectionClass($canonicalCandidate);

        if ($reflection->getName() !== $canonicalCandidate) {
            $this->fail($slot, $source, $candidate, 'the canonical, case-correct class name with at most one leading slash');
        }

        if ($reflection->isInterface() || $reflection->isTrait() || $reflection->isEnum()
            || $reflection->isAnonymous() || ! $reflection->isSubclassOf(Component::class)) {
            $this->fail($slot, $source, $candidate, 'a named Livewire component class');
        }

        if ($reflection->isAbstract() || ! $reflection->isInstantiable()) {
            $this->fail($slot, $source, $candidate, 'an instantiable Livewire component');
        }

        $constructor = $reflection->getConstructor();

        if ($constructor && $constructor->getNumberOfRequiredParameters() > 0) {
            $this->fail($slot, $source, $candidate, 'a constructor with zero required parameters');
        }

        $mount = $reflection->hasMethod('mount') ? $reflection->getMethod('mount') : null;

        if ($mount && (! $mount->isPublic() || $mount->isStatic())) {
            $this->fail($slot, $source, $candidate, 'a public, non-static mount method');
        }

        $this->validateInputs($slot, $source, $candidate, $reflection, $mount);
        $this->validateExtraMountParameters($slot, $source, $candidate, $mount);

        if ($slot === 'media-manager') {
            $this->validateMediaActions($slot, $source, $candidate, $reflection);
            $this->validateModalClasses($slot, $source, $candidate, $reflection);
        }

        return $reflection->getName();
    }

    private function acceptsValue(?ReflectionType $type, mixed $value): bool
    {
        if ($type === null) {
            return true;
        }

        if ($type instanceof ReflectionUnionType) {
            foreach ($type->getTypes() as $unionType) {
                if ($this->acceptsValue($unionType, $value)) {
                    return true;
                }
            }

            return false;
        }

        if ($type instanceof ReflectionIntersectionType) {
            foreach ($type->getTypes() as $intersectionType) {
                if (! $this->acceptsValue($intersectionType, $value)) {
                    return false;
                }
            }

            return true;
        }

        if (! $type instanceof ReflectionNamedType) {
            return false;
        }

        if ($value === null) {
            return $type->allowsNull() || $type->getName() === 'mixed' || $type->getName() === 'null';
        }

        if (! $type->isBuiltin()) {
            return $value instanceof ($type->getName());
        }

        return match ($type->getName()) {
            'array' => is_array($value),
            'bool' => is_bool($value),
            'callable' => is_callable($value),
            'false' => $value === false,
            'float' => is_float($value),
            'int' => is_int($value),
            'iterable' => is_iterable($value),
            'mixed' => true,
            'null' => false,
            'object' => is_object($value),
            'string' => is_string($value),
            'true' => $value === true,
            default => false,
        };
    }

    private function fail(string $slot, string $source, mixed $candidate, string $requirement): never
    {
        $value = is_string($candidate) ? $candidate : get_debug_type($candidate);

        throw new InvalidComponentSlotCandidate(
            "Component slot [{$slot}] source [{$source}] candidate [{$value}] violates requirement: {$requirement}."
        );
    }

    private function resolveDependency(
        string $slot,
        string $source,
        mixed $candidate,
        ReflectionParameter $parameter,
        ReflectionNamedType $type,
    ): void {
        $dependency = $type->getName();

        if (enum_exists($dependency) || is_a($dependency, UrlRoutable::class, true)) {
            $this->fail($slot, $source, $candidate, "mount parameter [{$parameter->getName()}] cannot require caller data [{$dependency}]");
        }

        if ($parameter->isDefaultValueAvailable() && ! $this->container->bound($dependency)) {
            return;
        }

        try {
            $resolved = $this->container->make($dependency);
        } catch (Throwable) {
            $this->fail($slot, $source, $candidate, "mount dependency [{$dependency}] for [{$parameter->getName()}] must resolve from the container");
        }

        if (! $resolved instanceof $dependency) {
            $this->fail($slot, $source, $candidate, "mount dependency [{$dependency}] for [{$parameter->getName()}] must resolve to that declared type");
        }
    }

    private function symbolExists(string $candidate): bool
    {
        return class_exists($candidate)
            || interface_exists($candidate)
            || trait_exists($candidate)
            || enum_exists($candidate);
    }

    private function validateExtraMountParameters(
        string $slot,
        string $source,
        mixed $candidate,
        ?ReflectionMethod $mount,
    ): void {
        if ($mount === null) {
            return;
        }

        foreach ($mount->getParameters() as $parameter) {
            if (array_key_exists($parameter->getName(), self::SLOT_INPUT_VALUES[$slot]) || $parameter->isVariadic()) {
                continue;
            }

            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && ! $type->isBuiltin()) {
                $this->resolveDependency($slot, $source, $candidate, $parameter, $type);

                continue;
            }

            if ($parameter->isOptional()) {
                continue;
            }

            $this->fail($slot, $source, $candidate, "mount parameter [{$parameter->getName()}] must be optional, variadic, or a resolvable class/interface dependency");
        }
    }

    private function validateInputs(
        string $slot,
        string $source,
        mixed $candidate,
        ReflectionClass $reflection,
        ?ReflectionMethod $mount,
    ): void {
        $mountParameters = $mount
            ? collect($mount->getParameters())->keyBy(fn (ReflectionParameter $parameter): string => $parameter->getName())
            : collect();

        foreach (self::SLOT_INPUT_VALUES[$slot] as $name => $values) {
            $property = $reflection->hasProperty($name) ? $reflection->getProperty($name) : null;
            $parameter = $mountParameters->get($name);

            if ($property === null && ! $parameter instanceof ReflectionParameter) {
                $this->fail($slot, $source, $candidate, "named input [{$name}] must have a writable public property or mount parameter");
            }

            if ($property !== null) {
                $declaringClass = $property->getDeclaringClass()->getName();
                $declaredBelowComponent = $declaringClass !== Component::class
                    && is_subclass_of($declaringClass, Component::class);

                if (! $property->isPublic() || $property->isStatic() || $property->isReadOnly() || ! $declaredBelowComponent) {
                    $this->fail($slot, $source, $candidate, "named input [{$name}] property must be public, writable, non-static, and declared below Livewire\\Component");
                }

                foreach ($values as $value) {
                    if (! $this->acceptsValue($property->getType(), $value)) {
                        $this->fail($slot, $source, $candidate, "named input [{$name}] property type must accept every supplied value");
                    }
                }
            }

            if ($parameter instanceof ReflectionParameter) {
                foreach ($values as $value) {
                    if (! $this->acceptsValue($parameter->getType(), $value)) {
                        $this->fail($slot, $source, $candidate, "named input [{$name}] mount parameter type must accept every supplied value");
                    }
                }
            }
        }
    }

    private function validateMediaActions(
        string $slot,
        string $source,
        mixed $candidate,
        ReflectionClass $reflection,
    ): void {
        $actions = [
            'requestMediaSelection' => [
                ['value', 'array', false, false],
            ],
            'acknowledgeMediaSelection' => [
                ['ownerToken', 'string', false, false],
                ['requestToken', 'string', false, false],
                ['outcome', 'string', false, false],
                ['errorCode', 'string', true, true],
            ],
            'expireMediaSelection' => [
                ['requestToken', 'string', false, false],
            ],
        ];

        foreach ($actions as $methodName => $expectedParameters) {
            if (! $reflection->hasMethod($methodName)) {
                $this->fail($slot, $source, $candidate, "public action [{$methodName}] with the required signature");
            }

            $method = $reflection->getMethod($methodName);
            $returnType = $method->getReturnType();

            if (! $method->isPublic() || $method->isStatic()
                || ! $returnType instanceof ReflectionNamedType
                || $returnType->getName() !== 'void'
                || $returnType->allowsNull()
                || count($method->getParameters()) !== count($expectedParameters)) {
                $this->fail($slot, $source, $candidate, "public action [{$methodName}] with the required signature and void return type");
            }

            foreach ($method->getParameters() as $index => $parameter) {
                [$expectedName, $expectedType, $nullable, $hasNullDefault] = $expectedParameters[$index];
                $type = $parameter->getType();

                if ($parameter->getName() !== $expectedName
                    || $parameter->isVariadic()
                    || $parameter->isPassedByReference()
                    || ! $type instanceof ReflectionNamedType
                    || $type->getName() !== $expectedType
                    || $type->allowsNull() !== $nullable
                    || $parameter->isDefaultValueAvailable() !== $hasNullDefault
                    || ($hasNullDefault && $parameter->getDefaultValue() !== null)) {
                    $this->fail($slot, $source, $candidate, "public action [{$methodName}] parameter [{$expectedName}] with the required signature");
                }
            }
        }

        $acknowledgement = $reflection->getMethod('acknowledgeMediaSelection');
        $listensForAcknowledgement = collect($acknowledgement->getAttributes(On::class))
            ->contains(fn ($attribute): bool => $attribute->newInstance()->event === 'aura-media-selection-acknowledged');

        if (! $listensForAcknowledgement) {
            $this->fail($slot, $source, $candidate, 'acknowledgeMediaSelection must listen for [aura-media-selection-acknowledged]');
        }
    }

    private function validateModalClasses(
        string $slot,
        string $source,
        mixed $candidate,
        ReflectionClass $reflection,
    ): void {
        if (! $reflection->hasMethod('modalClasses')) {
            return;
        }

        $method = $reflection->getMethod('modalClasses');
        $returnType = $method->getReturnType();

        if (! $method->isPublic() || ! $method->isStatic() || $method->getNumberOfParameters() !== 0
            || ! $returnType instanceof ReflectionNamedType || $returnType->getName() !== 'string'
            || $returnType->allowsNull()) {
            $this->fail($slot, $source, $candidate, 'modalClasses must be public static, have zero parameters, and declare string');
        }

        try {
            $classes = $method->invoke(null);
        } catch (Throwable) {
            $this->fail($slot, $source, $candidate, 'modalClasses must execute successfully during boot validation');
        }

        if ($classes === '' || strlen($classes) > 512 || preg_match('/[\x00-\x1F\x7F]/', $classes) === 1) {
            $this->fail($slot, $source, $candidate, 'modalClasses must return 1-512 bytes without ASCII control characters');
        }
    }
}
