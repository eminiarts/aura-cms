<?php

namespace Aura\Base\RecordLayout;

use Aura\Base\Livewire\ComponentSlots\LivewireCollisionInspector;
use Aura\Base\Preferences\PreferenceRegistry;
use Aura\Base\Preferences\PreferenceScope;
use Aura\Base\Preferences\PreferenceValueType;
use Aura\Base\Resource;
use InvalidArgumentException;
use Livewire\Livewire;

final class RecordLayoutRegistry
{
    public const MAX_PANELS = 100;

    public const MAX_RELATIONSHIPS_PER_PANEL = 12;

    public const MAX_RESOURCES_PER_PANEL = 32;

    /** @var array<string, RegisteredRecordLayoutPanel> */
    private array $baselinePanels = [];

    private bool $finalized = false;

    /** @var array<string, RegisteredRecordLayoutPanel> */
    private array $panels = [];

    public function __construct(
        private readonly LivewireCollisionInspector $collisionInspector,
        private readonly PreferenceRegistry $preferences,
        private readonly RecordLayoutPanelValidator $validator,
    ) {}

    /** @param  list<class-string>  $resources */
    public function captureBaselineState(array $resources = []): void
    {
        foreach ($resources as $resource) {
            $this->registerResourcePanels($resource);
        }

        if (count($this->panels) > self::MAX_PANELS) {
            throw new InvalidArgumentException('The record layout panel registry limit was exceeded.');
        }

        $this->validatePreferences();

        $this->baselinePanels = $this->panels;
        $this->finalized = true;
    }

    public function flushState(): void
    {
        $this->panels = $this->baselinePanels;
    }

    /** @return list<RegisteredRecordLayoutPanel> */
    public function panelsFor(Resource $resource): array
    {
        $panels = array_filter(
            $this->panels,
            fn (RegisteredRecordLayoutPanel $registered): bool => $this->matchesResource($registered->panel, $resource),
        );

        usort($panels, static fn (RegisteredRecordLayoutPanel $left, RegisteredRecordLayoutPanel $right): int => [
            $left->panel->region->value,
            $left->panel->order,
            $left->source,
            $left->panel->key,
        ] <=> [
            $right->panel->region->value,
            $right->panel->order,
            $right->source,
            $right->panel->key,
        ]);

        return array_values($panels);
    }

    /**
     * @param  list<RecordLayoutPanel>  $panels
     */
    public function register(string $source, array $panels): void
    {
        if ($this->finalized) {
            throw new InvalidArgumentException('Record layout panels must be registered before the application finishes booting.');
        }

        if (preg_match('/\A[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\/[a-z0-9](?:[a-z0-9._-]*[a-z0-9])?\z/D', $source) !== 1) {
            throw new InvalidArgumentException("Record layout source [{$source}] must be a lowercase Composer package name.");
        }

        $this->mergePanels($source, $panels);
    }

    private function matchesResource(RecordLayoutPanel $panel, Resource $resource): bool
    {
        foreach ($panel->resources as $candidate) {
            if ($candidate === '*'
                || $candidate === $resource::class
                || $candidate === $resource->getSlug()
                || $candidate === $resource->getType()) {
                return true;
            }
        }

        return false;
    }

    /** @param  list<RecordLayoutPanel>  $panels */
    private function mergePanels(string $source, array $panels): void
    {
        $pending = $this->panels;

        foreach ($panels as $panel) {
            if (! $panel instanceof RecordLayoutPanel) {
                throw new InvalidArgumentException("Record layout source [{$source}] must register immutable panel definitions.");
            }

            $registered = new RegisteredRecordLayoutPanel($source, $panel);
            $identity = $registered->identity();
            $this->validator->validate($source, $panel);

            if (isset($pending[$identity])) {
                if ($pending[$identity] == $registered) {
                    continue;
                }

                throw new InvalidArgumentException("Record layout panel [{$identity}] is already registered differently.");
            }

            $pending[$identity] = $registered;
        }

        if (count($pending) > self::MAX_PANELS) {
            throw new InvalidArgumentException('The record layout panel registry limit was exceeded.');
        }

        foreach (array_diff_key($pending, $this->panels) as $registered) {
            $this->registerTransport($registered);
        }

        $this->panels = $pending;
    }

    /** @param  class-string  $resource */
    private function registerResourcePanels(string $resource): void
    {
        if (! is_subclass_of($resource, DefinesRecordLayoutPanels::class)) {
            return;
        }

        $source = 'resource/record-layout-'.substr(hash('sha256', $resource), 0, 16);

        $panels = [];

        foreach ($resource::recordLayoutPanels() as $panel) {
            if (! $panel instanceof RecordLayoutPanel) {
                throw new InvalidArgumentException("Resource [{$resource}] must return immutable record layout panels.");
            }

            $panels[] = new RecordLayoutPanel(
                key: $panel->key,
                region: $panel->region,
                component: $panel->component,
                order: $panel->order,
                resources: [$resource],
                ability: $panel->ability,
                visible: $panel->visible,
                preferenceKey: $panel->preferenceKey,
                eagerLoad: $panel->eagerLoad,
            );
        }

        $this->mergePanels($source, $panels);
    }

    private function registerTransport(RegisteredRecordLayoutPanel $registered): void
    {
        $this->collisionInspector->assertReservable(
            $registered->transport(),
            $registered->panel->component,
            static fn (?string $name): ?string => null,
        );
        Livewire::component($registered->transport(), $registered->panel->component);
    }

    private function validatePreferences(): void
    {
        foreach ($this->panels as $registered) {
            $key = $registered->panel->preferenceKey;

            if ($key === null) {
                continue;
            }

            try {
                $definition = $this->preferences->get($key);
            } catch (InvalidArgumentException) {
                throw new InvalidRecordLayoutPanel(
                    "Record layout panel [{$registered->identity()}] preference [{$key}] is not registered."
                );
            }

            if ($definition->type !== PreferenceValueType::Boolean
                || (! $definition->supports(PreferenceScope::User)
                    && ! $definition->supports(PreferenceScope::Team))) {
                throw new InvalidRecordLayoutPanel(
                    "Record layout panel [{$registered->identity()}] preference [{$key}] must be a user or team boolean."
                );
            }
        }
    }
}
