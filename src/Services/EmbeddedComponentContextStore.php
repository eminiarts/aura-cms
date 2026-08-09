<?php

namespace Aura\Base\Services;

use Illuminate\Database\Eloquent\Model;

final class EmbeddedComponentContextStore
{
    /** @var array<string, Model> */
    private array $resources = [];

    public function find(string $signature): ?Model
    {
        return $this->resources[$signature] ?? null;
    }

    public function remember(string $signature, Model $resource): void
    {
        $this->resources[$signature] = $resource;
    }
}
