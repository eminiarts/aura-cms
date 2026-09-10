<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Templates\PanelWithSidebar;
use Aura\Base\Templates\PanelWithTabs;
use Aura\Base\Templates\TabsWithPanels;

it('resolves multiword template names and slugs', function (string $slug, string $class) {
    expect(Aura::findTemplateBySlug($slug))->toBeInstanceOf($class);
})->with([
    ['panel-with-sidebar', PanelWithSidebar::class],
    ['panel_with_tabs', PanelWithTabs::class],
    ['TabsWithPanels', TabsWithPanels::class],
]);
