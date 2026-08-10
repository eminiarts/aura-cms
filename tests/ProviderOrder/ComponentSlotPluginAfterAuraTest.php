<?php

use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Tests\Fixtures\ComponentSlots\PluginGlobalSearch;
use Aura\Base\Tests\Support\ComponentSlotPluginAfterAuraTestCase;
use Livewire\Livewire;

uses(ComponentSlotPluginAfterAuraTestCase::class);

test('a plugin component slot wins when its provider boots after Aura', function () {
    $registry = app(ComponentSlotRegistry::class);

    expect($registry->winner('global-search'))->toBe(PluginGlobalSearch::class)
        ->and(app('livewire.factory')->resolveComponentNameAndClass('aura::global-search')[1])
        ->toBe(PluginGlobalSearch::class)
        ->and(app('livewire.factory')->resolveComponentNameAndClass('aura.base.livewire.global-search')[1])
        ->toBe(PluginGlobalSearch::class);

    Livewire::test('aura.base.livewire.global-search')
        ->call('runSearch', 'hydrated')
        ->assertSet('query', 'hydrated');
});
