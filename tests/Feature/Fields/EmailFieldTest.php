<?php

namespace Tests\Feature\Livewire;

use Eminiarts\Aura\Http\Livewire\Post\Create;
use Eminiarts\Aura\Models\User;
use Eminiarts\Aura\Resources\Post;
use Eminiarts\Aura\Resources\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Refresh Database on every test
uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    // Create User
    $this->actingAs($this->user = User::factory()->create());

    // Create Team and assign to user
    createSuperAdmin();

    // Refresh User
    $this->user = $this->user->refresh();

    // Login
    $this->actingAs($this->user);
});

// Create Resource for this test
class EmailFieldModel extends Post
{
    public static $singularName = 'Email Model';

    public static ?string $slug = 'email-model';

    public static string $type = 'EmailModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Email for Test',
                'type' => 'Eminiarts\\Aura\\Fields\\Email',
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
        ->assertHasErrors(['post.fields.email'])
        ->set('post.fields.email', 'hello')
        ->call('save')
        ->assertHasErrors(['post.fields.email'])

        ->set('post.fields.email', 'example@example.com ') // should trim
        ->call('save')
        ->assertHasErrors(['post.fields.email'])

        ->set('post.fields.email', 'example@example.com')
        ->call('save')
        ->assertHasNoErrors(['post.fields.email']);

    // assert in db has post with type DateModel
    $this->assertDatabaseHas('posts', ['type' => 'EmailModel']);

    $model = EmailFieldModel::first();

    expect($model->fields['email'])->toBe('example@example.com');
    expect($model->email)->toBe('example@example.com');
});

test('Email Field - Placeholder', function () {
});
