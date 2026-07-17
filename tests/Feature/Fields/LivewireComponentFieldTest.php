<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\LivewireComponent;

describe('LivewireComponent Field Configuration', function () {
    test('has correct edit property and type', function () {
        $field = new LivewireComponent;

        expect($field->edit)->toBe('aura::fields.livewire-component')
            ->and($field->type)->toBe('livewire-component');
    });

    test('has component configuration field', function () {
        $fields = collect((new LivewireComponent)->getFields());

        expect($fields->firstWhere('slug', 'component'))->not->toBeNull();
    });

    test('is not treated as an input or relation field', function () {
        $field = new LivewireComponent;

        expect($field->isInputField())->toBeFalse()
            ->and($field->isRelation())->toBeFalse();
    });
});
