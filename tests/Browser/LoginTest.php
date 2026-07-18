<?php

use Illuminate\Support\Facades\Auth;

require_once __DIR__.'/Support/helpers.php';

test('a registered user logs in through the form and out through the admin shell', function () {
    $user = browserSuperAdmin('secret-password');

    // Prove the login FORM: start from a clean guest session (createSuperAdmin
    // logged the user in in-process; drop that so the browser truly signs in).
    Auth::logout();

    $page = visit('/login');

    $page->assertSee('Welcome back');

    $page->fill('#email', $user->email)
        ->fill('#password', 'secret-password')
        ->press('Log in')
        ->wait(2);

    // Signed in → admin panel.
    $page->assertPathIs('/admin');

    // Log out via the user menu in the sidebar (opens a tippy popover holding
    // the logout form).
    $page->click('.aura-sidebar-team-switcher')->wait(1);

    $page->click('Logout')->wait(2);

    // Logout redirects to '/'; the admin panel now bounces guests to /login.
    $page->navigate('/admin')->wait(1);

    $page->assertPathIs('/login');
});

test('logging in with the wrong password shows an error', function () {
    $user = browserSuperAdmin('correct-password');

    Auth::logout();

    $page = visit('/login');

    $page->fill('#email', $user->email)
        ->fill('#password', 'wrong-password')
        ->press('Log in')
        ->wait(2);

    $page->assertPathIs('/login')
        ->assertSee('These credentials do not match our records');
});
