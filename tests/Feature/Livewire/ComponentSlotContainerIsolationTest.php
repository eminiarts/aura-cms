<?php

use Aura\Base\Livewire\ComponentSlots\ComponentSlotCandidateValidator;
use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Livewire\ComponentSlots\Livewire43CollisionInspector;
use Aura\Base\Livewire\ComponentSlots\LivewireComponentSlotBridge;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Livewire\MediaManager;
use Aura\Base\Tests\Fixtures\ComponentSlots\PluginGlobalSearch;
use Illuminate\Config\Repository;
use Illuminate\Container\Container;
use Livewire\Compiler\Compiler;
use Livewire\Factory\Factory;
use Livewire\Finder\Finder;

class IsolatedLivewireComponentSlotBridge implements LivewireComponentSlotBridge
{
    public function __construct(
        private readonly Livewire43CollisionInspector $inspector,
        private readonly Finder $finder,
        private readonly Factory $factory,
    ) {}

    public function assertCompatible(): void
    {
        $this->inspector->assertCompatible();
    }

    public function assertUnclaimed(array $identifiers, Closure $auraResolver): void
    {
        $this->inspector->assertUnclaimed($identifiers, $auraResolver);
    }

    public function installMissingResolver(Closure $resolver): void
    {
        $this->factory->resolveMissingComponent($resolver);
    }

    public function register(string $name, string $component): void
    {
        $this->finder->addComponent(name: $name, class: $component);
    }

    public function resolve(string $name): array
    {
        return $this->factory->resolveComponentNameAndClass($name);
    }
}

function isolatedComponentSlotContainer(): Container
{
    $container = new Container;
    $config = new Repository([
        'aura' => [
            'component-slots' => [
                'global-search' => null,
                'media-manager' => null,
            ],
            'components' => [
                'media-manager' => MediaManager::class,
            ],
        ],
    ]);
    $finder = new Finder;
    $factory = new Factory($finder, Mockery::mock(Compiler::class));
    $bridge = new IsolatedLivewireComponentSlotBridge(
        new Livewire43CollisionInspector($finder, $factory),
        $finder,
        $factory,
    );
    $registry = new ComponentSlotRegistry(
        $config,
        new ComponentSlotCandidateValidator($container),
        $bridge,
    );

    $container->instance(Repository::class, $config);
    $container->instance(Finder::class, $finder);
    $container->instance(Factory::class, $factory);
    $container->instance(ComponentSlotRegistry::class, $registry);

    return $container;
}

test('two independent containers receive fresh registries factories caches and aliases', function () {
    $first = isolatedComponentSlotContainer();
    $second = isolatedComponentSlotContainer();
    $firstRegistry = $first->make(ComponentSlotRegistry::class);
    $secondRegistry = $second->make(ComponentSlotRegistry::class);

    $firstRegistry->install();
    $secondRegistry->install();
    $firstRegistry->register('fixture/component-slots', [
        'global-search' => PluginGlobalSearch::class,
    ]);
    $firstRegistry->finalize();
    $secondRegistry->finalize();

    expect($firstRegistry)->not->toBe($secondRegistry)
        ->and($first->make(Finder::class))->not->toBe($second->make(Finder::class))
        ->and($first->make(Factory::class))->not->toBe($second->make(Factory::class))
        ->and($firstRegistry->winner('global-search'))->toBe(PluginGlobalSearch::class)
        ->and($secondRegistry->winner('global-search'))->toBe(GlobalSearch::class)
        ->and($first->make(Factory::class)->resolveComponentNameAndClass('aura::global-search')[1])
        ->toBe(PluginGlobalSearch::class)
        ->and($second->make(Factory::class)->resolveComponentNameAndClass('aura::global-search')[1])
        ->toBe(GlobalSearch::class)
        ->and($first->make(Factory::class)->resolveComponentNameAndClass('aura.base.livewire.global-search')[1])
        ->toBe(PluginGlobalSearch::class)
        ->and($second->make(Factory::class)->resolveComponentNameAndClass('aura.base.livewire.global-search')[1])
        ->toBe(GlobalSearch::class);
});
