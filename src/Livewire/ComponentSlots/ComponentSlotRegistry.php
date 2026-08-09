<?php

namespace Aura\Base\Livewire\ComponentSlots;

use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Livewire\MediaManager;
use Closure;
use Illuminate\Contracts\Config\Repository;
use Livewire\Component;

class ComponentSlotRegistry
{
    public const GLOBAL_SEARCH_TRANSPORT_ID = 'aura-slot-5d08acbafc1799908d00c98ba128984f725d8bc43d13679c7689c9e24e2c107c';

    public const MEDIA_MANAGER_TRANSPORT_ID = 'aura-slot-f16ee9c2b47b1df672e85903a69ffc98066ccd885e96e5f18536c737f00c5a88';

    /**
     * @var array<string, array{
     *     default: class-string<Component>,
     *     config: string,
     *     transport: string,
     *     aliases: list<string>
     * }>
     */
    private const DEFINITIONS = [
        'global-search' => [
            'default' => GlobalSearch::class,
            'config' => 'aura.component-slots.global-search',
            'transport' => self::GLOBAL_SEARCH_TRANSPORT_ID,
            'aliases' => [
                'aura::global-search',
                'aura.base.livewire.global-search',
            ],
        ],
        'media-manager' => [
            'default' => MediaManager::class,
            'config' => 'aura.component-slots.media-manager',
            'transport' => self::MEDIA_MANAGER_TRANSPORT_ID,
            'aliases' => [
                'aura::media-manager',
                'aura.base.livewire.media-manager',
            ],
        ],
    ];

    /**
     * @var array<string, array<string, mixed>>
     */
    private array $declarations = [];

    private bool $internalAliasResolution = false;

    private ?Closure $resolver = null;

    private string $state = 'collecting';

    /**
     * @var array<string, class-string<Component>>
     */
    private array $winners = [];

    public function __construct(
        private readonly Repository $config,
        private readonly ComponentSlotCandidateValidator $validator,
        private readonly LivewireComponentSlotBridge $bridge,
    ) {}

    public function finalize(): void
    {
        if ($this->resolver === null || $this->state !== 'collecting') {
            throw new ComponentSlotLifecycleException('Component slots can finalize exactly once after resolver installation while collecting.');
        }

        $this->state = 'finalizing';
        $this->winners = $this->selectWinners();

        $this->bridge->assertUnclaimed($this->identifiers(), $this->resolver);

        foreach (self::DEFINITIONS as $slot => $definition) {
            $winner = $this->winners[$slot];
            $this->bridge->register($definition['transport'], $winner);
            $this->assertResolution($definition['transport'], $winner);
        }

        $this->internalAliasResolution = true;

        try {
            foreach (self::DEFINITIONS as $slot => $definition) {
                foreach ($definition['aliases'] as $alias) {
                    $this->assertResolution($alias, $this->winners[$slot]);
                }
            }
        } finally {
            $this->internalAliasResolution = false;
        }

        $this->state = 'finalized';
    }

    /**
     * @param  array<string, class-string<Component>>  $fallbackComponents
     */
    public function install(array $fallbackComponents = []): void
    {
        if ($this->resolver !== null || $this->state !== 'collecting') {
            throw new ComponentSlotLifecycleException('Component slot resolver can be installed exactly once while collecting.');
        }

        $this->bridge->assertCompatible();

        $this->resolver = function (?string $name) use ($fallbackComponents): string|null {
            $slot = $this->slotForAlias($name);

            if ($slot === null) {
                return $name === null ? null : ($fallbackComponents[$name] ?? null);
            }

            if ($this->state === 'collecting') {
                throw new ComponentSlotLifecycleException("Component slot alias [{$name}] cannot resolve before finalization.");
            }

            if ($this->state === 'finalizing' && ! $this->internalAliasResolution) {
                throw new ComponentSlotLifecycleException("Component slot alias [{$name}] cannot resolve during external finalization.");
            }

            return $this->winners[$slot] ?? null;
        };

        $this->bridge->installMissingResolver($this->resolver);
    }

    /**
     * @param  array<string, mixed>  $slots
     */
    public function register(string $source, array $slots): void
    {
        if ($this->state !== 'collecting') {
            throw new ComponentSlotLifecycleException('Component slot declarations are accepted only while collecting.');
        }

        if (preg_match('/^[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?$/', $source) !== 1) {
            throw new ComponentSlotConflict("Component slot source [{$source}] must be a lowercase Composer package name.");
        }

        foreach ($slots as $slot => $candidate) {
            if (! is_string($slot) || ! array_key_exists($slot, self::DEFINITIONS)) {
                $label = is_string($slot) ? $slot : get_debug_type($slot);

                throw new ComponentSlotConflict("Unknown component slot [{$label}] from source [{$source}].");
            }

            if (array_key_exists($source, $this->declarations[$slot] ?? [])) {
                if ($this->declarations[$slot][$source] === $candidate) {
                    continue;
                }

                throw new ComponentSlotConflict("Source [{$source}] already declared a different candidate for component slot [{$slot}].");
            }

            $this->declarations[$slot][$source] = $candidate;
        }
    }

    public function state(): string
    {
        return $this->state;
    }

    /**
     * @return class-string<Component>
     */
    public function winner(string $slot): string
    {
        if ($this->state !== 'finalized') {
            throw new ComponentSlotLifecycleException("Component slot winner [{$slot}] cannot resolve before finalization.");
        }

        if (! array_key_exists($slot, self::DEFINITIONS)) {
            throw new ComponentSlotConflict("Unknown component slot [{$slot}].");
        }

        return $this->winners[$slot];
    }

    /**
     * @param  class-string<Component>  $expected
     */
    private function assertResolution(string $identifier, string $expected): void
    {
        [$resolvedName, $resolvedClass] = $this->bridge->resolve($identifier);

        if ($resolvedName !== $identifier || $resolvedClass !== $expected) {
            throw new ComponentSlotConflict(
                "Livewire component slot assertion failed for [{$identifier}]: expected [{$expected}], got [{$resolvedName}] => [{$resolvedClass}]."
            );
        }
    }

    /**
     * @return list<string>
     */
    private function identifiers(): array
    {
        $identifiers = [];

        foreach (self::DEFINITIONS as $definition) {
            $identifiers[] = $definition['transport'];
            array_push($identifiers, ...$definition['aliases']);
        }

        return $identifiers;
    }

    /**
     * @return array<string, class-string<Component>>
     */
    private function selectWinners(): array
    {
        $winners = [];

        foreach (self::DEFINITIONS as $slot => $definition) {
            $default = $this->validator->validate($slot, 'aura', $definition['default']);
            $plugins = $this->validatedPluginCandidates($slot);
            $host = $this->validatedHostCandidate($slot, $definition);

            if ($host !== null) {
                $winners[$slot] = $host;

                continue;
            }

            $distinctPlugins = array_values(array_unique($plugins, SORT_STRING));

            if (count($distinctPlugins) > 1) {
                $sources = array_keys($plugins);
                sort($sources, SORT_STRING);

                throw new ComponentSlotConflict(
                    "Ambiguous plugin candidates for component slot [{$slot}] from sources [".implode(', ', $sources)."]. Configure [{$definition['config']}] to select a host winner."
                );
            }

            $winners[$slot] = $distinctPlugins[0] ?? $default;
        }

        return $winners;
    }

    private function slotForAlias(?string $alias): ?string
    {
        if ($alias === null) {
            return null;
        }

        foreach (self::DEFINITIONS as $slot => $definition) {
            if (in_array($alias, $definition['aliases'], true)) {
                return $slot;
            }
        }

        return null;
    }

    /**
     * @param  array{default: class-string<Component>, config: string, transport: string, aliases: list<string>}  $definition
     * @return class-string<Component>|null
     */
    private function validatedHostCandidate(string $slot, array $definition): ?string
    {
        $configured = $this->config->get($definition['config']);

        if ($slot !== 'media-manager') {
            return $configured === null
                ? null
                : $this->validator->validate($slot, 'host', $configured);
        }

        $legacy = $this->config->get('aura.components.media-manager', MediaManager::class);
        $legacyCandidate = $legacy === MediaManager::class
            ? null
            : $this->validator->validate($slot, 'host legacy aura.components.media-manager', $legacy);
        $configuredCandidate = $configured === null
            ? null
            : $this->validator->validate($slot, 'host', $configured);

        if ($configuredCandidate !== null && $legacyCandidate !== null && $configuredCandidate !== $legacyCandidate) {
            throw new ComponentSlotConflict(
                'Conflicting media-manager host choices in [aura.component-slots.media-manager] and legacy [aura.components.media-manager].'
            );
        }

        return $configuredCandidate ?? $legacyCandidate;
    }

    /**
     * @return array<string, class-string<Component>>
     */
    private function validatedPluginCandidates(string $slot): array
    {
        $declarations = $this->declarations[$slot] ?? [];
        ksort($declarations, SORT_STRING);

        $validated = [];

        foreach ($declarations as $source => $candidate) {
            $validated[$source] = $this->validator->validate($slot, $source, $candidate);
        }

        return $validated;
    }
}
