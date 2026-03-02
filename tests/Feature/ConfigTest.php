<?php

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('features config has correct default values', function () {
    expect(config('aura.features'))->toMatchArray([
        'global_search' => true,
        'bookmarks' => true,
        'last_visited_pages' => true,
        'notifications' => true,
        'plugins' => true,
        'settings' => true,
        'profile' => true,
        'create_resource' => true,
        'resource_view' => true,
        'resource_edit' => true,
        'resource_editor' => false,
        'custom_tables_for_resources' => false,
    ]);
});

test('auth config has correct default values', function () {
    expect(config('aura.auth'))->toMatchArray([
        'registration' => env('AURA_REGISTRATION', true),
        'redirect' => '/admin',
        '2fa' => true,
        'user_invitations' => true,
        'create_teams' => true,
    ]);
});
