<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Models\User;
use Aura\Base\Resource;
use Aura\Base\Resources\Team;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Livewire\Livewire;

// Refresh Database on every test
uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

// Create Resource for this test
class SlugFieldModel extends Resource
{
    public static $singularName = 'Slug Model';

    public static ?string $slug = 'slug-model';

    public static string $type = 'SlugModel';

    public static function getFields()
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

test('Slug Field Test', function () {
    $model = new SlugFieldModel();

    $component = Livewire::test(Create::class, ['slug' => 'Post'])
        ->call('setModel', $model)
        ->assertSee('Create Slug Model')
        ->assertSee('Slug for Test')
        ->assertSeeHtml('type="text"')
        ->assertSee('Slug')
        ->call('save')
        ->assertHasErrors(['form.fields.slug']);

    // Test custom slug
    $component
        ->set('form.fields.text', 'Custom Title')
        ->set('form.fields.slug', 'custom-title')
        ->call('save')
        ->assertHasNoErrors(['form.fields.slug']);

    // Assert that the model was saved to the database with the custom slug
    $this->assertDatabaseHas('posts', ['type' => 'SlugModel', 'slug' => 'custom-title']);

    // Get the saved model
    $post = SlugFieldModel::first();

    // Assert that $post->fields['slug'] is 'custom-slug'
    $this->assertEquals('custom-title', $post->fields['slug']);

    Aura::fake();
    Aura::setModel($model);

    $this->assertInstanceOf(SlugFieldModel::class, Aura::findResourceBySlug('SlugModel')->find($post->id));

    // If we call the edit view, the password field should be empty
    $component = Livewire::test(Edit::class, ['slug' => 'SlugModel', 'id' => $post->id])
        ->set('form.fields.slug', 'toggle-slug')
        ->call('save')
        ->assertHasNoErrors(['form.fields.slug']);

    // Get the saved model
    $post = $post->refresh();

    // Assert that $model->fields['slug'] is 'toggle-slug'
    $this->assertEquals('toggle-slug', $post->slug);

    // Test validation
    $component->set('form.fields.slug', 'invalid slug')
        ->call('save')
        ->assertHasErrors(['form.fields.slug']);

    // Assert that the model was not saved to the database
    $this->assertDatabaseMissing('posts', ['type' => 'SlugModel', 'fields' => json_encode(['slug' => 'invalid slug'])]);
});


test('Slug Field - Without Custom Checkbox', function () {
});

test('Slug Field - only disabled input - true', function () {
});

test('Slug Field - disabled input - false', function () {
});

test('Slug Field - custom - false ', function () {
});

test('Slug Field - custom - true', function () {
});
