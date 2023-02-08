<?php

namespace Tests\Feature\Livewire;

use Eminiarts\Aura;
use Eminiarts\Aura\Resources\Post;
use Eminiarts\Aura\Resources\Team;
use Eminiarts\Aura\Http\Livewire\Post\Create;
use Eminiarts\Aura\Http\Livewire\Post\Edit;
use Eminiarts\Aura\Models\User;
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
class SlugFieldModel extends Post
{
    public static $singularName = 'Slug Model';

    public static ?string $slug = 'slug-model';

    public static string $type = 'SlugModel';

    public static function getFields()
    {
        return [
            [
                'name' => 'Text',
                'type' => 'Eminiarts\\Aura\\Fields\\Text',
                'validation' => 'required',
                'conditional_logic' => [],
                'slug' => 'text',
            ],
            [
                'name' => 'Slug for Test',
                'type' => 'Eminiarts\\Aura\\Fields\\Slug',
                'validation' => 'required|alpha_dash',
                'conditional_logic' => [],
                'slug' => 'slug',
                'based_on' => 'text',
            ],
        ];
    }
}

test('Slug Field Test', function () {
    $model = new SlugFieldModel();

    $component = Livewire::test(Create::class, ['slug' => 'Post'])
        ->call('setModel', $model)
        ->assertSee('Create Slug Model')
        ->assertSee('Slug for Test')
        ->assertSeeHtml('type="text"')
        ->assertSee('Slug')
        ->call('save')
        ->assertHasErrors(['post.fields.slug']);

    // Test custom slug
    $component
        ->set('post.fields.text', 'Custom Title')
        ->set('post.fields.slug', 'custom-title')
        ->call('save')
        ->assertHasNoErrors(['post.fields.slug']);

    // Assert that the model was saved to the database with the custom slug
    $this->assertDatabaseHas('posts', ['type' => 'SlugModel', 'slug' => 'custom-title']);

    // Get the saved model
    $post = SlugFieldModel::first();

    // Assert that $post->fields['slug'] is 'custom-slug'
    $this->assertEquals('custom-title', $post->fields['slug']);

    // Mock the findResourceBySlug method
    $this->mock(Aura::class)->shouldReceive('findResourceBySlug')->with('SlugModel')->andReturn(SlugFieldModel::query());

    // Assert that the mock works
    $this->assertInstanceOf(SlugFieldModel::class, app(Aura::class)->findResourceBySlug('SlugModel')->find($post->id));

    // If we call the edit view, the password field should be empty
    $component = Livewire::test(Edit::class, ['slug' => 'SlugModel', 'id' => $post->id])
        ->set('post.fields.slug', 'toggle-slug')
        ->call('save')
        ->assertHasNoErrors(['post.fields.slug']);

    // Get the saved model
    $post = $post->refresh();

    // Assert that $model->fields['slug'] is 'toggle-slug'
    $this->assertEquals('toggle-slug', $post->slug);

    // Test validation
    $component->set('post.fields.slug', 'invalid slug')
        ->call('save')
        ->assertHasErrors(['post.fields.slug']);

    // Assert that the model was not saved to the database
    $this->assertDatabaseMissing('posts', ['type' => 'SlugModel', 'fields' => json_encode(['slug' => 'invalid slug'])]);
});
