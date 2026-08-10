<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Tests\Fixtures\Widgets\DashboardProbeWidget;

test('a registered widget renders in the real dashboard without browser errors', function () {
    $this->actingAs(createSuperAdmin());

    Aura::registerWidgets([[
        'id' => 'browser-dashboard-widget',
        'component' => DashboardProbeWidget::class,
        'arguments' => ['label' => 'Browser dashboard widget'],
        'columns' => 6,
    ]]);
    Aura::captureBaselineState();

    visit('/admin')
        ->assertSee('Quick Actions')
        ->assertSee('Browser dashboard widget')
        ->assertVisible('[data-dashboard-probe]')
        ->assertNoConsoleLogs()
        ->assertNoJavaScriptErrors();
});
