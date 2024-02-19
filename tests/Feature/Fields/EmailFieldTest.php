<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Aura\Base\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Refresh Database on every test
uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// Create Resource for this test
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

test('Email Field', function () {
    // show all exceptions
    //$this->withoutExceptionHandling();

    $model = new EmailFieldModel();

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
})->todo();
