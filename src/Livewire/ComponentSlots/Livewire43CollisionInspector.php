<?php

namespace Aura\Base\Livewire\ComponentSlots;

use Closure;
use Livewire\Factory\Factory;
use Livewire\Finder\Finder;
use ReflectionClass;
use ReflectionProperty;
use Throwable;

class Livewire43CollisionInspector implements LivewireCollisionInspector
{
    /** @var list<string> */
    private const FACTORY_PROPERTIES = [
        'finder',
        'compiler',
        'missingComponentResolvers',
        'resolvedComponentCache',
    ];

    /** @var list<string> */
    private const FINDER_PROPERTIES = [
        'classLocations',
        'viewLocations',
        'classNamespaces',
        'viewNamespaces',
        'classComponents',
        'viewComponents',
    ];

    public function __construct(
        private readonly Finder $finder,
        private readonly Factory $factory,
    ) {}

    public function assertCompatible(): void
    {
        if ($this->finder::class !== Finder::class) {
            throw new UnsupportedLivewireInternals(
                'Livewire 4.3 collision inspection requires exact Finder ['.Finder::class.']; got ['.$this->finder::class.'].'
            );
        }

        if ($this->factory::class !== Factory::class) {
            throw new UnsupportedLivewireInternals(
                'Livewire 4.3 collision inspection requires exact Factory ['.Factory::class.']; got ['.$this->factory::class.'].'
            );
        }

        $this->assertProperties(Finder::class, self::FINDER_PROPERTIES);
        $this->assertProperties(Factory::class, self::FACTORY_PROPERTIES);
        $this->assertMethod(Finder::class, 'normalizeName', 1, 1);
        $this->assertMethod(Finder::class, 'parseNamespaceAndName', 1, 1);
        $this->assertMethod(Finder::class, 'resolveClassComponentClassName', 1, 1);
        $this->assertMethod(Finder::class, 'resolveMultiFileComponentPath', 1, 1);
        $this->assertMethod(Finder::class, 'resolveSingleFileComponentPath', 1, 1);
        $this->assertMethod(Factory::class, 'resolveComponentNameAndClass', 1, 1);
        $this->assertMethod(Factory::class, 'resolveMissingComponent', 1, 1);

        foreach (array_merge(self::FINDER_PROPERTIES, ['missingComponentResolvers', 'resolvedComponentCache']) as $property) {
            $target = in_array($property, self::FINDER_PROPERTIES, true) ? $this->finder : $this->factory;

            if (! is_array($this->read($target, $property))) {
                throw new UnsupportedLivewireInternals("Livewire 4.3 internal [{$property}] must contain an array.");
            }
        }

        if ($this->read($this->factory, 'finder') !== $this->finder) {
            throw new UnsupportedLivewireInternals('Livewire Factory must reference the inspected Finder instance.');
        }
    }

    public function assertUnclaimed(array $identifiers, Closure $auraResolver): void
    {
        $this->assertCompatible();

        $classComponents = $this->read($this->finder, 'classComponents');
        $viewComponents = $this->read($this->finder, 'viewComponents');
        $classNamespaces = $this->read($this->finder, 'classNamespaces');
        $viewNamespaces = $this->read($this->finder, 'viewNamespaces');
        $factoryCache = $this->read($this->factory, 'resolvedComponentCache');
        $resolvers = $this->read($this->factory, 'missingComponentResolvers');

        foreach ($identifiers as $identifier) {
            $normalized = $this->finder->normalizeName($identifier);

            if (! is_string($normalized) || $normalized === '') {
                throw new UnsupportedLivewireInternals("Livewire could not normalize protected component identifier [{$identifier}].");
            }

            if (array_key_exists($normalized, $factoryCache)) {
                $this->collision($identifier, 'factory-cache', $factoryCache[$normalized]);
            }

            if (array_key_exists($normalized, $classComponents)) {
                $this->collision($identifier, 'explicit-class', $classComponents[$normalized]);
            }

            if (array_key_exists($normalized, $viewComponents)) {
                $this->collision($identifier, 'explicit-view', $viewComponents[$normalized]);
            }

            [$namespace] = $this->finder->parseNamespaceAndName($normalized);

            if ($namespace !== null && array_key_exists($namespace, $classNamespaces)) {
                $this->collision($identifier, 'class-namespace', $classNamespaces[$namespace]);
            }

            if ($namespace !== null && array_key_exists($namespace, $viewNamespaces)) {
                $this->collision($identifier, 'view-namespace', $viewNamespaces[$namespace]);
            }

            $class = $this->finder->resolveClassComponentClassName($normalized);

            if ($class !== null) {
                $this->collision($identifier, 'conventional-class', $class);
            }

            $multiFilePath = $this->finder->resolveMultiFileComponentPath($normalized);

            if ($multiFilePath !== null) {
                $this->collision($identifier, 'multi-file', $multiFilePath);
            }

            $singleFilePath = $this->finder->resolveSingleFileComponentPath($normalized);

            if ($singleFilePath !== null) {
                $this->collision($identifier, 'single-file', $singleFilePath);
            }

            foreach ($resolvers as $resolver) {
                if ($resolver === $auraResolver) {
                    continue;
                }

                try {
                    $target = $resolver($normalized);
                } catch (Throwable $exception) {
                    $this->collision($identifier, 'missing-resolver-error', $exception::class);
                }

                if ($target) {
                    $this->collision($identifier, 'missing-resolver', $target);
                }
            }
        }
    }

    /** @param class-string $class */
    private function assertMethod(string $class, string $methodName, int $parameters, int $required): void
    {
        $reflection = new ReflectionClass($class);

        if (! $reflection->hasMethod($methodName)) {
            throw new UnsupportedLivewireInternals("Livewire 4.3 method [{$class}::{$methodName}] is missing.");
        }

        $method = $reflection->getMethod($methodName);

        if (! $method->isPublic() || $method->isStatic()
            || $method->getNumberOfParameters() !== $parameters
            || $method->getNumberOfRequiredParameters() !== $required) {
            throw new UnsupportedLivewireInternals("Livewire 4.3 method [{$class}::{$methodName}] has an unsupported signature.");
        }
    }

    /**
     * @param  class-string  $class
     * @param  list<string>  $properties
     */
    private function assertProperties(string $class, array $properties): void
    {
        $reflection = new ReflectionClass($class);

        foreach ($properties as $propertyName) {
            if (! $reflection->hasProperty($propertyName)) {
                throw new UnsupportedLivewireInternals("Livewire 4.3 internal [{$class}::\${$propertyName}] is missing.");
            }

            $property = $reflection->getProperty($propertyName);

            if (! $property->isProtected() || $property->isStatic() || $property->getDeclaringClass()->getName() !== $class) {
                throw new UnsupportedLivewireInternals("Livewire 4.3 internal [{$class}::\${$propertyName}] has an unsupported shape.");
            }
        }
    }

    private function collision(string $identifier, string $kind, mixed $target): never
    {
        $description = match (true) {
            is_string($target) => $target,
            is_scalar($target), $target === null => var_export($target, true),
            default => json_encode($target) ?: get_debug_type($target),
        };

        throw new ComponentSlotCollision(
            "Livewire component identifier [{$identifier}] is already claimed by [{$kind}] target [{$description}]."
        );
    }

    private function read(object $target, string $propertyName): mixed
    {
        $property = new ReflectionProperty($target, $propertyName);

        return $property->getValue($target);
    }
}
