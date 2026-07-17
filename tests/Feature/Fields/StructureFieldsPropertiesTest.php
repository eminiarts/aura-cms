<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\Group;
use Aura\Base\Fields\Panel;
use Aura\Base\Fields\Repeater;
use Aura\Base\Fields\Tab;
use Aura\Base\Fields\Tabs;

/*
 * Structural coverage for the grouping/layout wrapper fields. Their render
 * behaviour (flat-sibling child tree) is proven in StructureFieldsRenderTest;
 * this file locks in each class's structural contract.
 */

describe('Panel Field', function () {
    test('is a same-level grouping structure field', function () {
        $field = new Panel;

        expect($field->group)->toBeTrue()
            ->and($field->sameLevelGrouping)->toBeTrue()
            ->and($field->type)->toBe('panel')
            ->and($field->optionGroup)->toBe('Structure Fields')
            ->and($field->edit)->toBe('aura::fields.panel');
    });

    test('strips on_* and searchable option fields', function () {
        $slugs = collect((new Panel)->getFields())->pluck('slug');

        expect($slugs)->not->toContain('searchable')
            ->and($slugs)->not->toContain('on_index')
            ->and($slugs)->not->toContain('on_forms')
            ->and($slugs)->not->toContain('on_view');
    });
});

describe('Tab Field', function () {
    test('is a same-level grouping structure field wrapped by Tabs', function () {
        $field = new Tab;

        expect($field->group)->toBeTrue()
            ->and($field->sameLevelGrouping)->toBeTrue()
            ->and($field->type)->toBe('tab')
            ->and($field->view)->toBe('aura::fields.tab')
            ->and($field->wrapper)->toBe(Tabs::class);
    });

    test('strips on_* option fields', function () {
        $slugs = collect((new Tab)->getFields())->pluck('slug');

        expect($slugs)->not->toContain('on_index')
            ->and($slugs)->not->toContain('on_forms')
            ->and($slugs)->not->toContain('on_view');
    });
});

describe('Tabs Field', function () {
    test('is a non-same-level grouping wrapper', function () {
        $field = new Tabs;

        expect($field->group)->toBeTrue()
            ->and($field->sameLevelGrouping)->toBeFalse()
            ->and($field->type)->toBe('tabs')
            ->and($field->edit)->toBe('aura::fields.tabs')
            ->and($field->view)->toBe('aura::fields.tabs');
    });
});

describe('Group Field', function () {
    test('is a grouping structure field', function () {
        $field = new Group;

        expect($field->group)->toBeTrue()
            ->and($field->type)->toBe('group')
            ->and($field->optionGroup)->toBe('Structure Fields')
            ->and($field->edit)->toBe('aura::fields.group')
            ->and($field->isInputField())->toBeTrue();
    });
});

describe('Repeater Field', function () {
    test('is a grouping input field', function () {
        $field = new Repeater;

        expect($field->group)->toBeTrue()
            ->and($field->type)->toBe('input')
            ->and($field->optionGroup)->toBe('Structure Fields')
            ->and($field->edit)->toBe('aura::fields.repeater')
            ->and($field->isInputField())->toBeTrue();
    });

    test('has min and max entry configuration fields', function () {
        $fields = collect((new Repeater)->getFields());

        expect($fields->firstWhere('slug', 'min'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'max'))->not->toBeNull();
    });

    test('get decodes JSON strings and passes arrays through', function () {
        $field = new Repeater;

        expect($field->get(null, '[{"x":1}]'))->toBe([['x' => 1]])
            ->and($field->get(null, [['x' => 1]]))->toBe([['x' => 1]]);
    });

    test('set encodes arrays as JSON', function () {
        $field = new Repeater;

        expect($field->set(null, [], [['x' => 1]]))->toBe('[{"x":1}]');
    });

    test('round-trips repeater rows through set then get', function () {
        $field = new Repeater;
        $rows = [['question' => 'Q1', 'answer' => 'A1'], ['question' => 'Q2', 'answer' => 'A2']];

        expect($field->get(null, $field->set(null, [], $rows)))->toBe($rows);
    });

    test('transform namespaces child slugs per row index', function () {
        $field = new Repeater;
        $definition = [
            'slug' => 'faq',
            'fields' => [
                ['name' => 'Question', 'slug' => 'question'],
                ['name' => 'Answer', 'slug' => 'answer'],
            ],
        ];

        $result = $field->transform($definition, [['question' => 'Q', 'answer' => 'A']]);

        $slugs = collect($result->first())->pluck('slug')->all();
        expect($slugs)->toBe(['faq.0.question', 'faq.0.answer']);
    });
});
