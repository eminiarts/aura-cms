<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Email;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Livewire\Livewire;

class EmailFieldModel extends Resource
{
    public static $singularName = 'Email Model';

    public static ?string $slug = 'email';

    public static string $type = 'EmailModel';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Email for Test',
                'type' => 'Aura\\Base\\Fields\\Email',
                'validation' => 'required|email',
                'conditional_logic' => [],
                'slug' => 'email',
            ],
        ];
    }
}

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Email Field Configuration', function () {
    test('has required configuration fields', function () {
        $emailField = new Email;
        $fields = collect($emailField->getFields());

        expect($fields->firstWhere('slug', 'placeholder'))->not->toBeNull()
            ->and($fields->firstWhere('slug', 'default'))->not->toBeNull();
    });

    test('does not have prefix and suffix fields', function () {
        $emailField = new Email;
        $fields = collect($emailField->getFields());

        expect($fields->firstWhere('slug', 'prefix'))->toBeNull()
            ->and($fields->firstWhere('slug', 'suffix'))->toBeNull();
    });

    test('has correct option group', function () {
        $emailField = new Email;

        expect($emailField->optionGroup)->toBe('Input Fields');
    });

    test('has correct edit and view properties', function () {
        $emailField = new Email;

        expect($emailField->edit)->toBe('aura::fields.email')
            ->and($emailField->view)->toBe('aura::fields.view-value');
    });

    test('edit method returns edit property', function () {
        $emailField = new Email;

        expect($emailField->edit())->toBe('aura::fields.email');
    });

    test('view method returns view property', function () {
        $emailField = new Email;

        expect($emailField->view())->toBe('aura::fields.view-value');
    });
});

describe('Email Field Rendering', function () {
    test('renders field name as label', function () {
        $field = [
            'name' => 'Email for Test',
            'type' => 'Aura\\Base\\Fields\\Email',
            'validation' => '',
            'conditional_logic' => [],
            'slug' => 'email',
        ];

        $fieldClass = app($field['type']);
        $field['field'] = $fieldClass;

        $view = $this->withViewErrors([])->blade(
            '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
            ['component' => $fieldClass->edit(), 'field' => $field, 'form' => []]
        );

        expect((string) $view)->toContain('>Email for Test</label>')
            ->and((string) $view)->toContain('type="email"');
    });

    test('renders placeholder attribute', function () {
        $field = [
            'name' => 'Email for Test',
            'type' => 'Aura\\Base\\Fields\\Email',
            'validation' => '',
            'conditional_logic' => [],
            'placeholder' => 'Enter your email',
            'slug' => 'email',
        ];

        $fieldClass = app($field['type']);
        $field['field'] = $fieldClass;

        $view = $this->withViewErrors([])->blade(
            '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
            ['component' => $fieldClass->edit(), 'field' => $field, 'form' => []]
        );

        expect((string) $view)->toContain('placeholder="Enter your email"');
    });
});

describe('Email Field Validation', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new EmailFieldModel);
    });

    test('validates required field', function () {
        Livewire::test(Create::class, ['slug' => 'email'])
            ->call('save')
            ->assertHasErrors(['form.fields.email']);
    });

    test('validates email format', function () {
        Livewire::test(Create::class, ['slug' => 'email'])
            ->set('form.fields.email', 'invalid-email')
            ->call('save')
            ->assertHasErrors(['form.fields.email']);
    });

    test('rejects email with trailing whitespace', function () {
        Livewire::test(Create::class, ['slug' => 'email'])
            ->set('form.fields.email', 'example@example.com ')
            ->call('save')
            ->assertHasErrors(['form.fields.email']);
    });

    test('accepts valid email address', function () {
        Livewire::test(Create::class, ['slug' => 'email'])
            ->set('form.fields.email', 'example@example.com')
            ->call('save')
            ->assertHasNoErrors(['form.fields.email']);
    });
});

describe('Email Field in Livewire', function () {
    beforeEach(function () {
        Aura::fake();
        Aura::setModel(new EmailFieldModel);
    });

    test('renders in create form', function () {
        Livewire::test(Create::class, ['slug' => 'email'])
            ->assertSee('Create Email Model')
            ->assertSee('Email for Test')
            ->assertSeeHtml('type="email"');
    });

    test('saves email to database', function () {
        Livewire::test(Create::class, ['slug' => 'email'])
            ->set('form.fields.email', 'example@example.com')
            ->call('save')
            ->assertHasNoErrors(['form.fields.email']);

        $this->assertDatabaseHas('posts', ['type' => 'EmailModel']);

        $model = EmailFieldModel::first();
        expect($model->fields['email'])->toBe('example@example.com')
            ->and($model->email)->toBe('example@example.com');
    });
});

describe('Email Field Value Handling', function () {
    test('get method returns value unchanged', function () {
        $emailField = new Email;

        expect($emailField->get(null, 'test@example.com'))->toBe('test@example.com')
            ->and($emailField->get(null, ''))->toBe('')
            ->and($emailField->get(null, null))->toBeNull();
    });

    test('value method returns value unchanged', function () {
        $emailField = new Email;

        expect($emailField->value('test@example.com'))->toBe('test@example.com')
            ->and($emailField->value(''))->toBe('')
            ->and($emailField->value(null))->toBeNull();
    });

    test('filterOptions returns correct string filters', function () {
        $emailField = new Email;
        $options = $emailField->filterOptions();

        expect($options)->toHaveKey('contains')
            ->and($options)->toHaveKey('is')
            ->and($options)->toHaveKey('starts_with')
            ->and($options)->toHaveKey('ends_with')
            ->and($options)->toHaveKey('is_empty')
            ->and($options)->toHaveKey('is_not_empty');
    });
});
