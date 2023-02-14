<?php

use Eminiarts\Aura\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(fn () => $this->actingAs($this->user = User::factory()->create()));

test('resource in app can be edited', function () {
    // create a app resource

    $this->assertFalse($appResource->isAppResource());
    $this->assertTrue($appResource->isVendorResource());

    createSuperAdmin();

    // visit edit posttype page
    $response = $this->get(route('aura.posttype.edit', 'user'));

    $response->assertForbidden();

    expect($response->exception->getMessage())->toBe('Only App resources can be edited.');
});

test('vendor resource can not be edited', function () {
    $userResource = new User();

    $this->assertFalse($userResource->isAppResource());
    $this->assertTrue($userResource->isVendorResource());

    createSuperAdmin();

    // visit edit posttype page
    $response = $this->get(route('aura.posttype.edit', 'user'));

    $response->assertForbidden();

    expect($response->exception->getMessage())->toBe('Only App resources can be edited.');
});

test('edit posttype should be allowed', function () {
    $config = config('aura.posttype_editor');

    $this->assertTrue($config);
});

test('edit posttype can be turned off in config', function () {
    createSuperAdmin();

    config(['aura.posttype_editor' => false]);

    $config = config('aura.posttype_editor');

    $this->assertFalse($config);

    // visit edit posttype page
    $response = $this->get(route('aura.posttype.edit', 'user'));

    $response->assertForbidden();

    expect($response->exception->getMessage())->toBe('Posttype Editor is turned off.');
});

test('edit posttype should not be available in production', function () {
    // Set env to production
    config(['app.env' => 'production']);

    // Set aura.posttype_editor to true
    config(['aura.posttype_editor' => false]);

    $config = config('aura.posttype_editor');

    $this->assertFalse($config);
});
