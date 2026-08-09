<?php

namespace Aura\Base\Livewire\ComponentSlots;

use Closure;
use Livewire\Compiler\Compiler;
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
        $this->assertMethod(Finder::class, 'normalizeName', 'nameComponentOrClass', '?string');
        $this->assertMethod(Finder::class, 'parseNamespaceAndName', 'name', 'array');
        $this->assertMethod(Finder::class, 'resolveClassComponentClassName', 'name', '?string');
        $this->assertMethod(Finder::class, 'resolveMultiFileComponentPath', 'name', '?string');
        $this->assertMethod(Finder::class, 'resolveSingleFileComponentPath', 'name', '?string');
        $this->assertMethod(Factory::class, 'resolveComponentNameAndClass', 'name', 'array');
        $this->assertMethod(Factory::class, 'resolveMissingComponent', 'resolver', 'void');

        foreach (array_merge(self::FINDER_PROPERTIES, ['missingComponentResolvers', 'resolvedComponentCache']) as $property) {
            $target = in_array($property, self::FINDER_PROPERTIES, true) ? $this->finder : $this->factory;

            if (! is_array($this->read($target, $property))) {
                throw new UnsupportedLivewireInternals("Livewire 4.3 internal [{$property}] must contain an array.");
            }
        }

        if ($this->read($this->factory, 'finder') !== $this->finder) {
            throw new UnsupportedLivewireInternals('Livewire Factory must reference the inspected Finder instance.');
        }

        if (! $this->read($this->factory, 'compiler') instanceof Compiler) {
            throw new UnsupportedLivewireInternals('Livewire 4.3 internal [compiler] must contain a Livewire Compiler.');
        }

        $this->assertCollectionShapes();
    }

    public function assertReservable(string $identifier, string $intrinsicComponent, Closure $auraResolver): void
    {
        $this->assertCompatible();
        $this->assertIdentifierUnclaimed(
            $identifier,
            $auraResolver,
            allowedConventionalClass: $intrinsicComponent,
            allowAuraReservation: false,
        );
    }

    public function assertUnclaimed(array $identifiers, Closure $auraResolver): void
    {
        $this->assertCompatible();

        foreach ($identifiers as $identifier) {
            $this->assertIdentifierUnclaimed($identifier, $auraResolver);
        }

        foreach ($identifiers as $identifier) {
            $this->assertStaticClaimsUnclaimed(
                $identifier,
                $this->normalizeIdentifier($identifier),
                allowedConventionalClass: null,
                allowAuraReservation: true,
            );
        }
    }

    private function assertCollectionShapes(): void
    {
        foreach (['classLocations', 'viewLocations'] as $property) {
            $locations = $this->read($this->finder, $property);

            if (! array_is_list($locations) || collect($locations)->contains(fn (mixed $location): bool => ! is_string($location))) {
                $this->unsupportedShape($property);
            }
        }

        $classNamespaces = $this->read($this->finder, 'classNamespaces');

        foreach ($classNamespaces as $namespace => $definition) {
            if (! is_string($namespace) || ! is_array($definition)
                || array_keys($definition) !== ['classNamespace', 'classPath', 'classViewPath']
                || ! is_string($definition['classNamespace'])
                || (! is_string($definition['classPath']) && $definition['classPath'] !== null)
                || (! is_string($definition['classViewPath']) && $definition['classViewPath'] !== null)) {
                $this->unsupportedShape('classNamespaces');
            }
        }

        foreach (['viewNamespaces', 'classComponents', 'viewComponents'] as $property) {
            $map = $this->read($this->finder, $property);

            foreach ($map as $name => $target) {
                if (! is_string($name) || ! is_string($target)) {
                    $this->unsupportedShape($property);
                }
            }
        }

        $resolvers = $this->read($this->factory, 'missingComponentResolvers');

        if (! array_is_list($resolvers) || collect($resolvers)->contains(fn (mixed $resolver): bool => ! is_callable($resolver))) {
            $this->unsupportedShape('missingComponentResolvers');
        }

        $cache = $this->read($this->factory, 'resolvedComponentCache');

        foreach ($cache as $name => $class) {
            if (! is_string($name) || ! is_string($class)) {
                $this->unsupportedShape('resolvedComponentCache');
            }
        }
    }

    private function assertIdentifierUnclaimed(
        string $identifier,
        Closure $auraResolver,
        ?string $allowedConventionalClass = null,
        bool $allowAuraReservation = true,
    ): void {
        $normalized = $this->normalizeIdentifier($identifier);
        $resolvers = $this->read($this->factory, 'missingComponentResolvers');

        $this->assertStaticClaimsUnclaimed(
            $identifier,
            $normalized,
            $allowedConventionalClass,
            $allowAuraReservation,
        );

        foreach ($resolvers as $resolver) {
            if ($resolver === $auraResolver) {
                continue;
            }

            try {
                $target = $resolver($normalized);
            } catch (Throwable $exception) {
                $this->collision($identifier, 'missing-resolver-error', $exception::class);
            }

            $this->assertStaticClaimsUnclaimed(
                $identifier,
                $normalized,
                $allowedConventionalClass,
                $allowAuraReservation,
            );

            if ($target) {
                $this->collision($identifier, 'missing-resolver', $target);
            }

            if ($this->read($this->factory, 'missingComponentResolvers') !== $resolvers) {
                $this->collision($identifier, 'missing-resolver-mutation', 'resolver list changed during inspection');
            }
        }
    }

    /** @param class-string $class */
    private function assertMethod(string $class, string $methodName, string $parameterName, string $returnType): void
    {
        $reflection = new ReflectionClass($class);

        if (! $reflection->hasMethod($methodName)) {
            throw new UnsupportedLivewireInternals("Livewire 4.3 method [{$class}::{$methodName}] is missing.");
        }

        $method = $reflection->getMethod($methodName);

        $parameters = $method->getParameters();
        $parameter = $parameters[0] ?? null;

        if (! $method->isPublic() || $method->isStatic()
            || count($parameters) !== 1
            || $method->getNumberOfRequiredParameters() !== 1
            || $parameter === null
            || $parameter->getName() !== $parameterName
            || $parameter->getType() !== null
            || $parameter->isPassedByReference()
            || $parameter->isVariadic()
            || (string) $method->getReturnType() !== $returnType) {
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

    private function assertStaticClaimsUnclaimed(
        string $identifier,
        string $normalized,
        ?string $allowedConventionalClass,
        bool $allowAuraReservation,
    ): void {
        $factoryCache = $this->read($this->factory, 'resolvedComponentCache');

        if (array_key_exists($normalized, $factoryCache)) {
            $this->collision($identifier, 'factory-cache', $factoryCache[$normalized]);
        }

        $classComponents = $this->read($this->finder, 'classComponents');

        if (array_key_exists($normalized, $classComponents)
            && ! ($allowAuraReservation && $this->isAuraReservation($identifier, $classComponents[$normalized]))) {
            $this->collision($identifier, 'explicit-class', $classComponents[$normalized]);
        }

        $viewComponents = $this->read($this->finder, 'viewComponents');

        if (array_key_exists($normalized, $viewComponents)) {
            $this->collision($identifier, 'explicit-view', $viewComponents[$normalized]);
        }

        [$namespace] = $this->finder->parseNamespaceAndName($normalized);
        $classNamespaces = $this->read($this->finder, 'classNamespaces');
        $viewNamespaces = $this->read($this->finder, 'viewNamespaces');

        if ($namespace !== null && array_key_exists($namespace, $classNamespaces)) {
            $this->collision($identifier, 'class-namespace', $classNamespaces[$namespace]);
        }

        if ($namespace !== null && array_key_exists($namespace, $viewNamespaces)) {
            $this->collision($identifier, 'view-namespace', $viewNamespaces[$namespace]);
        }

        $class = $this->finder->resolveClassComponentClassName($normalized);

        if ($class !== null
            && $class !== $allowedConventionalClass
            && ! ($allowAuraReservation && $this->isAuraReservation($identifier, $class))) {
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

    private function isAuraReservation(string $identifier, mixed $target): bool
    {
        return in_array($identifier, [
            'aura.base.livewire.global-search',
            'aura.base.livewire.media-manager',
        ], true) && $target === ComponentSlotAliasReservation::class;
    }

    private function normalizeIdentifier(string $identifier): string
    {
        $normalized = $this->finder->normalizeName($identifier);

        if (! is_string($normalized) || $normalized === '') {
            throw new UnsupportedLivewireInternals("Livewire could not normalize protected component identifier [{$identifier}].");
        }

        return $normalized;
    }

    private function read(object $target, string $propertyName): mixed
    {
        $property = new ReflectionProperty($target, $propertyName);

        return $property->getValue($target);
    }

    private function unsupportedShape(string $property): never
    {
        throw new UnsupportedLivewireInternals("Livewire 4.3 internal [{$property}] has an unsupported collection shape.");
    }
}
