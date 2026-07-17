<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\ID;

describe('ID Field Configuration', function () {
    test('has correct edit and view properties', function () {
        $field = new ID;

        expect($field->edit)->toBe('aura::fields.text')
            ->and($field->view)->toBe('aura::fields.view-value');
    });

    test('is not shown on forms', function () {
        expect((new ID)->on_forms)->toBeFalse();
    });

    test('uses an auto-incrementing non-nullable column', function () {
        $field = new ID;

        expect($field->tableColumnType)->toBe('bigIncrements')
            ->and($field->tableNullable)->toBeFalse();
    });

    test('is an input field type', function () {
        expect((new ID)->type)->toBe('input');
    });
});

describe('ID Field Value Handling', function () {
    test('get and value return the value unchanged', function () {
        $field = new ID;

        expect($field->get(null, 42))->toBe(42)
            ->and($field->value(42))->toBe(42);
    });
});
