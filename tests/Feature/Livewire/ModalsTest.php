<?php

use Aura\Base\Livewire\Modals;
use Aura\Base\Tests\Fixtures\Modals\ModalStub;
use Livewire\Livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Livewire::component('modals-test-stub', ModalStub::class);
});

test('openModal accepts a single array payload with component key', function () {
    Livewire::test(Modals::class)
        ->call('openModal', [
            'component' => 'modals-test-stub',
            'arguments' => ['foo' => 'bar'],
            'modalAttributes' => ['slideOver' => true],
        ])
        ->assertSet('modals', function (array $modals) {
            expect($modals)->toHaveCount(1);

            $modal = array_values($modals)[0];

            expect($modal['name'])->toBe('modals-test-stub')
                ->and($modal['arguments'])->toBe(['foo' => 'bar'])
                ->and($modal['modalAttributes']['slideOver'])->toBeTrue()
                ->and($modal['active'])->toBeTrue();

            return true;
        });
});

test('openModal still accepts positional component arguments', function () {
    Livewire::test(Modals::class)
        ->call('openModal', 'modals-test-stub', ['id' => 1], ['persistent' => true])
        ->assertSet('modals', function (array $modals) {
            expect($modals)->toHaveCount(1);

            $modal = array_values($modals)[0];

            expect($modal['name'])->toBe('modals-test-stub')
                ->and($modal['arguments'])->toBe(['id' => 1])
                ->and($modal['modalAttributes']['persistent'])->toBeTrue()
                ->and($modal['active'])->toBeTrue();

            return true;
        });
});

test('modals view passes open state into the dialog component', function () {
    Livewire::test(Modals::class)
        ->call('openModal', 'modals-test-stub')
        ->assertSeeHtml('x-data="{ dialogOpen: true }"');
});
