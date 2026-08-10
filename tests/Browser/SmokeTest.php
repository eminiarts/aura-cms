<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Cache;

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

    $page->script("window.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', code: 'KeyK', ctrlKey: true, bubbles: true }))");

    $page->wait(1)
        ->assertVisible('#docsearch-input')
        ->fill('#docsearch-input', 'global-search-browser@example.com')
        ->wait(2)
        ->assertSee('Global Search Browser Result')
        ->keys('#docsearch-input', 'Enter')
        ->wait(1)
        ->assertPathIs('/admin/user/'.$result->getKey());
});

test('global search handles enter with no results without console errors', function () {
    $this->actingAs(createSuperAdmin());

    $page = visit('/admin');

    $page->script("window.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', code: 'KeyK', ctrlKey: true, bubbles: true }))");

    $page->wait(1)
        ->fill('#docsearch-input', 'browser-no-result-needle')
        ->wait(2)
        ->assertSee('No results')
        ->keys('#docsearch-input', 'Enter')
        ->wait(1)
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});

test('global search hides a forbidden resource in a real browser', function () {
    $admin = createAdmin();
    $this->actingAs($admin);

    $role = Role::resolveForTeam(
        'editor',
        $admin->current_team_id,
        $admin->getConnection(),
    );

    expect($role)->toBeInstanceOf(Role::class);

    $role->update([
        'permissions' => [
            'view-user' => false,
            'viewAny-user' => false,
            'scope-user' => false,
        ],
    ]);
    Cache::flush();
    $this->actingAs($admin->refresh());

    User::factory()->create([
        'name' => 'Forbidden Browser Person',
        'email' => 'forbidden-global-search-browser@example.com',
    ]);

    $page = visit('/admin');

    $page->script("window.dispatchEvent(new KeyboardEvent('keydown', { key: 'k', code: 'KeyK', ctrlKey: true, bubbles: true }))");

    $page->wait(1)
        ->fill('#docsearch-input', 'forbidden-global-search-browser@example.com')
        ->wait(2)
        ->assertSee('No results')
        ->assertDontSee('Forbidden Browser Person')
        ->assertNoJavaScriptErrors();
});
