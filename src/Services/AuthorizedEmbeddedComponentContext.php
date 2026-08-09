<?php

namespace Aura\Base\Services;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Arr;

final readonly class AuthorizedEmbeddedComponentContext
{
    /**
     * @param  array<string, mixed>  $parameters
     */
    public function __construct(
        public EmbeddedComponentSurface $surface,
        public Model $resource,
        public string $fieldSlug,
        public string $componentAlias,
        public array $parameters,
    ) {}

    public function parameter(string $key, mixed $default = null): mixed
    {
        return Arr::get($this->parameters, $key, $default);
    }
}
