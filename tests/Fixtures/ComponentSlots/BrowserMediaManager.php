<?php

namespace Aura\Base\Tests\Fixtures\ComponentSlots;

use Aura\Base\Livewire\MediaManager;

class BrowserMediaManager extends MediaManager
{
    public string $componentSlotReplacement = 'media-manager';
}
