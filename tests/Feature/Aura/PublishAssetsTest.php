<?php

use Aura\Base\Support\PublishedAssets;
use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

it('publishes assets from the local asset warning', function () {
    $this->actingAs(createSuperAdmin());
    app()['env'] = 'local';

    File::ensureDirectoryExists(public_path('vendor/aura'));
    File::put(public_path('vendor/aura/manifest.json'), "{}\n");

    $this->get(route('aura.dashboard'))
        ->assertSuccessful()
        ->assertSee('Publish assets')
        ->assertSee(route('aura.assets.publish'), false);

    $this->from(route('aura.dashboard'))
        ->post(route('aura.assets.publish'), ['_token' => csrf_token()])
        ->assertRedirect(route('aura.dashboard'))
        ->assertSessionMissing('aura.assets-publish-error');

    expect(PublishedAssets::areCurrent())->toBeTrue();

    $this->get(route('aura.dashboard'))
        ->assertSuccessful()
        ->assertDontSee('Publish assets')
        ->assertDontSee('The published assets are not up-to-date');
});

it('rejects asset publishing outside the local environment', function (string $environment) {
    $this->actingAs(createSuperAdmin());
    app()['env'] = $environment;

    $this->post(route('aura.assets.publish'), ['_token' => csrf_token()])->assertNotFound();

    $this->get(route('aura.dashboard'))
        ->assertSuccessful()
        ->assertDontSee('Publish assets');
})->with(['production', 'staging', 'testing']);

it('requires authentication to publish assets', function () {
    app()['env'] = 'local';

    $this->withSession(['_token' => 'publish-assets-token'])
        ->postJson(route('aura.assets.publish'), ['_token' => 'publish-assets-token'])
        ->assertUnauthorized();
});

it('reports a failed asset publish on the warning', function () {
    $this->actingAs(createSuperAdmin());
    app()['env'] = 'local';

    File::ensureDirectoryExists(public_path('vendor/aura'));
    File::put(public_path('vendor/aura/manifest.json'), "{}\n");

    Artisan::swap(Mockery::mock(Kernel::class)
        ->shouldReceive('call')->with('aura:publish')->once()->andReturn(1)
        ->getMock());

    $this->from(route('aura.dashboard'))
        ->post(route('aura.assets.publish'), ['_token' => csrf_token()])
        ->assertRedirect(route('aura.dashboard'))
        ->assertSessionHas('aura.assets-publish-error');

    $this->get(route('aura.dashboard'))
        ->assertSuccessful()
        ->assertSee('Publishing failed. Run the command in your terminal for details.')
        ->assertSee('Publish assets');
});
