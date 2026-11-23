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

test('token-driven elements carry no competing radius utility in the package views', function () {
    $tokenClass = '/(?<![\w-])aura-(?:button|card|card-small|sidebar-item|sidebar-dropdown-button|sidebar-dropdown-button-expanded|sidebar-dropdown-collapsed|sidebar-search|sidebar-toggle)(?![\w-])/';
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

test('the built CSS sets token properties with a doubled class selector', function () {
    $css = auraBuiltCss();

    expect($css)
        ->toContain('.aura-card.aura-card{padding:var(--aura-panel-padding,1.25rem);border-radius:var(--aura-panel-radius,.75rem)}')
        ->toContain('.aura-card-small.aura-card-small{padding:var(--aura-panel-small-padding,.5rem);border-radius:var(--aura-control-radius,.5rem)}')
        ->toContain('.aura-button.aura-button,')
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
