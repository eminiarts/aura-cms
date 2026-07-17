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
