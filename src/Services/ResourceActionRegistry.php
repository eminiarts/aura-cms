<?php

namespace Aura\Base\Services;

use Aura\Base\Contracts\ResourceActionRegistry as ResourceActionRegistryContract;
use Aura\Base\Exceptions\ResourceActionConflict;
use Aura\Base\Resource;
use Closure;
use Illuminate\Auth\Access\AuthorizationException;
use InvalidArgumentException;

class ResourceActionRegistry implements ResourceActionRegistryContract
{
    /** @var array<string, array<string, mixed>> */
    private array $actions = [];

    /** @var array<string, array<string, mixed>> */
    private array $baselineActions = [];

    public function actionsFor(Resource $resource, mixed $actor): array
    {
        $visible = [];

        foreach ($this->actions as $name => $definition) {
            if (! $this->matches($definition['supports'] ?? null, $resource, $actor)) {
                continue;
            }

            if (! $this->matches($definition['visible'] ?? null, $resource, $actor)) {
                continue;
            }

            $visible[$name] = collect($definition)
                ->except(['authorize', 'handler', 'supports', 'visible'])
                ->all();
        }

        return $visible;
    }

    public function captureBaselineState(): void
    {
        $this->baselineActions = $this->actions;
    }

    public function execute(string $name, Resource $resource, mixed $actor): mixed
    {
        $definition = $this->actions[$name] ?? null;

        if (! is_array($definition)
            || ! $this->matches($definition['supports'] ?? null, $resource, $actor)
            || ! $this->matches($definition['authorize'] ?? null, $resource, $actor)) {
            throw new AuthorizationException('You are not authorized to perform this action.');
        }

        $handler = $definition['handler'] ?? null;

        if (! is_callable($handler)) {
            throw new InvalidArgumentException("Resource action [{$name}] has no callable handler.");
        }

        return $handler($resource, $actor);
    }

    public function flushState(): void
    {
        $this->actions = $this->baselineActions;
    }

    public function register(string $name, array $definition): void
    {
        if (! preg_match('/^[a-z0-9][a-z0-9._-]+\.[a-z0-9][a-z0-9._-]+$/', $name)) {
            throw new InvalidArgumentException('Resource action names must be namespaced, for example [vendor.action].');
        }

        if (isset($this->actions[$name])) {
            throw new ResourceActionConflict("Resource action [{$name}] is already registered.");
        }

        if (! isset($definition['label']) || trim((string) $definition['label']) === '') {
            throw new InvalidArgumentException("Resource action [{$name}] requires a label.");
        }

        $this->actions[$name] = $definition;
    }

    private function matches(mixed $callback, Resource $resource, mixed $actor): bool
    {
        if ($callback === null) {
            return true;
        }

        if (! $callback instanceof Closure && ! is_callable($callback)) {
            throw new InvalidArgumentException('Resource action predicates must be callable.');
        }

        return (bool) $callback($resource, $actor);
    }
}
