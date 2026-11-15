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
        ->assertSeeInOrder(['Open pipeline', 'CHF 42', '3 open deals', 'Pipeline by stage', 'Recently edited', 'My tasks', 'Setup', 'Documentation'])
        ->assertSee('text-red-700', false)
        ->assertDontSee('Quick actions')
        ->assertDontSee('Create your first resource');
});

// With app sections the package's own links are one Setup block, only for people who see the team links.
test('the setup block is left out for members without team links', function () {
    $this->actingAs(soleMemberOf($this->user->currentTeam));
    Aura::registerInjectView('dashboard_side', fn () => '<section>My tasks</section>');

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertSee('My tasks')
        ->assertDontSee('data-dashboard-quick-actions', false)
        ->assertDontSee('Documentation');
});

// An empty team's onboarding (the stats slot without other app sections) stands alone: no quick actions repeat it.
test('an empty team with an onboarding stats slot shows only that block', function () {
    Aura::registerInjectView('dashboard_stats', fn () => '<div>Set up your pipeline</div>');

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertSee('Set up your pipeline')
        ->assertDontSee('Quick actions')
        ->assertDontSee('Recently edited')
        ->assertDontSee('Create your first resource');
});

test('a slot that renders nothing leaves the dashboard as it is', function () {
    Aura::registerInjectView('dashboard_stats', fn () => '  ');

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertDontSee('data-dashboard-slot', false)
        ->assertSee('Create your first resource');
});
