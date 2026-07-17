<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\ViewValue;

describe('ViewValue Field Configuration', function () {
    test('uses the view-value template for both edit and view', function () {
        $field = new ViewValue;

        expect($field->edit)->toBe('aura::fields.view-value')
            ->and($field->view)->toBe('aura::fields.view-value')
            ->and($field->edit())->toBe('aura::fields.view-value')
            ->and($field->view())->toBe('aura::fields.view-value');
    });
});

describe('ViewValue Field Value Handling', function () {
    test('get and value return the value unchanged', function () {
        $field = new ViewValue;

        expect($field->get(null, 'display me'))->toBe('display me')
            ->and($field->value('display me'))->toBe('display me');
    });

    test('display HTML-escapes scalar values', function () {
        $field = new ViewValue;

        expect($field->display([], '<b>x</b>', null))->toBe('&lt;b&gt;x&lt;/b&gt;');
    });
});
