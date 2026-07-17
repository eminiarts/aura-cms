<?php

namespace Tests\Feature\Fields;

/*
 * Guard test for the "every field type has coverage" acceptance criterion
 * (issue #42). It fails when a new field class ships under src/Fields/ without
 * any accompanying reference in the test suite — the tripwire that keeps field
 * coverage from regressing.
 */

function auraFieldClasses(): array
{
    $dir = dirname(__DIR__, 3).'/src/Fields';
    $classes = [];

    foreach (glob($dir.'/*.php') as $file) {
        $name = basename($file, '.php');

        // The abstract base class is not a shippable field type.
        if ($name === 'Field') {
            continue;
        }

        $classes[] = $name;
    }

    sort($classes);

    return $classes;
}

function auraTestSourceBlob(): string
{
    $testDir = dirname(__DIR__, 2); // tests/
    $blob = '';

    $iterator = new \RecursiveIteratorIterator(
        new \RecursiveDirectoryIterator($testDir, \FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if ($file->isFile() && $file->getExtension() === 'php') {
            // Skip this guard file itself.
            if ($file->getFilename() === 'FieldCoverageGuardTest.php') {
                continue;
            }
            $blob .= file_get_contents($file->getPathname())."\n";
        }
    }

    return $blob;
}

test('every field class under src/Fields has a corresponding test reference', function () {
    $blob = auraTestSourceBlob();

    $uncovered = [];

    foreach (auraFieldClasses() as $class) {
        // Match a reference to the field class, e.g. Fields\Text or Fields\\Text.
        // Require a word boundary after the class name so "Text" does not match
        // "Textarea".
        $pattern = '/Fields\\\\{1,2}'.preg_quote($class, '/').'\b/';

        if (! preg_match($pattern, $blob)) {
            $uncovered[] = $class;
        }
    }

    expect($uncovered)->toBe(
        [],
        'These field classes have no test reference: '.implode(', ', $uncovered)
    );
});

test('sanity: the guard can enumerate the field classes', function () {
    // Guards the guard: if the src/Fields path breaks, the coverage check above
    // would silently pass with an empty class list.
    expect(auraFieldClasses())->not->toBeEmpty()
        ->and(auraFieldClasses())->toContain('Text', 'Datetime', 'Heading', 'HorizontalLine');
});
