<?php

namespace Tests\Feature\Livewire;

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resource;
use Aura\Base\Resources\Post;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

// Refresh Database on every test
// uses(RefreshDatabase::class);

// Before each test, create a Superadmin and login
beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());

    Aura::fake();
    Aura::setModel(new PasswordFieldModel);
});

// Create Resource for this test
class PasswordFieldModel extends Resource
{
    public static $singularName = 'Password Model';

    public static ?string $slug = 'password-model';

    public static string $type = 'PasswordModel';

    protected $hidden = ['password'];

    public static function getFields()
    {
        return [
            [
                'name' => 'Password for Test',
                'type' => 'Aura\\Base\\Fields\\Password',
                'validation' => 'nullable|min:8',
                'conditional_logic' => [],
                'slug' => 'password',
            ],
        ];
    }
}

test('Password Field Test', function () {
    $model = new PasswordFieldModel;

    $component = Livewire::test(Create::class, ['slug' => 'password-model'])
        ->call('setModel', $model)
        ->assertSee('Create Password Model')
        ->assertSee('Password for Test')
        ->assertSeeHtml('type="password"')
        ->call('save')
        ->assertHasNoErrors(['form.fields.number']);

    // assert in db has post with type DateModel
    $this->assertDatabaseHas('posts', ['type' => 'PasswordModel']);

    $model = PasswordFieldModel::first();

    // Assert that $model->fields['number'] is null
    $this->assertArrayNotHasKey('password', $model->fields);

    ray()->clearScreen();

    $component->set('form.fields.password', '123456')
        ->call('save')
        ->assertHasErrors(['form.fields.password'])
        ->set('form.fields.password', '123456789')
        ->call('save')
        ->assertHasNoErrors(['form.fields.password']);

    // get the datemodel from db
    $model = PasswordFieldModel::orderBy('id', 'desc')->first();

    // ray($model->password, $model->fields);

    // $this->assertEquals($model->fields['password'], null);
    $this->assertTrue(Hash::check('123456789', $model->fields['password']));

    $this->assertTrue(Hash::check('123456789', $model->password));
});

test('password field gets not overwritten if saved as null', function () {
    $model = new PasswordFieldModel;

    $component = Livewire::test(Create::class, ['slug' => 'password-model'])
        ->call('setModel', $model)
        ->set('form.fields.password', '123456789')
        ->call('save')
        ->assertHasNoErrors(['form.fields.password']);

    // assert in db has post with type DateModel
    $this->assertDatabaseHas('posts', ['type' => 'PasswordModel']);

    $post = PasswordFieldModel::first();

    // $this->assertEquals($post->fields['password'], '123456789');
    // $this->assertEquals($post->fields['password'], null);
    $this->assertTrue(Hash::check('123456789', $post->password));

    $this->assertTrue(Hash::check('123456789', $post->password));

    // dump($post->password);

    $model = new PasswordFieldModel;
    $slug = 'PasswordModel';

    Aura::fake();
    Aura::setModel($model);

    // Assert that the mock works
    $this->assertInstanceOf(PasswordFieldModel::class, Aura::findResourceBySlug($slug)->find($post->id));

    // If we call the edit view, the password field should be empty
    $component = Livewire::test(Edit::class, ['slug' => $slug, 'id' => $post->id])
        ->assertSee('Edit Password Model')
        ->assertSee('Password for Test')
        ->assertSeeHtml('type="password"')
        // assert that the password field is empty
        ->assertSet('form.fields.password', null)
        ->call('save');

    $post = PasswordFieldModel::first();

    dump($post->fields['password']);

    // Assert Password is still 123456789
    $this->assertTrue(Hash::check('123456789', $post->fields['password']));

    $this->assertTrue(Hash::check('123456789', $post->password));
});

test('password field gets not overwritten if saved as empty string', function () {
    $model = new PasswordFieldModel;

    $component = Livewire::test(Create::class, ['slug' => 'password-model'])
        ->call('setModel', $model)
        ->set('form.fields.password', '123456789')
        ->call('save')
        ->assertHasNoErrors(['form.fields.number']);

    // assert in db has post with type DateModel
    $this->assertDatabaseHas('posts', ['type' => 'PasswordModel']);

    $post = PasswordFieldModel::first();

    $this->assertTrue(Hash::check('123456789', $post->fields['password']));
    $this->assertTrue(Hash::check('123456789', $post->password));

    $model = new PasswordFieldModel;
    $slug = 'PasswordModel';

    Aura::fake();
    Aura::setModel($model);

    // Assert that the mock works
    $this->assertInstanceOf(PasswordFieldModel::class, Aura::findResourceBySlug($slug)->find($post->id));

    // If we call the edit view, the password field should be empty
    $component = Livewire::test(Edit::class, ['slug' => $slug, 'id' => $post->id])
        ->assertSee('Edit Password Model')
        ->assertSee('Password for Test')
        ->assertSeeHtml('type="password"')
        // assert that the password field is empty
        ->assertSet('form.fields.password', '')
        ->call('save')
        ->assertHasNoErrors(['form.fields.password']);

    $post = PasswordFieldModel::first();

    // Assert Password is still 123456789
    $this->assertTrue(Hash::check('123456789', $post->fields['password']));
    $this->assertTrue(Hash::check('123456789', $post->password));
});

test('user password field gets not overwritten if saved as empty string', function () {

    $user = User::factory()->create([
        'password' => Hash::make('password'),
    ]);

    $model = $user;

    Aura::fake();
    Aura::setModel($model);

    // If we call the edit view, the password field should be empty
    $component = Livewire::test(Edit::class, ['slug' => 'User', 'id' => $model->id])
        ->assertSee('Password')
        ->assertSeeHtml('type="password"')
        // assert that the password field is empty
        ->assertSet('form.fields.password', '')
        ->call('save')
        ->assertHasNoErrors(['form.fields.password']);

    $user = $user->refresh();

    // Assert Password is still 123456789
    $this->assertTrue(Hash::check('password', $user->password));
});
