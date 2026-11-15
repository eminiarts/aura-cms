<?php

use Aura\Base\Facades\Aura;
use Illuminate\Support\Facades\Blade;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// The test app has no app resources, so the default dashboard is the onboarding call-out.
test('without slots the dashboard stays as it is', function () {
    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertSee('Create your first resource')
        ->assertDontSee('data-dashboard-slot', false);
});

test('the stats slot replaces the count cards and the main and side slots lead their columns', function () {
    Aura::registerInjectView('dashboard_stats', fn () => Blade::render('<x-aura::dashboard.stat label="Open pipeline" value="CHF 42" caption="3 open deals" tone="danger" />'));
    Aura::registerInjectView('dashboard_main', fn () => '<section>Pipeline by stage</section>');
    Aura::registerInjectView('dashboard_side', fn () => '<section>My tasks</section>');

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertSee('data-dashboard-slot="stats"', false)
        ->assertSeeInOrder(['Open pipeline', 'CHF 42', '3 open deals', 'Pipeline by stage', 'Recently edited', 'My tasks', 'Quick actions'])
        ->assertSee('text-red-700', false)
        ->assertDontSee('Create your first resource');
});

test('a slot that renders nothing leaves the dashboard as it is', function () {
    Aura::registerInjectView('dashboard_stats', fn () => '  ');

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertDontSee('data-dashboard-slot', false)
        ->assertSee('Create your first resource');
});
