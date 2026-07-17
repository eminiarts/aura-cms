<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Wysiwyg;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class WysiwygFieldModel extends Resource
{
    public static $singularName = 'Wysiwyg Model';

    public static ?string $slug = 'wysiwygmodel';

    public static string $type = 'WysiwygModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Body for Test',
                'type' => 'Aura\\Base\\Fields\\Wysiwyg',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'body',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Wysiwyg Field Configuration', function () {
    test('has correct properties', function () {
        $field = new Wysiwyg;

        expect($field->optionGroup)->toBe('JS Fields')
            ->and($field->edit)->toBe('aura::fields.wysiwyg')
            ->and($field->view)->toBe('aura::fields.view-value')
            ->and($field->tableColumnType)->toBe('text');
    });
});

describe('Wysiwyg Field Sanitization', function () {
    test('sanitize strips script tags', function () {
        $dirty = '<p>Hello</p><script>alert("xss")</script>';
        $clean = Wysiwyg::sanitize($dirty);

        expect($clean)->toContain('<p>Hello</p>')
            ->and($clean)->not->toContain('<script>');
    });

    test('sanitize keeps safe markup', function () {
        $html = '<p><strong>Bold</strong> and <em>italic</em></p>';

        expect(Wysiwyg::sanitize($html))->toContain('<strong>Bold</strong>');
    });

    test('sanitize returns empty string for null or empty input', function () {
        expect(Wysiwyg::sanitize(null))->toBe('')
            ->and(Wysiwyg::sanitize(''))->toBe('');
    });

    test('display sanitizes string values', function () {
        $field = new Wysiwyg;

        $result = $field->display([], '<p>ok</p><script>alert(1)</script>', null);

        expect($result)->toContain('<p>ok</p>')
            ->and($result)->not->toContain('<script>');
    });

    test('display returns non-string values unchanged', function () {
        $field = new Wysiwyg;

        expect($field->display([], ['a' => 1], null))->toBe(['a' => 1]);
    });
});

describe('Wysiwyg Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new WysiwygFieldModel);
    });

    test('saves html value round-trip', function () {
        Livewire::test(Create::class, ['slug' => 'wysiwygmodel'])
            ->set('form.fields.body', '<p>Hello world</p>')
            ->call('save')
            ->assertHasNoErrors(['form.fields.body']);

        $model = WysiwygFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['body'])->toBe('<p>Hello world</p>');
    });
});
