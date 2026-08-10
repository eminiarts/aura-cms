<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Preferences\PreferenceContext;
use Aura\Base\Preferences\PreferenceManager;
use Aura\Base\Preferences\PreferenceScope;
use Aura\Base\Tests\Fixtures\Widgets\DashboardProbeWidget;
use Aura\Base\Tests\Resources\Post;
use Aura\Base\Widgets\DashboardWidgetRegistry;
use Illuminate\Support\Facades\Gate;

abstract class AbstractDashboardWidgetResource extends \Aura\Base\Resource {}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('an empty registry preserves the built in dashboard', function () {
    expect(app(DashboardWidgetRegistry::class)->forUser($this->user))->toBe([]);

    $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertSee('Quick Actions')
        ->assertDontSee('data-dashboard-probe', false);
});

test('registered widgets are normalized authorized and ordered while invalid definitions are ignored', function () {
    Gate::define('view-dashboard-denied', fn (): bool => false);

    Aura::registerWidgets([
        ['id' => 'later', 'component' => DashboardProbeWidget::class, 'order' => 20],
        ['id' => 'first', 'component' => DashboardProbeWidget::class, 'order' => 10, 'columns' => 4],
        ['id' => 'denied', 'component' => DashboardProbeWidget::class, 'authorization' => false],
        [
            'id' => 'gate-denied',
            'component' => DashboardProbeWidget::class,
            'authorization' => ['ability' => 'view-dashboard-denied', 'subject' => 'dashboard'],
        ],
        ['id' => 'invisible', 'component' => DashboardProbeWidget::class, 'visible' => false],
        ['id' => 'missing-component', 'component' => 'Missing\\DashboardWidget'],
        ['id' => 'abstract-resource', 'component' => DashboardProbeWidget::class, 'resource' => AbstractDashboardWidgetResource::class],
        ['id' => 'unsafe', 'component' => DashboardProbeWidget::class, 'arguments' => ['callback' => fn () => true]],
        ['id' => 'first', 'component' => DashboardProbeWidget::class, 'order' => -100],
    ]);

    $widgets = app(DashboardWidgetRegistry::class)->forUser($this->user);

    expect(array_column($widgets, 'id'))->toBe(['first', 'later'])
        ->and($widgets[0]['class'])->toBe('col-span-12 sm:col-span-6 lg:col-span-4');
});

test('resource widgets are authorized and receive precomputed component arguments', function () {
    Aura::registerWidgets([[
        'id' => 'post-total',
        'component' => DashboardProbeWidget::class,
        'resource' => Post::class,
        'arguments' => ['label' => 'Authorized resource widget'],
    ]]);

    $widget = app(DashboardWidgetRegistry::class)->forUser($this->user)[0];

    expect($widget['arguments']['model'])->toBeInstanceOf(Post::class)
        ->and($widget['arguments']['label'])->toBe('Authorized resource widget');
});

test('user and team preferences hide and reorder registered widgets', function () {
    Aura::registerWidgets([
        ['id' => 'alpha', 'component' => DashboardProbeWidget::class],
        ['id' => 'beta', 'component' => DashboardProbeWidget::class],
        ['id' => 'gamma', 'component' => DashboardProbeWidget::class],
    ]);

    $team = config('aura.teams') ? $this->user->currentTeam : null;
    $context = new PreferenceContext('aura.dashboard', $this->user, $team);
    $preferences = app(PreferenceManager::class);

    if ($team !== null) {
        $preferences->set('dashboard.widgets.order', ['gamma', 'alpha'], PreferenceScope::Team, $context, $this->user);

        expect(array_column(app(DashboardWidgetRegistry::class)->forUser($this->user), 'id'))
            ->toBe(['gamma', 'alpha', 'beta']);
    }

    $preferences->set('dashboard.widgets.order', ['beta', 'alpha'], PreferenceScope::User, $context, $this->user);
    $preferences->set('dashboard.widgets.hidden', ['gamma'], PreferenceScope::User, $context, $this->user);

    expect(array_column(app(DashboardWidgetRegistry::class)->forUser($this->user), 'id'))
        ->toBe(['beta', 'alpha']);
});

test('the dashboard renders registered component slots after its built in cards', function () {
    Aura::registerWidgets([
        [
            'id' => 'rendered-widget',
            'component' => DashboardProbeWidget::class,
            'arguments' => ['label' => 'Rendered dashboard widget'],
        ],
        ['id' => 'invalid-widget', 'component' => stdClass::class],
    ]);

    $response = $this->get(route('aura.dashboard'))
        ->assertOk()
        ->assertSee('Quick Actions')
        ->assertSee('Rendered dashboard widget')
        ->assertSee('data-dashboard-probe', false)
        ->assertDontSee('invalid-widget');

    expect(strpos($response->getContent(), 'Quick Actions'))
        ->toBeLessThan(strpos($response->getContent(), 'Rendered dashboard widget'));
});
