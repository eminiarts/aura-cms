<?php

namespace Aura\Base\Livewire\Media;

use Closure;

final readonly class MediaSelectionMutation
{
    public function __construct(
        public Closure $apply,
        public Closure $rollback,
    ) {}
}
