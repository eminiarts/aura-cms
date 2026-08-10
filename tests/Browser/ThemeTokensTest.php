<?php

beforeEach(function () {
    $this->actingAs(createSuperAdmin());
});

test('admin shell uses default semantic tokens without remote font requests', function () {
    $page = visit('/admin')->inLightMode();

    $page->assertSee('Dashboard')
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs()
        ->assertNoAccessibilityIssues();

    $state = json_decode((string) $page->script(<<<'JS'
        JSON.stringify((() => {
            const root = document.documentElement;
            const rootStyles = getComputedStyle(root);
            const bodyStyles = getComputedStyle(document.body);
            const externalFontRequests = performance.getEntriesByType('resource')
                .map((entry) => entry.name)
                .filter((url) => {
                    const parsed = new URL(url, window.location.href);
                    const isFont = /\.(?:woff2?|ttf|otf)(?:\?|$)/i.test(parsed.pathname)
                        || /fonts\.(?:googleapis|gstatic)\.com$/i.test(parsed.hostname);

                    return isFont && parsed.origin !== window.location.origin;
                });

            return {
                background: rootStyles.getPropertyValue('--aura-color-background').trim(),
                panel: rootStyles.getPropertyValue('--aura-color-panel').trim(),
                text: rootStyles.getPropertyValue('--aura-color-text').trim(),
                font: bodyStyles.fontFamily,
                bodyBackground: bodyStyles.backgroundColor,
                bodyColor: bodyStyles.color,
                externalFontRequests,
            };
        })())
        JS), true);

    expect($state)->toMatchArray([
        'background' => '255 255 255',
        'panel' => '250 250 250',
        'text' => '24 24 27',
        'bodyBackground' => 'rgb(255, 255, 255)',
        'bodyColor' => 'rgb(24, 24, 27)',
        'externalFontRequests' => [],
    ])->and($state['font'])->toContain('system-ui')
        ->not->toContain('Inter');
});

test('admin shell applies dark tokens at a mobile viewport', function () {
    $page = visit('/admin')->inDarkMode()->resize(390, 844);

    $page->assertSee('Dashboard')
        ->assertNoJavaScriptErrors()
        ->assertNoConsoleLogs()
        ->assertNoAccessibilityIssues();

    $state = json_decode((string) $page->script(<<<'JS'
        JSON.stringify((() => {
            const root = document.documentElement;
            const rootStyles = getComputedStyle(root);
            const bodyStyles = getComputedStyle(document.body);

            return {
                dark: root.classList.contains('dark'),
                background: rootStyles.getPropertyValue('--aura-color-background').trim(),
                panel: rootStyles.getPropertyValue('--aura-color-panel').trim(),
                text: rootStyles.getPropertyValue('--aura-color-text').trim(),
                bodyBackground: bodyStyles.backgroundColor,
                bodyColor: bodyStyles.color,
                viewportWidth: window.innerWidth,
                documentWidth: document.documentElement.scrollWidth,
            };
        })())
        JS), true);

    expect($state)->toMatchArray([
        'dark' => true,
        'background' => '9 9 11',
        'panel' => '24 24 27',
        'text' => '244 244 245',
        'bodyBackground' => 'rgb(9, 9, 11)',
        'bodyColor' => 'rgb(244, 244, 245)',
        'viewportWidth' => 390,
    ])->and($state['documentWidth'])->toBeLessThanOrEqual(390);
});
