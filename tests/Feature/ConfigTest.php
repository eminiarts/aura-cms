<?php

//uses()->group('current');

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('config features test', function () {
    expect(config('aura.features'))->toMatchArray([
        'global_search' => true,
        'notifications' => true,
        'bookmarks' => true,
        'plugins' => true,
        'flows' => false,
        'forms' => true,
        'settings' => true,
        'profile' => true,
        'create_resource' => true,
        'resource_view' => true,
        'resource_edit' => true,
    ]);
});

test('resource editor', function () {
    expect(config('aura.resource_editor'))->toMatchArray([
        'enabled' => config('app.env') == 'production' ? false : true,
        'custom_table_migrations' => false,
    ]);
});
