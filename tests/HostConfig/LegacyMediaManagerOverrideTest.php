<?php

use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Tests\Fixtures\ComponentSlots\HostMediaManager;
use Aura\Base\Tests\Support\LegacyMediaManagerOverrideTestCase;

uses(LegacyMediaManagerOverrideTestCase::class);

test('legacy media manager config remains the winner for its full compatibility window', function () {
    $registry = app(ComponentSlotRegistry::class);

    expect($registry->winner('media-manager'))->toBe(HostMediaManager::class)
        ->and(app('livewire.factory')->resolveComponentNameAndClass(ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID)[1])
        ->toBe(HostMediaManager::class)
        ->and(app('livewire.factory')->resolveComponentNameAndClass('aura::media-manager')[1])
        ->toBe(HostMediaManager::class)
        ->and(app('livewire.factory')->resolveComponentNameAndClass('aura.base.livewire.media-manager')[1])
        ->toBe(HostMediaManager::class);
});
