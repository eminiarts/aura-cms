<?php

it('declares the V1 support matrix in Composer', function () {
    $composer = json_decode(file_get_contents(__DIR__.'/../../../composer.json'), true, flags: JSON_THROW_ON_ERROR);

    // Only the runtime constraints are asserted: the CI matrix rewrites the
    // laravel/framework and orchestra/testbench dev constraints to pin a single
    // version per job, so asserting their committed ranges here would fail under
    // the very matrix that proves the support range.
    expect($composer['require']['php'])->toBe('^8.4')
        ->and($composer['require']['illuminate/support'])->toBe('^12.0.1|^13.0')
        ->and($composer['require']['livewire/livewire'])->toBe('~4.3.5')
        ->and($composer['minimum-stability'])->toBe('stable');
});

it('pins the Laravel 12 floor in CI', function () {
    $workflow = file_get_contents(__DIR__.'/../../../.github/workflows/run-tests.yml');

    expect($workflow)
        ->toContain('Laravel 12.0.1 floor')
        ->toContain('laravel/framework:12.0.1')
        ->toContain('orchestra/testbench:10.0.0')
        ->toContain('--prefer-lowest');
});
