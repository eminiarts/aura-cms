<?php

use Illuminate\Support\Facades\Artisan;

test('resource in app can be edited', function () {
    // Todo: add test
});


test('vendor resource can not be edited', function () {
    // Todo: add test
});



test('edit posttype should be allowed', function () {
    $config = config('aura.posttype_editor');

    $this->assertTrue($config);
});

test('edit posttype can be turned off in config', function () {
    // Set aura.posttype_editor to true
    config(['aura.posttype_editor' => false]);

    $config = config('aura.posttype_editor');

    $this->assertFalse($config);

    // visit edit posttype page
    $this->get(route('aura.posttype.edit', 'post'))
        ->assertStatus(403);
});


test('edit posttype should not be available in production', function () {
    // Set env to production
    config(['app.env' => 'production']);

    // Set aura.posttype_editor to true
    config(['aura.posttype_editor' => false]);

    $config = config('aura.posttype_editor');

    $this->assertFalse($config);
});
