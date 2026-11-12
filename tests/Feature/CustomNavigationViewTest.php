<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\View;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
    View::addLocation(__DIR__.'/../Fixtures/views');
});

test('aura.views.navigation replaces the sidebar', function () {
    config(['aura.views.navigation' => 'custom-sidebar']);

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertSee('data-test="custom-sidebar"', false)
        ->assertSee('Custom sidebar for '.$this->user->name)
        ->assertDontSee('aura-sidebar-type-', false);
});

test('without aura.views.navigation the sidebar is aura\'s', function () {
    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertSee('aura-sidebar-type-', false)
        ->assertDontSee('data-test="custom-sidebar"', false);
});

test('a flush layout renders its slot without padding and version line', function () {
    $render = fn (bool $flush) => Blade::render(
        '<x-aura::layout.app :flush="$flush"><div data-test="page">Page</div></x-aura::layout.app>',
        ['flush' => $flush],
    );

    $flush = $render(true);
    expect($flush)->toContain('data-test="page"')
        ->not->toContain('<div class="p-5 md:p-8">');

    expect($render(false))->toContain('<div class="p-5 md:p-8">');
});
