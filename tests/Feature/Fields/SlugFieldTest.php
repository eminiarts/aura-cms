<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Slug;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resource;
use Livewire\Livewire;

class SlugFieldModel extends Resource
{
    public static $singularName = 'Slug Model';

    public static ?string $slug = 'slug-model';

    public static string $type = 'SlugModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Text',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'text',
            ],
            [
                'name' => 'Slug for Test',
                'type' => 'Aura\\Base\\Fields\\Slug',
                'validation' => 'required|alpha_dash',
                'conditional_logic' => [],
                'slug' => 'slug',
                'based_on' => 'text',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
    Aura::fake();
    Aura::setModel(new SlugFieldModel);
});

describe('Slug Field Configuration', function () {
    test('has required configuration fields', function () {
        $slugField = new Slug;
        $fields = collect($slugField->getFields());

        expect($fields->firstWhere('slug', 'based_on'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'custom'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'disabled'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'default'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'placeholder'))->not->toBeNull();
    });

    test('has correct option group', function () {
        $slugField = new Slug;

        expect($slugField->optionGroup)->toBe('Input Fields');
    });

    test('has correct edit and view properties', function () {
        $slugField = new Slug;

        expect($slugField->edit)->toBe('aura::fields.slug')
            ->and($slugField->view)->toBe('aura::fields.view-value');
    });

    test('based_on field has required validation', function () {
        $slugField = new Slug;
        $fields = collect($slugField->getFields());

        $basedOnField = $fields->firstWhere('slug', 'based_on');
        expect($basedOnField['validation'])->toBe('required');
    });

    test('disabled field is Boolean type', function () {
        $slugField = new Slug;
        $fields = collect($slugField->getFields());

        $disabledField = $fields->firstWhere('slug', 'disabled');
        expect($disabledField['type'])->toBe('Aura\\Base\\Fields\\Boolean');
    });
});

describe('Slug Field Rendering', function () {
    test('renders in create form', function () {
        Livewire::test(Create::class, ['slug' => 'slug-model'])
            ->assertSee('Create Slug Model')
            ->assertSee('Slug for Test')
            ->assertSeeHtml('type="text"')
            ->assertSee('Slug');
    });

    test('renders without custom checkbox when custom is false', function () {
        $field = [
            'name' => 'Slug for Test',
            'type' => 'Aura\\Base\\Fields\\Slug',
            'validation' => 'required|alpha_dash',
            'conditional_logic' => [],
            'slug' => 'slug',
            'based_on' => 'text',
            'custom' => false,
        ];

        $fieldClass = app($field['type']);

        $view = $this->withViewErrors([])->blade(
            '<x-dynamic-component :component="$component" :field="$field" />',
            ['component' => $fieldClass->edit(), 'field' => $field]
        );

        $view->assertSee('Slug for Test');
        expect((string) $view)->not->toContain('<div class="custom-slug')
            ->and((string) $view)->toContain('custom: true,')
            ->and((string) $view)->toContain("value: \$wire.entangle('form.fields.slug')");
    });

    test('renders with custom checkbox when custom is true', function () {
        $field = [
            'name' => 'Slug for Test',
            'type' => 'Aura\\Base\\Fields\\Slug',
            'validation' => 'required|alpha_dash',
            'conditional_logic' => [],
            'slug' => 'slug',
            'based_on' => 'text',
            'custom' => true,
        ];

        $fieldClass = app($field['type']);

        $view = $this->withViewErrors([])->blade(
            '<x-dynamic-component :component="$component" :field="$field" />',
            ['component' => $fieldClass->edit(), 'field' => $field]
        );

        $view->assertSee('Slug for Test');
        expect((string) $view)->toContain('<div class="flex flex-col custom-slug')
            ->and((string) $view)->toContain('custom: true,');
    });

    test('renders disabled input when disabled is true', function () {
        $field = [
            'name' => 'Slug for Test',
            'type' => 'Aura\\Base\\Fields\\Slug',
            'validation' => 'required|alpha_dash',
            'conditional_logic' => [],
            'slug' => 'slug',
            'based_on' => 'text',
            'custom' => false,
            'disabled' => true,
        ];

        $fieldClass = app($field['type']);

        $view = $this->withViewErrors([])->blade(
            '<x-dynamic-component :component="$component" :field="$field" />',
            ['component' => $fieldClass->edit(), 'field' => $field]
        );

        $view->assertSee('Slug for Test');
        expect((string) $view)->not->toContain('<div class="custom-slug')
            ->and((string) $view)->toContain('custom: false,')
            ->and((string) $view)->toContain('x-bind:disabled="!custom"');
    });

    test('renders enabled input when disabled is false', function () {
        $field = [
            'name' => 'Slug for Test',
            'type' => 'Aura\\Base\\Fields\\Slug',
            'validation' => 'required|alpha_dash',
            'conditional_logic' => [],
            'slug' => 'slug',
            'based_on' => 'text',
            'disabled' => false,
        ];

        $fieldClass = app($field['type']);

        $view = $this->withViewErrors([])->blade(
            '<x-dynamic-component :component="$component" :field="$field" />',
            ['component' => $fieldClass->edit(), 'field' => $field]
        );

        $view->assertSee('Slug for Test');
        expect((string) $view)->not->toContain('<div class="custom-slug')
            ->and((string) $view)->toContain('custom: true,')
            ->and((string) $view)->toContain('x-bind:disabled="!custom"');
    });
});

describe('Slug Field Validation', function () {
    test('validates required slug', function () {
        Livewire::test(Create::class, ['slug' => 'slug-model'])
            ->call('save')
            ->assertHasErrors(['form.fields.slug']);
    });

    test('validates alpha_dash format', function () {
        Livewire::test(Create::class, ['slug' => 'slug-model'])
            ->set('form.fields.text', 'Custom Title')
            ->set('form.fields.slug', 'invalid slug')
            ->call('save')
            ->assertHasErrors(['form.fields.slug']);
    });

    test('accepts valid slug format', function () {
        Livewire::test(Create::class, ['slug' => 'slug-model'])
            ->set('form.fields.text', 'Custom Title')
            ->set('form.fields.slug', 'custom-title')
            ->call('save')
            ->assertHasNoErrors(['form.fields.slug']);
    });
});

describe('Slug Field in Livewire', function () {
    test('saves slug to database', function () {
        Livewire::test(Create::class, ['slug' => 'slug-model'])
            ->set('form.fields.text', 'Custom Title')
            ->set('form.fields.slug', 'custom-title')
            ->call('save')
            ->assertHasNoErrors(['form.fields.slug']);

        $this->assertDatabaseHas('posts', ['type' => 'SlugModel', 'slug' => 'custom-title']);

        $model = SlugFieldModel::first();
        expect($model->fields['slug'])->toBe('custom-title');
    });

    test('updates slug on edit', function () {
        // Create model
        Livewire::test(Create::class, ['slug' => 'slug-model'])
            ->set('form.fields.text', 'Custom Title')
            ->set('form.fields.slug', 'custom-title')
            ->call('save');

        $post = SlugFieldModel::first();

        Aura::fake();
        Aura::setModel(new SlugFieldModel);

        // Edit and update slug
        Livewire::test(Edit::class, ['slug' => 'SlugModel', 'id' => $post->id])
            ->set('form.fields.slug', 'updated-slug')
            ->call('save')
            ->assertHasNoErrors(['form.fields.slug']);

        $post = $post->refresh();
        expect($post->slug)->toBe('updated-slug');
    });

    test('rejects invalid slug on edit', function () {
        // Create model
        Livewire::test(Create::class, ['slug' => 'slug-model'])
            ->set('form.fields.text', 'Custom Title')
            ->set('form.fields.slug', 'custom-title')
            ->call('save');

        $post = SlugFieldModel::first();

        Aura::fake();
        Aura::setModel(new SlugFieldModel);

        // Edit with invalid slug
        Livewire::test(Edit::class, ['slug' => 'SlugModel', 'id' => $post->id])
            ->set('form.fields.slug', 'invalid slug')
            ->call('save')
            ->assertHasErrors(['form.fields.slug']);

        // Database should not have invalid slug
        $this->assertDatabaseMissing('posts', ['type' => 'SlugModel', 'slug' => 'invalid slug']);
    });
});

describe('Slug Field Value Handling', function () {
    test('get method returns value unchanged', function () {
        $slugField = new Slug;

        expect($slugField->get(null, 'my-slug'))->toBe('my-slug')
            ->and($slugField->get(null, null))->toBeNull();
    });

    test('value method returns value unchanged', function () {
        $slugField = new Slug;

        expect($slugField->value('my-slug'))->toBe('my-slug')
            ->and($slugField->value(''))->toBe('');
    });

    test('filterOptions returns correct filters', function () {
        $slugField = new Slug;
        $options = $slugField->filterOptions();

        expect($options)->toHaveKey('is')
            ->and($options)->toHaveKey('is_not')
            ->and($options)->toHaveKey('contains')
            ->and($options)->toHaveKey('is_empty')
            ->and($options)->toHaveKey('is_not_empty');
    });
});
