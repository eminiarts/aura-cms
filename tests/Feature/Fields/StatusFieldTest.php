<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Status;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class StatusFieldModel extends Resource
{
    public static $singularName = 'Status Model';

    public static ?string $slug = 'statusmodel';

    public static string $type = 'StatusModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Status for Test',
                'type' => 'Aura\\Base\\Fields\\Status',
                'validation' => '',
                'conditional_logic' => [],
                'slug' => 'state',
                'options' => [
                    ['key' => 'draft', 'value' => 'Draft', 'color' => 'bg-gray-100 text-gray-800'],
                    ['key' => 'published', 'value' => 'Published', 'color' => 'bg-green-100 text-green-800'],
                ],
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Status Field Configuration', function () {
    test('has correct properties', function () {
        $field = new Status;

        expect($field->optionGroup)->toBe('Choice Fields')
            ->and($field->edit)->toBe('aura::fields.status')
            ->and($field->view)->toBe('aura::fields.status-view')
            ->and($field->index)->toBe('aura::fields.status-index');
    });

    test('has options, default and color configuration fields', function () {
        $fields = collect((new Status)->getFields());

        expect($fields->firstWhere('slug', 'options'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'default'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'color'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'allow_multiple'))->not->toBeNull();
    });

    test('color configuration field ships with predefined color options', function () {
        $fields = collect((new Status)->getFields());
        $color = $fields->firstWhere('slug', 'color');

        expect($color['options'])->toBeArray()
            ->and(collect($color['options'])->pluck('value'))->toContain('Blue', 'Green', 'Red');
    });
});

describe('Status Field Options Resolution', function () {
    test('options returns field-defined options', function () {
        $field = new Status;
        $definition = ['slug' => 'state', 'options' => [['key' => 'draft', 'value' => 'Draft']]];

        expect($field->options(new StatusFieldModel, $definition))->toBe([['key' => 'draft', 'value' => 'Draft']]);
    });
});

describe('Status Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new StatusFieldModel);
    });

    test('saves status value round-trip', function () {
        Livewire::test(Create::class, ['slug' => 'statusmodel'])
            ->set('form.fields.state', 'published')
            ->call('save')
            ->assertHasNoErrors(['form.fields.state']);

        $model = StatusFieldModel::orderBy('id', 'desc')->first();
        expect($model->fields['state'])->toBe('published');
    });
});
