<?php

use Illuminate\Support\Facades\Blade;
use Symfony\Component\Finder\Finder;

// A host app may load its own Tailwind build after Aura's CSS. Equal-specificity host utilities
// (.rounded-lg, .flex-col, .border-gray-200/80) then win over Aura's token rules and its md:/dark:
// variants. These tests keep the markup free of utilities that compete with a token rule and
// check that the shipped CSS has the stronger token selectors.

function auraRadiusUtility(): string
{
    return '/(?<![\w:-])rounded(?:-(?:none|sm|md|lg|xl|2xl|3xl|full))?(?![\w-])/';
}

function auraHeightUtility(): string
{
    return '/(?<![\w:\[-])(?:h-(?:\d+(?:\.5)?|\[[^\]]+\])|min-h-\[[^\]]+\]|py-\d+(?:\.5)?|pt-\d+(?:\.5)?|pb-\d+(?:\.5)?)(?![\w-])/';
}

function auraPaddingUtility(): string
{
    return '/(?<![\w:\[-])p[xytrbl]?-(?:\d+(?:\.5)?|px|\[[^\]]+\])(?![\w-])/';
}

/**
 * Every class="…" attribute in the package views, as [relative path, class string].
 *
 * @return array<int, array{0: string, 1: string}>
 */
function auraViewClasses(): array
{
    $classes = [];

    foreach (Finder::create()->files()->in(dirname(__DIR__, 2).'/resources/views')->name('*.blade.php') as $file) {
        preg_match_all('/\bclass="([^"]*)"/', $file->getContents(), $matches);

        foreach ($matches[1] as $class) {
            $classes[] = [$file->getRelativePathname(), $class];
        }
    }

    return $classes;
}

function auraBuiltCss(): string
{
    $dist = dirname(__DIR__, 2).'/resources/dist';
    $manifest = json_decode(file_get_contents($dist.'/manifest.json'), true);

    return file_get_contents($dist.'/'.$manifest['resources/css/app.css']['file']);
}

test('buttons leave the radius to the aura-button token rule in every size', function (string $size) {
    $html = Blade::render('<x-aura::button size="'.$size.'">Save</x-aura::button>');

    preg_match('/class="([^"]*)"/', $html, $class);

    expect($class[1])->toContain('aura-button')
        ->and($class[1])->not->toMatch(auraRadiusUtility());
})->with(['icon-xs', 'xs', 'sm', 'base', 'lg', 'xl']);

test('buttons take their height from the control size class, not padding', function (string $size, string $control) {
    $html = Blade::render('<x-aura::button size="'.$size.'">Save</x-aura::button>');

    preg_match('/class="([^"]*)"/', $html, $class);

    expect($class[1])->toContain('aura-control-'.$control)
        ->and($class[1])->not->toMatch(auraHeightUtility());
})->with([
    ['icon-xs', 'xs'],
    ['xs', 'xs'],
    ['sm', 'sm'],
    ['base', 'base'],
    ['lg', 'lg'],
    ['xl', 'xl'],
]);

test('icon-only buttons are square and buttons with text are not', function () {
    $iconOnly = Blade::render('<x-aura::button.border><x-aura::icon icon="dots" size="xs" /></x-aura::button.border>');
    $srOnly = Blade::render('<x-aura::button.border><span class="sr-only">More</span><x-aura::icon icon="dots" size="xs" /></x-aura::button.border>');
    $withText = Blade::render('<x-aura::button.border><x-aura::icon icon="plus" size="xs" /> Create</x-aura::button.border>');

    expect($iconOnly)->toContain('aura-control-square')
        ->and($srOnly)->toContain('aura-control-square')
        ->and($withText)->not->toContain('aura-control-square')
        ->and(Blade::render('<x-aura::button size="icon-xs"><x-aura::icon icon="dots" size="xs" /></x-aura::button>'))->toContain('aura-control-square');
});

test('inputs and selects share the button sizes and the field radius', function (string $size) {
    $input = Blade::render('<x-aura::input.text size="'.$size.'" prefix="https://" />');
    $select = Blade::render('<x-aura::input.select name="s" :options="[1 => \'One\']" size="'.$size.'" />');

    preg_match('/<input[^>]*class="([^"]*)"/', $input, $inputClass);
    preg_match('/<select[^>]*class="([^"]*)"/', $select, $selectClass);

    foreach ([$inputClass[1], $selectClass[1]] as $class) {
        expect($class)->toContain('aura-field')
            ->toContain('aura-control-'.$size)
            ->not->toMatch(auraRadiusUtility())
            ->not->toMatch(auraHeightUtility());
    }

    expect($input)->toContain('aura-field-group');
})->with(['xs', 'sm', 'base', 'lg']);

test('token-driven elements carry no competing radius utility in the package views', function () {
    $tokenClass = '/(?<![\w-])aura-(?:button|field|card|card-small|sidebar-item|sidebar-dropdown-button|sidebar-dropdown-button-expanded|sidebar-dropdown-collapsed|sidebar-search|sidebar-toggle|sidebar-icon|sidebar-child-item|sidebar-team-switcher|radius-panel|radius-control|modal-panel|dropdown-item|segmented|segmented-item|group-section|login-card)(?![\w-])/';
    $offenders = [];

    foreach (Finder::create()->files()->in(dirname(__DIR__, 2).'/resources/views')->name('*.blade.php') as $file) {
        preg_match_all('/\bclass="([^"]*)"/', $file->getContents(), $matches);

        foreach ($matches[1] as $class) {
            if (preg_match($tokenClass, $class) && preg_match(auraRadiusUtility(), $class)) {
                $offenders[] = $file->getRelativePathname().': '.$class;
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('controls with a size class carry no competing height or vertical padding utility', function () {
    $offenders = [];

    foreach (Finder::create()->files()->in(dirname(__DIR__, 2).'/resources/views')->name('*.blade.php') as $file) {
        preg_match_all('/\bclass="([^"]*)"/', $file->getContents(), $matches);

        foreach ($matches[1] as $class) {
            if (preg_match('/(?<![\w-])aura-control-(?:xs|sm|base|lg|xl)(?![\w-])/', $class) && preg_match(auraHeightUtility(), $class)) {
                $offenders[] = $file->getRelativePathname().': '.$class;
            }
        }
    }

    expect($offenders)->toBe([]);
});

test('the built CSS sets token properties with a doubled class selector', function () {
    $css = auraBuiltCss();

    expect($css)
        ->toContain('.aura-card.aura-card{padding:var(--aura-panel-padding,1.25rem);border-radius:var(--aura-panel-radius,.75rem)}')
        ->toContain('.aura-card-small.aura-card-small{padding:var(--aura-panel-small-padding,.5rem);border-radius:var(--aura-control-radius,.5rem)}')
        ->toContain('.aura-button.aura-button,')
        ->toContain('.aura-field.aura-field,')
        ->toContain('.aura-control-xs.aura-control-xs{height:var(--aura-control-height-xs,1.75rem);padding-top:0;padding-bottom:0}')
        ->toContain('.aura-control-sm.aura-control-sm{height:var(--aura-control-height-sm,2rem);padding-top:0;padding-bottom:0}')
        ->toContain('.aura-control-base.aura-control-base{height:var(--aura-control-height,2.25rem);padding-top:0;padding-bottom:0}')
        ->toContain('.aura-control-lg.aura-control-lg{height:var(--aura-control-height-lg,2.75rem);padding-top:0;padding-bottom:0}')
        ->toContain('.aura-control-min-base.aura-control-min-base{min-height:var(--aura-control-height,2.25rem)}')
        ->toContain('.aura-sidebar-toggle.aura-sidebar-toggle,')
        ->toContain('.aura-radius-control.aura-radius-control{border-radius:var(--aura-control-radius,.5rem)}')
        ->toContain('.aura-radius-panel.aura-radius-panel{border-radius:var(--aura-panel-radius,.75rem)}')
        ->toContain('.aura-sidebar-groups.aura-sidebar-groups{gap:var(--aura-sidebar-group-gap,.25rem)}');
});

test('the table toolbar keeps one row and the settings sub-panel switches color through a component class', function () {
    $css = auraBuiltCss();

    expect($css)
        ->toContain('.dark .aura-group-section{border-color:rgb(var(--gray-700) / .8)');

    $views = dirname(__DIR__, 2).'/resources/views';

    // One flex row on every width (phones may wrap it), so no host base class
    // such as .flex-col can stack it against an md: variant.
    expect(file_get_contents($views.'/livewire/table.blade.php'))
        ->toContain("'flex gap-2 items-center w-full'")
        ->toContain('aura-field aura-control-base')
        ->not->toContain('md:flex-row')
        ->not->toContain('flex-col');

    expect(file_get_contents($views.'/components/fields/group.blade.php'))
        ->toContain('aura-group-section')
        ->not->toContain('dark:border-gray-700/80');
});

test('token-styled panels carry no padding utility that competes with the panel padding', function () {
    $panelClass = '/(?<![\w-])aura-(?:card|card-small|group-section|login-card)(?![\w-])/';
    $offenders = [];

    foreach (auraViewClasses() as [$file, $class]) {
        if (preg_match($panelClass, $class) && preg_match(auraPaddingUtility(), $class)) {
            $offenders[] = $file.': '.$class;
        }
    }

    expect($offenders)->toBe([]);
});

test('panel rows and cells take their gutter from the layout gap, not px/-mx utility pairs', function () {
    $offenders = [];

    foreach (auraViewClasses() as [$file, $class]) {
        // Rows and cells own the horizontal margin and padding; grids own the gap.
        if (preg_match('/(?<![\w-])aura-layout-(?:row|cell)(?![\w-])/', $class)
            && preg_match('/(?<![\w:\[-])-?(?:m|p)[xlr]-(?:\d|px|\[)/', $class)) {
            $offenders[] = $file.': '.$class;
        }

        if (preg_match('/(?<![\w-])aura-layout-grid(?![\w-])/', $class) && preg_match('/(?<![\w:-])(?:gap|space-[xy])-\d/', $class)) {
            $offenders[] = $file.': '.$class;
        }

        // The old gutter: a wrapping row pulled out with -mx-2/-mx-4 around px-2/px-4 cells.
        if (preg_match('/(?<![\w-])flex-wrap(?![\w-])/', $class) && preg_match('/(?<![\w:])-mx-(?:2|4)(?![\w-])/', $class)) {
            $offenders[] = $file.': '.$class;
        }
    }

    expect($offenders)->toBe([]);

    $views = dirname(__DIR__, 2).'/resources/views/components';

    expect(file_get_contents($views.'/fields/fields.blade.php'))->toContain('aura-layout-row')
        ->and(file_get_contents($views.'/fields/wrapper.blade.php'))->toContain("'wrapperClass' => 'aura-layout-cell'")
        ->and(file_get_contents($views.'/fields/panel.blade.php'))->toContain('class="aura-layout-cell aura-layout-panel"')->not->toContain('mb-8')
        ->and(file_get_contents($views.'/fields/tabs.blade.php'))->toContain('aura-layout-cell')
        ->and(file_get_contents($views.'/fields/has-many.blade.php'))->toContain('aura-layout-cell')->not->toMatch('/(?<![\w-])px-2(?![\w-])/')
        ->and(file_get_contents($views.'/widgets/index.blade.php'))->toContain('aura-layout-row aura-widget-grid')->not->toContain('pb-4');
});

test('dashboard panels, stat cards and sparklines follow the panel tokens', function () {
    $views = dirname(__DIR__, 2).'/resources/views';
    $offenders = [];

    foreach (Finder::create()->files()->in([$views.'/components/dashboard', $views.'/components/widgets'])->name('*.blade.php') as $file) {
        $contents = $file->getContents();

        // Panels: no fixed panel radius or padding (the old rounded-xl … p-6 wrapper and the p-5 stat card).
        if (preg_match('/(?<![\w:-])(?:rounded-(?:lg|xl|2xl)|p-[56]|-mx-5|-mb-5)(?![\w-])/', $contents, $match)) {
            $offenders[] = $file->getRelativePathname().': '.$match[0];
        }
    }

    expect($offenders)->toBe([])
        ->and(file_get_contents($views.'/livewire/dashboard.blade.php'))->toContain('aura-layout-grid')->not->toContain('gap-6')
        ->and(file_get_contents($views.'/components/widgets/sparkline-area.blade.php'))->toContain('aura-card-bleed')
        ->and(file_get_contents($views.'/components/widgets/sparkline-bar.blade.php'))->toContain('aura-card-bleed');
});

test('the built CSS derives gutters, bleeds and floating surfaces from the tokens', function () {
    // Compare without whitespace: the minifier writes calc(a / -2) with spaces.
    $css = preg_replace('/\s+/', '', auraBuiltCss());

    expect($css)
        ->toContain('.aura-layout-row.aura-layout-row{margin-left:calc(var(--aura-layout-gap,1.5rem)/-2);margin-right:calc(var(--aura-layout-gap,1.5rem)/-2);width:calc(100%+var(--aura-layout-gap,1.5rem))}')
        ->toContain('.aura-layout-cell.aura-layout-cell{padding-left:calc(var(--aura-layout-gap,1.5rem)/2);padding-right:calc(var(--aura-layout-gap,1.5rem)/2)}')
        ->toContain('.aura-layout-panel.aura-layout-panel{margin-bottom:var(--aura-layout-gap,1.5rem)}')
        ->toContain('.aura-layout-grid.aura-layout-grid{gap:var(--aura-layout-gap,1.5rem)}')
        ->toContain('.aura-card-bleed.aura-card-bleed{margin-left:calc(var(--aura-panel-padding,1.25rem)*-1);margin-right:calc(var(--aura-panel-padding,1.25rem)*-1);margin-bottom:calc(var(--aura-panel-padding,1.25rem)*-1)}')
        ->toContain('.aura-group-section.aura-group-section{padding:var(--aura-panel-padding,1.25rem);margin-bottom:var(--aura-section-gap,1rem);border-radius:var(--aura-panel-radius,.75rem)}')
        ->toMatch('/\.tippy-box\[data-theme~=aura-small\]\{[^}]*border-radius:var\(--aura-panel-radius,\.75rem\)/');
});
