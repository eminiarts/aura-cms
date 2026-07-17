<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Time;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class TimeFieldModel extends Resource
{
    public static $singularName = 'Time Model';

    public static ?string $slug = 'timemodel';

    public static string $type = 'TimeModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Time for Test',
                'type' => 'Aura\\Base\\Fields\\Time',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'time',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Time Field Configuration', function () {
    test('has correct option group and edit property', function () {
        $field = new Time;

        expect($field->optionGroup)->toBe('Input Fields')
            ->and($field->edit)->toBe('aura::fields.time')
            ->and($field->edit())->toBe('aura::fields.time');
    });

    test('has required configuration fields with defaults', function () {
        $fields = collect((new Time)->getFields());

        expect($fields->firstWhere('slug', 'format'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'format')['default'])->toBe('H:i')
            ->and($fields->firstWhere('slug', 'display_format')['default'])->toBe('H:i')
            ->and($fields->firstWhere('slug', 'enable_input')['default'])->toBe(true)
            ->and($fields->firstWhere('slug', 'enable_seconds')['default'])->toBe(false);
    });
});

describe('Time Field Value Handling', function () {
    test('get, set and value return the value unchanged', function () {
        $field = new Time;

        expect($field->get(null, '12:30'))->toBe('12:30')
            ->and($field->get(null, null))->toBeNull()
            ->and($field->set(null, [], '12:30'))->toBe('12:30')
            ->and($field->value('12:30'))->toBe('12:30');
    });
});

describe('Time Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new TimeFieldModel);
    });

    test('renders in create form', function () {
        Livewire::test(Create::class, ['slug' => 'timemodel'])
            ->assertOk()
            ->assertSee('Time for Test');
    });

    test('saves time value round-trip', function () {
        Livewire::test(Create::class, ['slug' => 'timemodel'])
            ->set('form.fields.time', '14:45')
            ->call('save')
            ->assertHasNoErrors(['form.fields.time']);

        $model = TimeFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['time'])->toBe('14:45')
            ->and($model->time)->toBe('14:45');
    });
});
