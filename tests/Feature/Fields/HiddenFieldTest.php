<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Hidden;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class HiddenFieldModel extends Resource
{
    public static $singularName = 'Hidden Model';

    public static ?string $slug = 'hiddenmodel';

    public static string $type = 'HiddenModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Hidden for Test',
                'type' => 'Aura\\Base\\Fields\\Hidden',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'secret',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Hidden Field Configuration', function () {
    test('has correct edit and view properties', function () {
        $field = new Hidden;

        expect($field->edit)->toBe('aura::fields.hidden')
            ->and($field->view)->toBe('aura::fields.view-hidden');
    });
});

describe('Hidden Field Value Handling', function () {
    test('get and value return the value unchanged', function () {
        $field = new Hidden;

        expect($field->get(null, 'token-123'))->toBe('token-123')
            ->and($field->value('token-123'))->toBe('token-123');
    });
});

describe('Hidden Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new HiddenFieldModel);
    });

    test('saves hidden value round-trip', function () {
        Livewire::test(Create::class, ['slug' => 'hiddenmodel'])
            ->set('form.fields.secret', 'token-123')
            ->call('save')
            ->assertHasNoErrors(['form.fields.secret']);

        $model = HiddenFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['secret'])->toBe('token-123');
    });
});
