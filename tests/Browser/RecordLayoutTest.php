<?php

use Aura\Base\Tests\Resources\RecordLayoutPage;

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
});

test('record layouts render all regions without mobile overflow or browser errors', function () {
    $record = RecordLayoutPage::create([
        'title' => 'Browser record layout',
        'content' => 'Responsive panel content',
        'type' => RecordLayoutPage::getType(),
        'status' => 'publish',
    ]);

    $page = visit(route('aura.record-layout-page.view', $record))->resize(390, 844);

    $page->assertSee('RecordLayoutPage')
        ->assertPresent('[data-record-layout="page"]')
        ->assertPresent('[data-record-layout-region="header-actions"]')
        ->assertPresent('[data-record-layout-region="left-summary"]')
        ->assertPresent('[data-record-layout-region="main-content"]')
        ->assertPresent('[data-record-layout-region="right-sidebar"]')
        ->assertPresent('[data-record-layout-region="activity-timeline"]')
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs()
        ->assertNoAccessibilityIssues();

    $widths = json_decode((string) $page->script(<<<'JS'
        JSON.stringify({
            viewport: window.innerWidth,
            document: document.documentElement.scrollWidth,
        })
        JS), true);

    expect($widths)->toBe([
        'viewport' => 390,
        'document' => 390,
    ]);
});
