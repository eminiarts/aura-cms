<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\AdvancedSelect;
use Aura\Base\Fields\HasOne;

describe('HasOne Field Configuration', function () {
    test('extends AdvancedSelect', function () {
        expect(new HasOne)->toBeInstanceOf(AdvancedSelect::class);
    });

    test('has correct relationship properties', function () {
        $field = new HasOne;

        expect($field->optionGroup)->toBe('Relationship Fields')
            ->and($field->edit)->toBe('aura::fields.has-one')
            ->and($field->type)->toBe('relation')
            ->and($field->multiple)->toBeFalse()
            ->and($field->searchable)->toBeTrue()
            ->and($field->api)->toBeTrue()
            ->and($field->group)->toBeFalse();
    });

    test('is treated as a relation field', function () {
        expect((new HasOne)->isRelation())->toBeTrue();
    });
});

describe('HasOne Field Value Handling', function () {
    test('get normalises a single id into a one-element array', function () {
        $field = new HasOne;
        $definition = ['multiple' => false];

        expect($field->get(null, 5, $definition))->toBe([5]);
    });

    test('get returns an empty array for empty non-multiple values', function () {
        $field = new HasOne;
        $definition = ['multiple' => false];

        expect($field->get(null, null, $definition))->toBe([]);
    });

    test('set JSON-encodes the value', function () {
        $field = new HasOne;

        expect($field->set(null, ['multiple' => false], [5]))->toBe('[5]');
    });
});
