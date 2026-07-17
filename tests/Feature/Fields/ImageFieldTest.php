<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\Image;

describe('Image Field Configuration', function () {
    test('has correct properties', function () {
        $field = new Image;

        expect($field->optionGroup)->toBe('Media Fields')
            ->and($field->edit)->toBe('aura::fields.image')
            ->and($field->view)->toBe('aura::fields.view-value');
    });

    test('has media manager and file constraint configuration fields', function () {
        $fields = collect((new Image)->getFields());

        expect($fields->firstWhere('slug', 'use_media_manager'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'min_files'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'max_files'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'allowed_file_types'))->not->toBeNull();
    });
});

describe('Image Field Value Handling', function () {
    test('get decodes JSON strings into arrays', function () {
        $field = new Image;

        expect($field->get(null, '[1,2]'))->toBe([1, 2]);
    });

    test('get returns arrays unchanged', function () {
        $field = new Image;

        expect($field->get(null, [1, 2]))->toBe([1, 2]);
    });

    test('set encodes value as JSON', function () {
        $field = new Image;

        expect($field->set(null, [], [1, 2]))->toBe('[1,2]');
    });
});

describe('Image Field Display', function () {
    test('display returns null when value is empty', function () {
        $field = new Image;

        expect($field->display([], null, null))->toBeNull();
    });

    test('display returns the raw value when no attachment is found', function () {
        // No attachment exists for id 999999, so display falls back to the raw value.
        $field = new Image;

        expect($field->display([], 999999, null))->toBe(999999);
    });
});
