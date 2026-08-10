<?php

use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Livewire\EmbeddedComponentAuthorizationHook;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Services\EmbeddedComponentContextCodec;
use Aura\Base\Tests\Support\AuraBeforeLivewireTestCase;
use Livewire\ComponentHookRegistry;
use Livewire\Features\SupportEvents\SupportEvents;
use Livewire\Features\SupportLifecycleHooks\SupportLifecycleHooks;
use Livewire\LivewireServiceProvider;

use function Livewire\trigger;

uses(AuraBeforeLivewireTestCase::class);

test('Aura boots when package discovery registers it before Livewire', function () {
    $livewireProvider = app()->getProvider(LivewireServiceProvider::class);

    expect($livewireProvider)->toBeInstanceOf(LivewireServiceProvider::class)
        ->and(app()->bound('livewire.finder'))->toBeTrue()
        ->and(app()->bound('livewire.factory'))->toBeTrue()
        ->and(app(ComponentSlotRegistry::class)->winner('global-search'))->toBe(GlobalSearch::class)
        ->and(app('livewire.factory')->resolveComponentNameAndClass('aura::global-search')[1])
        ->toBe(GlobalSearch::class)
        ->and(app()->register(LivewireServiceProvider::class))->toBe($livewireProvider);
});

test('Aura registers embedded authorization before executable Livewire hooks', function () {
    $reflection = new ReflectionClass(ComponentHookRegistry::class);
    $hooks = $reflection->getStaticPropertyValue('componentHooks');
    $authorizationHookIndex = array_search(EmbeddedComponentAuthorizationHook::class, $hooks, true);

    expect($authorizationHookIndex)->not->toBeFalse()
        ->and($authorizationHookIndex)->toBeLessThan(array_search(SupportEvents::class, $hooks, true))
        ->and($authorizationHookIndex)->toBeLessThan(array_search(SupportLifecycleHooks::class, $hooks, true));
});

test('Aura keeps the embedded request preflight listener when it registers Livewire', function () {
    $requestPreflight = new class
    {
        public int $calls = 0;

        public function primeLivewireRequest(array $requestPayload): void
        {
            $this->calls++;
        }
    };

    app()->instance(EmbeddedComponentContextCodec::class, $requestPreflight);

    trigger('request', []);

    expect($requestPreflight->calls)->toBe(1);
});
