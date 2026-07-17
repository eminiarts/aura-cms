<?php

it('declares the V1 support matrix in Composer', function () {
    $composer = json_decode(file_get_contents(__DIR__.'/../../../composer.json'), true, flags: JSON_THROW_ON_ERROR);

    expect($composer['require']['php'])->toBe('^8.4')
        ->and($composer['require']['illuminate/support'])->toBe('^12.0|^13.0')
        ->and($composer['require']['livewire/livewire'])->toBe('^4.0')
        ->and($composer['require-dev']['laravel/framework'])->toBe('^12.0|^13.0')
        ->and($composer['require-dev']['orchestra/testbench'])->toBe('^10.0|^11.0')
        ->and($composer['minimum-stability'])->toBe('stable');
});
