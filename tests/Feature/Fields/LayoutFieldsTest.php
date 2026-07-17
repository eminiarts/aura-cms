<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Heading;
use Aura\Base\Fields\HorizontalLine;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

/*
 * Heading and HorizontalLine are pure presentation fields (no stored value).
 * They only need structural smoke coverage: correct view wiring and rendering
 * inside a form without error.
 */

class LayoutFieldsModel extends Resource
{
    public static $singularName = 'Layout Fields Model';

    public static ?string $slug = 'layoutfieldsmodel';

    public static string $type = 'LayoutFieldsModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Section Heading',
                'type' => 'Aura\\Base\\Fields\\Heading',
                'slug' => 'section_heading',
            ],
            [
                'name' => 'Divider',
                'type' => 'Aura\\Base\\Fields\\HorizontalLine',
                'slug' => 'divider',
            ],
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'title',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Heading Field', function () {
    test('has correct edit property and layout option group', function () {
        $field = new Heading;

        expect($field->edit)->toBe('aura::fields.heading')
            ->and($field->optionGroup)->toBe('Layout Fields');
    });

    test('inherits the default input type from the base Field', function () {
        // Heading does not override $type, so it keeps the base 'input' type.
        expect((new Heading)->type)->toBe('input');
    });
});

describe('HorizontalLine Field', function () {
    test('has correct edit property and layout option group', function () {
        $field = new HorizontalLine;

        expect($field->edit)->toBe('aura::fields.hr')
            ->and($field->optionGroup)->toBe('Layout Fields');
    });

    test('inherits the default input type from the base Field', function () {
        // HorizontalLine does not override $type, so it keeps the base 'input' type.
        expect((new HorizontalLine)->type)->toBe('input');
    });
});

describe('Layout Fields Rendering', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new LayoutFieldsModel);
    });

    test('renders heading and divider in the create form without error', function () {
        Livewire::test(Create::class, ['slug' => 'layoutfieldsmodel'])
            ->assertOk()
            ->assertSee('Section Heading')
            ->assertSee('Title');
    });
});
