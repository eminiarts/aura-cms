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
    $tokenClass = '/(?<![\w-])aura-(?:button|field|card|card-small|sidebar-item|sidebar-dropdown-button|sidebar-dropdown-button-expanded|sidebar-dropdown-collapsed|sidebar-search|sidebar-toggle)(?![\w-])/';
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
        ->toContain('.aura-sidebar-toggle.aura-sidebar-toggle{border-radius:var(--aura-control-radius,.5rem)}')
        ->toContain('.aura-sidebar-groups.aura-sidebar-groups{gap:var(--aura-sidebar-group-gap,.25rem)}');
});

test('the table toolbar and settings sub-panel switch layout and color through component classes', function () {
    $css = auraBuiltCss();

    expect($css)
        ->toMatch('/\.aura-table-toolbar\{[^}]*flex-direction:column/')
        ->toMatch('/@media \((?:min-width:768px|width>=768px)\)\{\.aura-table-toolbar\{[^}]*flex-direction:row/')
        ->toContain('.dark .aura-group-section{border-color:rgb(var(--gray-700) / .8)');

    $views = dirname(__DIR__, 2).'/resources/views';

    expect(file_get_contents($views.'/livewire/table.blade.php'))
        ->toContain('class="aura-table-toolbar"')
        ->not->toContain('md:flex-row');

    expect(file_get_contents($views.'/components/fields/group.blade.php'))
        ->toContain('aura-group-section')
        ->not->toContain('dark:border-gray-700/80');
});
