<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

class ResourceEditModel extends Resource
{
    public static $singularName = 'Custom Resource';

    public static ?string $slug = 'resource';

    public static string $type = 'resource';

    public function isAppResource(): bool
    {
        return true;
    }
}

test('resource in app can be edited', function () {
    // create a app resource
    $appResource = new ResourceEditModel();

    $this->assertTrue($appResource->isAppResource());
    $this->assertFalse($appResource->isVendorResource());

    createSuperAdmin();

    Aura::fake();
    Aura::setModel($appResource);

    // visit edit resource page
    $response = $this->get(route('aura.resource.editor', 'resource'));

    $response->assertOk();
});

test('vendor resource can not be edited', function () {
    $userResource = new User();

    $this->assertFalse($userResource->isAppResource());
    $this->assertTrue($userResource->isVendorResource());

    createSuperAdmin();

    Aura::fake();
    Aura::setModel($userResource);

    // visit edit resource page
    $response = $this->get(route('aura.resource.editor', 'user'));

    $response->assertForbidden();

    expect($response->exception->getMessage())->toBe('Only App resources can be edited.');
});

test('edit resource should be allowed', function () {
    $config = config('aura.features.resource_editor');

    $this->assertTrue($config);
});

test('edit resource can be turned off in config', function () {
    createSuperAdmin();

    config(['aura.features.resource_editor' => false]);

    $config = config('aura.features.resource_editor');

    $this->assertFalse($config);

    // visit edit resource page
    $response = $this->get(route('aura.resource.editor', 'user'));

    $response->assertStatus(404);
});

test('edit resource should not be available in production', function () {
    // Set env to production
    config(['app.env' => 'production']);

    // Set aura.resource_editor to true
    config(['aura.features.resource_editor' => false]);

    $config = config('aura.features.resource_editor');

    $this->assertFalse($config);
});
