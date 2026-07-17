<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\Json;

describe('Json Field Configuration', function () {
    test('has correct edit and view properties', function () {
        $field = new Json;

        expect($field->edit)->toBe('aura::fields.json')
            ->and($field->view)->toBe('aura::fields.view-value');
    });
});

describe('Json Field Value Handling', function () {
    test('get decodes JSON strings to arrays', function () {
        $field = new Json;

        expect($field->get(null, '{"a":1,"b":2}'))->toBe(['a' => 1, 'b' => 2]);
    });

    test('get returns arrays and null unchanged', function () {
        $field = new Json;

        expect($field->get(null, ['a' => 1]))->toBe(['a' => 1])
            ->and($field->get(null, null))->toBeNull();
    });

    test('set encodes arrays to JSON', function () {
        $field = new Json;

        expect($field->set(null, [], ['a' => 1]))->toBe('{"a":1}');
    });

    test('set returns non-array values unchanged', function () {
        $field = new Json;

        expect($field->set(null, [], 'plain'))->toBe('plain');
    });

    test('round-trips an array through set then get', function () {
        $field = new Json;
        $value = ['nested' => ['x' => 1], 'list' => [1, 2, 3]];

        expect($field->get(null, $field->set(null, [], $value)))->toBe($value);
    });

    test('display JSON-encodes the value', function () {
        $field = new Json;

        expect($field->display([], ['a' => 1], null))->toBe('{"a":1}');
    });
});
