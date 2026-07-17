<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Color;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class ColorFieldModel extends Resource
{
    public static $singularName = 'Color Model';

    public static ?string $slug = 'colormodel';

    public static string $type = 'ColorModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Color for Test',
                'type' => 'Aura\\Base\\Fields\\Color',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'color',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Color Field Configuration', function () {
    test('has correct properties', function () {
        $field = new Color;

        expect($field->optionGroup)->toBe('JS Fields')
            ->and($field->edit)->toBe('aura::fields.color')
            ->and($field->view)->toBe('aura::fields.view-value');
    });

    test('has native and format configuration fields', function () {
        $fields = collect((new Color)->getFields());

        expect($fields->firstWhere('slug', 'native'))->not->toBeNull();

        $format = $fields->firstWhere('slug', 'format');
        expect($format)->not->toBeNull()
            ->and($format['options'])->toHaveKeys(['hex', 'rgb', 'hsl', 'hsv', 'cmyk']);
    });
});

describe('Color Field Value Handling', function () {
    test('get and value return the value unchanged', function () {
        $field = new Color;

        expect($field->get(null, '#ff0000'))->toBe('#ff0000')
            ->and($field->value('#ff0000'))->toBe('#ff0000');
    });
});

describe('Color Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new ColorFieldModel);
    });

    test('renders in create form', function () {
        Livewire::test(Create::class, ['slug' => 'colormodel'])
            ->assertOk()
            ->assertSee('Color for Test');
    });

    test('saves color value round-trip', function () {
        Livewire::test(Create::class, ['slug' => 'colormodel'])
            ->set('form.fields.color', '#123abc')
            ->call('save')
            ->assertHasNoErrors(['form.fields.color']);

        $model = ColorFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['color'])->toBe('#123abc');
    });
});
