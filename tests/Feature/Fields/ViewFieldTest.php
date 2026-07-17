<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\View;

describe('View Field Configuration', function () {
    test('has correct edit property and type', function () {
        $field = new View;

        expect($field->edit)->toBe('aura::fields.view')
            ->and($field->type)->toBe('view');
    });

    test('has a view configuration field', function () {
        $fields = collect((new View)->getFields());

        expect($fields->firstWhere('slug', 'view'))->not->toBeNull();
    });

    test('is not treated as an input or relation field', function () {
        $field = new View;

        expect($field->isInputField())->toBeFalse()
            ->and($field->isRelation())->toBeFalse();
    });

    test('view() falls back to the edit view', function () {
        // View has no $view property, so view() returns the edit view.
        expect((new View)->view())->toBe('aura::fields.view');
    });
});
