<?php

use Aura\Base\Fields\Image;
use Aura\Base\Livewire\ComponentSlots\ComponentSlotRegistry;
use Aura\Base\Livewire\Media\MediaOwnerTokenBroker;
use Aura\Base\Livewire\Modals;
use Aura\Base\Tests\Resources\Post;

test('modal host resolves a private component slot transport before reading modal classes', function () {
    $this->actingAs($actor = createSuperAdmin());
    app('aura')::registerResources([Post::class]);
    $ownerToken = app(MediaOwnerTokenBroker::class)->issue(
        ownerComponentId: 'modal-resolution-owner',
        modelClass: Post::class,
        modelKey: null,
        action: 'create',
        slug: 'image',
        fieldType: Image::class,
        actor: $actor,
    );
    $modals = new Modals;

    $modals->openModal(
        ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID,
        arguments: [
            'model' => Post::class,
            'slug' => 'image',
            'selected' => [],
            'ownerToken' => $ownerToken,
        ],
        modalAttributes: ['persistent' => false],
    );

    $modal = array_values($modals->modals)[0];

    expect($modal['name'])->toBe(ComponentSlotRegistry::MEDIA_MANAGER_TRANSPORT_ID)
        ->and($modal['modalAttributes']['modalClasses'])->toBe('max-w-7xl')
        ->and($modal['modalAttributes']['persistent'])->toBeTrue();
});
