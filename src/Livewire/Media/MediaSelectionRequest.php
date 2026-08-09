<?php

namespace Aura\Base\Livewire\Media;

final readonly class MediaSelectionRequest
{
    public function __construct(
        public string $token,
        public string $digest,
        public MediaSelectionRecord $record,
    ) {}
}
