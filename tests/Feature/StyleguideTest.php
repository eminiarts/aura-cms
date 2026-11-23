<?php

use Aura\Base\Livewire\Styleguide;
use Aura\Base\Livewire\StyleguideWidget;
use Aura\Base\ThemeTokens;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

const STYLEGUIDE_SECTIONS = [
    'tokens', 'colors', 'typography', 'icons', 'buttons', 'forms', 'sizing', 'badges', 'cards', 'charts',
    'table', 'tabs', 'navigation', 'menus', 'popovers', 'overlays', 'notifications', 'feedback', 'auth',
];

test('styleguide page renders', function () {
    $this->get(route('aura.styleguide'))
        ->assertOk()
        ->assertSee('Style Guide')
        ->assertSee('Design tokens')
        ->assertSee('Typography')
        ->assertSee('Buttons')
        ->assertSee('Control sizing')
        ->assertSee('Charts')
        ->assertSee('Tabs')
        ->assertSee('Popovers & tooltips')
        ->assertSee('Notifications');
});

test('styleguide requires authentication', function () {
    auth()->logout();

    $this->get(route('aura.styleguide'))
        ->assertRedirect();
});

test('every section has a stable anchor, in navigation order', function () {
    expect(array_keys((new Styleguide)->sections()))->toBe(STYLEGUIDE_SECTIONS);

    $response = $this->get(route('aura.styleguide'))->assertOk();

    $anchors = array_map(fn (string $id): string => 'id="'.$id.'" data-styleguide-section="'.$id.'"', STYLEGUIDE_SECTIONS);
    $links = array_map(fn (string $id): string => 'href="#'.$id.'"', STYLEGUIDE_SECTIONS);

    $response->assertSeeInOrder($anchors, false)->assertSeeInOrder($links, false);
});

test('mode=dark renders the style guide content in dark mode', function () {
    $html = $this->get(route('aura.styleguide', ['mode' => 'dark']))->assertOk()->getContent();

    expect($html)->toContain('data-styleguide-mode="dark"')
        ->and($html)->toMatch('/<div[^>]*data-styleguide-mode="dark"[^>]*class="[^"]*\bdark\b/');
});

test('mode=light asks the page to turn the admin dark class off', function () {
    $this->get(route('aura.styleguide', ['mode' => 'light']))
        ->assertOk()
        ->assertSee('auraStyleguide({ forceLight: true })', false)
        ->assertSee('data-styleguide-mode="light"', false);
});

test('unknown url parameters fall back to the defaults', function () {
    livewire(Styleguide::class, ['mode' => 'sepia', 'section' => 'nope', 'density' => 'huge', 'radius' => 'x', 'sidebarSize' => 'x'])
        ->assertSet('mode', '')
        ->assertSet('section', '')
        ->assertSet('density', '')
        ->assertSet('radius', '')
        ->assertSet('sidebarSize', '');
});

test('section renders a single section without the page header', function () {
    $this->get(route('aura.styleguide', ['section' => 'buttons']))
        ->assertOk()
        ->assertSee('data-styleguide-section="buttons"', false)
        ->assertDontSee('data-styleguide-section="forms"', false)
        ->assertDontSee('data-styleguide-controls', false);
});

test('section keeps its global number when rendered alone', function () {
    $this->get(route('aura.styleguide', ['section' => 'tabs']))
        ->assertOk()
        ->assertSeeInOrder(['12', 'Tabs'], false);
});

test('design token table documents every preset value from ThemeTokens', function () {
    $groups = (new Styleguide)->designTokens();

    expect(array_keys($groups))->toBe(array_keys(Styleguide::DESIGN_PRESETS));

    foreach (Styleguide::DESIGN_PRESETS as $setting => $presets) {
        expect(array_keys($groups[$setting]['presets']))->toBe($presets);

        foreach ($presets as $preset) {
            $expected = ThemeTokens::design([$setting => $preset]);

            foreach ($groups[$setting]['presets'][$preset] as $variable => $value) {
                expect($value)->toBe($expected[$variable]);
            }
        }
    }

    $documented = collect($groups)->flatMap(fn (array $group): array => array_keys($group['variables']))->all();

    // Every design variable belongs to exactly one setting and has its expert override listed.
    expect($documented)->toEqualCanonicalizing(array_keys(ThemeTokens::design([])))
        ->and(collect($groups)->flatMap(fn (array $group): array => array_column($group['variables'], 'expert'))->all())
        ->toEqualCanonicalizing(array_keys(ThemeTokens::designOverrides()));
});

test('preset parameters preview their variables on the style guide only', function () {
    $this->get(route('aura.styleguide', ['density' => 'compact', 'radius' => 'square', 'sidebar' => 'dense']))
        ->assertOk()
        ->assertSee('--aura-panel-padding: 1rem', false)
        ->assertSee('--aura-panel-radius: 0rem', false)
        ->assertSee('--aura-control-radius: 0rem', false)
        ->assertSee('--aura-sidebar-item-height: 1.75rem', false);
});

test('preview links keep the other parameters', function () {
    livewire(Styleguide::class, ['mode' => 'dark', 'density' => 'compact'])
        ->assertSee('?mode=dark&amp;density=compact&amp;radius=square', false)
        ->assertSee('?density=compact', false);
});

test('form error specimen uses the real field wrapper message', function () {
    $this->get(route('aura.styleguide', ['section' => 'forms']))
        ->assertOk()
        ->assertSee('The slug may only contain letters, numbers and dashes.');
});

test('table pagination uses the real pagination view and changes page', function () {
    livewire(Styleguide::class, ['section' => 'table'])
        ->assertSee('aria-current="page">1</span>', false)
        ->call('gotoPage', 3)
        ->assertSee('aria-current="page">3</span>', false);
});

test('charts render the real widget views with demo values', function () {
    livewire(StyleguideWidget::class, [
        'widgetView' => 'value',
        'widget' => ['name' => 'Posts', 'slug' => 'styleguide-value'],
        'demoValues' => ['current' => "2'741", 'previous' => "2'533", 'change' => '8.21'],
    ])
        ->assertSee('Posts')
        ->assertSee("2'741")
        ->assertSee('8.21%');

    livewire(StyleguideWidget::class, [
        'widgetView' => 'donut',
        'widget' => ['name' => 'Posts by status', 'slug' => 'styleguide-donut'],
        'demoValues' => ['current' => ['Draft' => 3, 'Published' => 5], 'previous' => []],
    ])->assertSee('Posts by status')->assertSee('window.ApexCharts', false);

    $this->get(route('aura.styleguide', ['section' => 'charts']))
        ->assertOk()
        ->assertSee('Posts per weekday')
        ->assertSee('Users by role');
});

test('style guide widget only renders known widget views', function () {
    livewire(StyleguideWidget::class, [
        'widgetView' => '../../secret',
        'widget' => ['name' => 'Fallback', 'slug' => 'styleguide-fallback'],
        'demoValues' => ['current' => '1', 'previous' => '0', 'change' => '0'],
    ])->assertViewIs('aura::components.widgets.value');
});
