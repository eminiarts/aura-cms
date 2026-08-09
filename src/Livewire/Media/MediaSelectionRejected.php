<?php

namespace Aura\Base\Livewire\Media;

use RuntimeException;

class MediaSelectionRejected extends RuntimeException
{
    public function __construct(public readonly string $errorCode)
    {
        parent::__construct('The media selection was rejected.');
    }
}
