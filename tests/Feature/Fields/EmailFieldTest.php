<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Fields\Email;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Aura\Base\Resources\Post;
use Livewire\Livewire;

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

class EmailFieldModel extends Resource
{
    public static $singularName = 'Email Model';

    public static ?string $slug = 'email-model';

    public static string $type = 'EmailModel';

    public static function getFields()
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

test('check Email Fields', function () {
    $slug = new Email;

    $fields = collect($slug->getFields());

    expect($fields->firstWhere('slug', 'placeholder'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'prefix'))->toBeNull();
    expect($fields->firstWhere('slug', 'suffix'))->toBeNull();
});

test('Email Field', function () {
    $model = new EmailFieldModel;

    $component = Livewire::test(Create::class, ['slug' => 'Post'])
        ->call('setModel', $model)
        ->assertSee('Create Email Model')
        ->assertSee('Email for Test')
        ->assertSeeHtml('type="email"')
        ->call('save')
        ->assertHasErrors(['form.fields.email'])
        ->set('form.fields.email', 'hello')
        ->call('save')
        ->assertHasErrors(['form.fields.email'])

        ->set('form.fields.email', 'example@example.com ') // should trim
        ->call('save')
        ->assertHasErrors(['form.fields.email'])

        ->set('form.fields.email', 'example@example.com')
        ->call('save')
        ->assertHasNoErrors(['form.fields.email']);

    // assert in db has post with type DateModel
    $this->assertDatabaseHas('posts', ['type' => 'EmailModel']);

    $model = EmailFieldModel::first();

    expect($model->fields['email'])->toBe('example@example.com');
    expect($model->email)->toBe('example@example.com');
});

test('Email Field - Placeholder', function () {
    $field = [
        'name' => 'Text for Test',
        'type' => 'Aura\\Base\\Fields\\Email',
        'validation' => '',
        'conditional_logic' => [],
        'placeholder' => 'Deine Email',
        'slug' => 'text',
    ];

    $fieldClass = app($field['type']);
    $field['field'] = $fieldClass;

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
        ['component' => $fieldClass->component, 'field' => $field, 'form' => []]
    );

    expect((string) $view)->toContain('placeholder="Deine Email"');

});

test('Email Field - Name rendered', function () {
    $field = [
        'name' => 'Text for Test',
        'type' => 'Aura\\Base\\Fields\\Email',
        'validation' => '',
        'conditional_logic' => [],
        'slug' => 'text',
    ];

    $fieldClass = app($field['type']);
    $field['field'] = $fieldClass;

    $view = $this->withViewErrors([])->blade(
        '<x-dynamic-component :component="$component" :field="$field" :form="$form" />',
        ['component' => $fieldClass->component, 'field' => $field, 'form' => []]
    );

    expect((string) $view)->toContain('>Text for Test</label>');
    expect((string) $view)->toContain('type="email"');
});
