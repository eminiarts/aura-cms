<?php

namespace Aura\Base\Livewire\Media;

use Aura\Base\Resource;

final readonly class AuthorizedMediaOwner
{
    /**
     * @param  array<string, mixed>  $field
     */
    public function __construct(
        public MediaOwnerContext $context,
        public Resource $resource,
        public array $field,
    ) {}
}
