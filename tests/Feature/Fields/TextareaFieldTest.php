<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Textarea;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class TextareaFieldModel extends Resource
{
    public static $singularName = 'Textarea Model';

    public static ?string $slug = 'textareamodel';

    public static string $type = 'TextareaModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Textarea for Test',
                'type' => 'Aura\\Base\\Fields\\Textarea',
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

describe('Textarea Field Configuration', function () {
    test('has correct properties', function () {
        $field = new Textarea;

        expect($field->optionGroup)->toBe('Input Fields')
            ->and($field->edit)->toBe('aura::fields.textarea')
            ->and($field->view)->toBe('aura::fields.view-value')
            ->and($field->tableColumnType)->toBe('text');
    });

    test('has placeholder, rows, default and max_length configuration fields', function () {
        $fields = collect((new Textarea)->getFields());

        expect($fields->firstWhere('slug', 'placeholder'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'default'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'max_length'))->not->toBeNull();

        $rows = $fields->firstWhere('slug', 'rows');
        expect($rows)->not->toBeNull()
            ->and($rows['default'])->toBe(3);
    });
});

describe('Textarea Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new TextareaFieldModel);
    });

    test('renders textarea in create form', function () {
        Livewire::test(Create::class, ['slug' => 'textareamodel'])
            ->assertOk()
            ->assertSee('Textarea for Test')
            ->assertSeeHtml('<textarea');
    });

    test('saves multiline value round-trip', function () {
        $value = "line one\nline two";

        Livewire::test(Create::class, ['slug' => 'textareamodel'])
            ->set('form.fields.body', $value)
            ->call('save')
            ->assertHasNoErrors(['form.fields.body']);

        $model = TextareaFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['body'])->toBe($value);
    });
});
