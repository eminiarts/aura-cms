<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\File;

describe('File Field Configuration', function () {
    test('has correct properties', function () {
        $field = new File;

        expect($field->optionGroup)->toBe('Media Fields')
            ->and($field->edit)->toBe('aura::fields.file')
            ->and($field->view)->toBe('aura::fields.view-value');
    });
});

describe('File Field Value Handling', function () {
    test('get decodes JSON strings into arrays', function () {
        $field = new File;

        expect($field->get(null, '[1,2,3]'))->toBe([1, 2, 3]);
    });

    test('get returns arrays unchanged', function () {
        $field = new File;

        expect($field->get(null, [1, 2]))->toBe([1, 2]);
    });

    test('set encodes arrays as JSON', function () {
        $field = new File;

        expect($field->set(null, [], [1, 2]))->toBe('[1,2]');
    });

    test('set returns null (nothing) for null value', function () {
        $field = new File;

        expect($field->set(null, [], null))->toBeNull();
    });

    test('set returns scalar values unchanged', function () {
        $field = new File;

        expect($field->set(null, [], 'file.pdf'))->toBe('file.pdf');
    });

    test('round-trips an array through set then get', function () {
        $field = new File;
        $value = [10, 20, 30];

        expect($field->get(null, $field->set(null, [], $value)))->toBe($value);
    });
});
