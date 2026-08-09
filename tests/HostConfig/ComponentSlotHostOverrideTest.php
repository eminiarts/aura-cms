<?php

use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Tests\Fixtures\ComponentSlots\HostGlobalSearch;
use Aura\Base\Tests\Support\ComponentSlotHostOverrideTestCase;
use Livewire\Livewire;

uses(ComponentSlotHostOverrideTestCase::class);

test('a host component slot overrides the Aura default throughout Livewire', function () {
    $registry = app(ComponentSlotRegistry::class);

    expect($registry->winner('global-search'))->toBe(HostGlobalSearch::class)
        ->and(app('livewire.factory')->resolveComponentNameAndClass(ComponentSlotRegistry::GLOBAL_SEARCH_TRANSPORT_ID)[1])
        ->toBe(HostGlobalSearch::class)
        ->and(app('livewire.factory')->resolveComponentNameAndClass('aura::global-search')[1])
        ->toBe(HostGlobalSearch::class)
        ->and(app('livewire.factory')->resolveComponentNameAndClass('aura.base.livewire.global-search')[1])
        ->toBe(HostGlobalSearch::class);

    foreach ([
        ComponentSlotRegistry::GLOBAL_SEARCH_TRANSPORT_ID,
        'aura::global-search',
        'aura.base.livewire.global-search',
    ] as $identifier) {
        Livewire::test($identifier)
            ->call('runSearch', 'hydrated')
            ->assertSet('query', 'hydrated')
            ->assertSee('Host search: hydrated');
    }
});
