<?php

use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Tests\Fixtures\ComponentSlots\PluginGlobalSearch;
use Aura\Base\Tests\Support\ComponentSlotPluginBeforeAuraTestCase;
use Livewire\Livewire;

uses(ComponentSlotPluginBeforeAuraTestCase::class);

test('a plugin component slot wins when its provider boots before Aura', function () {
    $registry = app(ComponentSlotRegistry::class);

    expect($registry->winner('global-search'))->toBe(PluginGlobalSearch::class)
        ->and(app('livewire.factory')->resolveComponentNameAndClass('aura::global-search')[1])
        ->toBe(PluginGlobalSearch::class)
        ->and(app('livewire.factory')->resolveComponentNameAndClass('aura.base.livewire.global-search')[1])
        ->toBe(PluginGlobalSearch::class);

    Livewire::test('aura::global-search')
        ->call('runSearch', 'hydrated')
        ->assertSet('query', 'hydrated');
});
