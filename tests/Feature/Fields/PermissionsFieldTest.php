<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\Permissions;

describe('Permissions Field Configuration', function () {
    test('has correct edit and view properties', function () {
        $field = new Permissions;

        expect($field->edit)->toBe('aura::fields.permissions')
            ->and($field->view)->toBe('aura::fields.permissions-view');
    });

    test('has resource configuration field', function () {
        $fields = collect((new Permissions)->getFields());

        expect($fields->firstWhere('slug', 'resource'))->not->toBeNull();
    });
});

describe('Permissions Field Value Handling', function () {
    test('get decodes JSON strings into arrays', function () {
        $field = new Permissions;

        expect($field->get(null, '{"view-post":true}'))->toBe(['view-post' => true]);
    });

    test('get returns arrays unchanged', function () {
        $field = new Permissions;

        expect($field->get(null, ['view-post' => true]))->toBe(['view-post' => true]);
    });

    test('set encodes value as JSON', function () {
        $field = new Permissions;

        expect($field->set(null, [], ['view-post' => true]))->toBe('{"view-post":true}');
    });

    test('round-trips permissions through set then get', function () {
        $field = new Permissions;
        $value = ['view-post' => true, 'delete-post' => false];

        expect($field->get(null, $field->set(null, [], $value)))->toBe($value);
    });
});
