<?php

namespace Aura\Base\Services;

use Illuminate\Database\Eloquent\Model;

final readonly class EmbeddedComponentContext
{
    /**
     * @param  array<string, mixed>  $field
     */
    public function __construct(
        public EmbeddedComponentSurface $surface,
        public Model $resource,
        public array $field,
        public mixed $value = null,
    ) {}
}
