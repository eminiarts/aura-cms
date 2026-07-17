<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Phone;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class PhoneFieldModel extends Resource
{
    public static $singularName = 'Phone Model';

    public static ?string $slug = 'phonemodel';

    public static string $type = 'PhoneModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Phone for Test',
                'type' => 'Aura\\Base\\Fields\\Phone',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'phone',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Phone Field Configuration', function () {
    test('has correct properties', function () {
        $field = new Phone;

        expect($field->optionGroup)->toBe('Input Fields')
            ->and($field->edit)->toBe('aura::fields.phone')
            ->and($field->view)->toBe('aura::fields.view-value');
    });
});

describe('Phone Field Value Handling', function () {
    test('get and value return the value unchanged', function () {
        $field = new Phone;

        expect($field->get(null, '+41791234567'))->toBe('+41791234567')
            ->and($field->value('+41791234567'))->toBe('+41791234567');
    });
});

describe('Phone Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new PhoneFieldModel);
    });

    test('renders in create form', function () {
        Livewire::test(Create::class, ['slug' => 'phonemodel'])
            ->assertOk()
            ->assertSee('Phone for Test');
    });

    test('saves phone value round-trip', function () {
        Livewire::test(Create::class, ['slug' => 'phonemodel'])
            ->set('form.fields.phone', '+41791234567')
            ->call('save')
            ->assertHasNoErrors(['form.fields.phone']);

        $model = PhoneFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['phone'])->toBe('+41791234567');
    });
});
