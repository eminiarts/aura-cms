<?php

use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Livewire\Modals;

test('modal host resolves a private component slot transport before reading modal classes', function () {
    $modals = new Modals;

    $modals->openModal(
        ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID,
        modalAttributes: ['persistent' => false],
    );

    $modal = array_values($modals->modals)[0];

    expect($modal['name'])->toBe(ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID)
        ->and($modal['modalAttributes']['modalClasses'])->toBe('max-w-7xl')
        ->and($modal['modalAttributes']['persistent'])->toBeTrue();
});
