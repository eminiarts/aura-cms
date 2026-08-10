<?php

use Aura\Base\Tests\Resources\GalleryPage;

test('a property-declared record action executes from the rendered menu', function () {
    $this->actingAs(createSuperAdmin());
    $resource = GalleryPage::create([
        'title' => 'Browser action record',
        'type' => GalleryPage::getType(),
        'status' => 'publish',
        ...config('aura.teams') ? ['team_id' => auth()->user()->current_team_id] : [],
    ]);

    $page = visit('/admin/gallery-page/'.$resource->getKey().'/edit');

    $page->assertSee('Actions')
        ->click('Actions')
        ->click('Mark reviewed')
        ->wait(1)
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();

    expect($resource->fresh()->content)->toBe('reviewed');
});
