<?php

namespace Aura\Base\RecordLayout;

use Aura\Base\Resource;
use InvalidArgumentException;
use Livewire\Exceptions\ComponentNotFoundException;
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

    public function __construct(private readonly RecordLayoutPanelValidator $validator) {}

    /** @param  list<class-string>  $resources */
    public function captureBaselineState(array $resources = []): void
    {
        foreach ($resources as $resource) {
            $this->registerResourcePanels($resource);
        }

        if (count($this->panels) > self::MAX_PANELS) {
            throw new InvalidArgumentException('The record layout panel registry limit was exceeded.');
        }

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

    /** @param  class-string  $resource */
    private function registerResourcePanels(string $resource): void
    {
        if (! is_subclass_of($resource, DefinesRecordLayoutPanels::class)) {
            return;
        }

        $source = 'resource/record-layout-'.substr(hash('sha256', $resource), 0, 16);

        foreach ($resource::recordLayoutPanels() as $panel) {
            if (! $panel instanceof RecordLayoutPanel) {
                throw new InvalidArgumentException("Resource [{$resource}] must return immutable record layout panels.");
            }

            $scopedPanel = new RecordLayoutPanel(
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
            $registered = new RegisteredRecordLayoutPanel($source, $scopedPanel);
            $this->validator->validate($source, $scopedPanel);
            $this->registerTransport($registered);
            $this->panels[$registered->identity()] = $registered;
        }
    }

    private function registerTransport(RegisteredRecordLayoutPanel $registered): void
    {
        try {
            $existing = app('livewire.factory')->resolveComponentClass($registered->transport());
        } catch (ComponentNotFoundException) {
            Livewire::component($registered->transport(), $registered->panel->component);

            return;
        }

        if ($existing !== $registered->panel->component) {
            throw new InvalidRecordLayoutPanel(
                "Record layout panel [{$registered->identity()}] transport [{$registered->transport()}] is already claimed."
            );
        }
    }
}
