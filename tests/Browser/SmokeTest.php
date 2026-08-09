<?php

test('login page renders in a real browser', function () {
    $page = visit('/login');

    $page->assertSee('Login');
});

test('an authenticated super admin can open the media library', function () {
    $this->actingAs(createSuperAdmin());

    $page = visit('/admin/attachment');

    $page->assertSee('Media Library');
});

test('global search returns an authorized resource destination', function () {
    $admin = createSuperAdmin();
    $this->actingAs($admin);

    $result = soleMemberOf($admin->currentTeam)->forceFill([
        'name' => 'Global Search Browser Result',
        'email' => 'global-search-browser@example.com',
    ]);
    $result->save();

    $page = visit('/admin');

    $page->script("window.dispatchEvent(new CustomEvent('search'))");

    $page->wait(1)
        ->fill('#docsearch-input', 'global-search-browser@example.com')
        ->wait(2)
        ->assertSee('Global Search Browser Result')
        ->click('a:has-text("Global Search Browser Result")')
        ->wait(1)
        ->assertPathIs('/admin/user/'.$result->getKey());
});
