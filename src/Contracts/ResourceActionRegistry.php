<?php

namespace Aura\Base\Contracts;

use Aura\Base\Resource;

interface ResourceActionRegistry
{
    /**
     * Return the registered actions that are visible to an actor for a Resource.
     *
     * @return array<string, array<string, mixed>>
     */
    public function actionsFor(Resource $resource, mixed $actor): array;

    /**
     * Capture package registrations made during application boot.
     */
    public function captureBaselineState(): void;

    /**
     * Execute one registered action after a fresh server-side authorization check.
     */
    public function execute(string $name, Resource $resource, mixed $actor): mixed;

    /**
     * Restore the boot-time registrations at a worker boundary.
     */
    public function flushState(): void;

    /**
     * Register a namespaced action definition.
     *
     * @param  array<string, mixed>  $definition
     */
    public function register(string $name, array $definition): void;
}
