<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Password;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resource;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

class PasswordFieldModel extends Resource
{
    public static $singularName = 'Password Model';

    public static ?string $slug = 'password-model';

    public static string $type = 'PasswordModel';

    public static function getFields(): array
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

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
    Aura::fake();
    Aura::setModel(new PasswordFieldModel);
});

describe('Password Field Configuration', function () {
    test('has correct option group', function () {
        $passwordField = new Password;

        expect($passwordField->optionGroup)->toBe('Input Fields');
    });

    test('has correct edit and view properties', function () {
        $passwordField = new Password;

        expect($passwordField->edit)->toBe('aura::fields.password')
            ->and($passwordField->view)->toBe('aura::fields.view-value');
    });

    test('edit method returns edit property', function () {
        $passwordField = new Password;

        expect($passwordField->edit())->toBe('aura::fields.password');
    });

    test('getFields returns parent fields only', function () {
        $passwordField = new Password;
        $fields = $passwordField->getFields();

        // Password field adds no additional configuration fields
        $parentField = new \Aura\Base\Fields\Text;
        $parentFields = count((new \ReflectionClass(\Aura\Base\Fields\Field::class))->getMethod('getFields')->invoke($passwordField));

        expect(count($fields))->toBe($parentFields);
    });
});

describe('Password Field Rendering', function () {
    test('renders in create form', function () {
        Livewire::test(Create::class, ['slug' => 'password-model'])
            ->assertSee('Create Password Model')
            ->assertSee('Password for Test')
            ->assertSeeHtml('type="password"');
    });
});

describe('Password Field Validation', function () {
    test('allows null value', function () {
        Livewire::test(Create::class, ['slug' => 'password-model'])
            ->call('save')
            ->assertHasNoErrors();

        $this->assertDatabaseHas('posts', ['type' => 'PasswordModel']);
    });

    test('validates minimum length', function () {
        Livewire::test(Create::class, ['slug' => 'password-model'])
            ->set('form.fields.password', '123456')
            ->call('save')
            ->assertHasErrors(['form.fields.password']);
    });

    test('accepts valid password', function () {
        Livewire::test(Create::class, ['slug' => 'password-model'])
            ->set('form.fields.password', '123456789')
            ->call('save')
            ->assertHasNoErrors(['form.fields.password']);
    });
});

describe('Password Field in Livewire', function () {
    test('hashes password when saving', function () {
        Livewire::test(Create::class, ['slug' => 'password-model'])
            ->set('form.fields.password', '123456789')
            ->call('save')
            ->assertHasNoErrors();

        $model = PasswordFieldModel::orderBy('id', 'desc')->first();

        expect(Hash::check('123456789', $model->fields['password']))->toBeTrue()
            ->and(Hash::check('123456789', $model->password))->toBeTrue();
    });

    test('password is empty or null when not provided on create', function () {
        Livewire::test(Create::class, ['slug' => 'password-model'])
            ->call('save')
            ->assertHasNoErrors();

        $model = PasswordFieldModel::first();

        // Password should either not exist or be null/empty
        expect($model->fields['password'] ?? null)->toBeEmpty();
    });
});

describe('Password Field Edit Behavior', function () {
    test('password field is empty when editing', function () {
        // Create a model with password
        Livewire::test(Create::class, ['slug' => 'password-model'])
            ->set('form.fields.password', '123456789')
            ->call('save')
            ->assertHasNoErrors();

        $post = PasswordFieldModel::first();

        Aura::fake();
        Aura::setModel(new PasswordFieldModel);

        // Edit should show empty password field
        Livewire::test(Edit::class, ['slug' => 'PasswordModel', 'id' => $post->id])
            ->assertSee('Edit Password Model')
            ->assertSee('Password for Test')
            ->assertSeeHtml('type="password"')
            ->assertSet('form.fields.password', null);
    });

    test('password is not overwritten when saved as null', function () {
        // Create a model with password
        Livewire::test(Create::class, ['slug' => 'password-model'])
            ->set('form.fields.password', '123456789')
            ->call('save');

        $post = PasswordFieldModel::first();
        expect(Hash::check('123456789', $post->password))->toBeTrue();

        Aura::fake();
        Aura::setModel(new PasswordFieldModel);

        // Edit and save without changing password
        Livewire::test(Edit::class, ['slug' => 'PasswordModel', 'id' => $post->id])
            ->assertSet('form.fields.password', null)
            ->call('save');

        $post = PasswordFieldModel::first();

        // Password should still be the original
        expect(Hash::check('123456789', $post->fields['password']))->toBeTrue()
            ->and(Hash::check('123456789', $post->password))->toBeTrue();
    });

    test('password is not overwritten when saved as empty string', function () {
        // Create a model with password
        Livewire::test(Create::class, ['slug' => 'password-model'])
            ->set('form.fields.password', '123456789')
            ->call('save');

        $post = PasswordFieldModel::first();
        expect(Hash::check('123456789', $post->fields['password']))->toBeTrue();

        Aura::fake();
        Aura::setModel(new PasswordFieldModel);

        // Edit and save with empty string
        Livewire::test(Edit::class, ['slug' => 'PasswordModel', 'id' => $post->id])
            ->assertSet('form.fields.password', '')
            ->call('save')
            ->assertHasNoErrors(['form.fields.password']);

        $post = PasswordFieldModel::first();

        // Password should still be the original
        expect(Hash::check('123456789', $post->fields['password']))->toBeTrue()
            ->and(Hash::check('123456789', $post->password))->toBeTrue();
    });
});

describe('User Password Field Edit', function () {
    test('user password is not overwritten when saved empty', function () {
        $model = $this->user;

        // Update password to a known value
        $model->update([
            'password' => Hash::make('password'),
        ]);

        Aura::fake();
        Aura::setModel($model);

        // Edit user and save with empty password
        Livewire::test(Edit::class, ['slug' => 'user', 'id' => $model->id])
            ->assertSuccessful()
            ->assertSee('Password')
            ->assertSeeHtml('type="password"')
            ->assertSet('form.fields.password', '')
            ->call('save')
            ->assertHasNoErrors(['form.fields.password']);

        $model = $model->fresh();

        // Password should still be 'password'
        expect(Hash::check('password', $model->password))->toBeTrue();
    });
});

describe('Password Field Value Handling', function () {
    test('set method hashes password when not empty', function () {
        $passwordField = new Password;
        $value = 'test_password';

        $result = $passwordField->set(null, [], $value);

        expect(Hash::check('test_password', $result))->toBeTrue();
    });

    test('set method returns null for empty value', function () {
        $passwordField = new Password;
        $value = '';

        $result = $passwordField->set(null, [], $value);

        expect($result)->toBeNull();
    });

    test('set method does not double-hash already hashed value', function () {
        $passwordField = new Password;
        $hashed = Hash::make('password');
        $value = $hashed;

        $result = $passwordField->set(null, [], $value);

        expect($result)->toBe($hashed);
    });

    test('shouldSkip returns true after empty value set', function () {
        $passwordField = new Password;
        $value = '';

        $passwordField->set(null, [], $value);

        expect($passwordField->shouldSkip(null, []))->toBeTrue();
    });

    test('shouldSkip returns false after valid value set', function () {
        $passwordField = new Password;
        $value = 'valid_password';

        $passwordField->set(null, [], $value);

        expect($passwordField->shouldSkip(null, []))->toBeFalse();
    });

    test('shouldSkip resets after being called', function () {
        $passwordField = new Password;
        $value = '';

        $passwordField->set(null, [], $value);
        $passwordField->shouldSkip(null, []); // First call returns true

        expect($passwordField->shouldSkip(null, []))->toBeFalse(); // Second call returns false
    });
});
