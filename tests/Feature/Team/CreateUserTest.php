<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Livewire\Resource\Edit;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Hash;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('User Creation', function () {
    it('can create a new user with valid data', function () {
        $initialCount = User::count();

        $this->withoutExceptionHandling();

        $role = Role::first();

        livewire(Create::class, ['slug' => 'user'])
            ->set('form.fields.name', 'Test User')
            ->set('form.fields.email', 'test@example.com')
            ->set('form.fields.current_team_id', $this->user->currentTeam->id)
            ->set('form.fields.password', 'Password123!XX')
            ->set('form.fields.password_confirmation', 'Password123!XX')
            ->set('form.fields.roles', [$role->id])
            ->call('save')
            ->assertHasNoErrors();

        expect(User::count())->toBe($initialCount + 1);

        $user = User::where('email', 'test@example.com')->first();

        expect($user->name)->toBe('Test User');
        expect($user->email)->toBe('test@example.com');
        expect(Hash::check('Password123!XX', $user->password))->toBeTrue();
        expect($user->roles->pluck('id')->toArray())->toContain($role->id);
    });

    it('validates password requirements', function () {
        $role = Role::first();

        livewire(Create::class, ['slug' => 'user'])
            ->set('form.fields.name', 'Test User')
            ->set('form.fields.email', 'test@example.com')
            ->set('form.fields.current_team_id', $this->user->currentTeam->id)
            ->set('form.fields.password', 'password')
            ->set('form.fields.password_confirmation', 'password')
            ->set('form.fields.roles', [$role->id])
            ->call('save')
            ->assertHasErrors(['form.fields.password']);
    });

    it('assigns role to created user', function () {
        $role = Role::first();

        livewire(Create::class, ['slug' => 'user'])
            ->set('form.fields.name', 'Role Test User')
            ->set('form.fields.email', 'roletest@example.com')
            ->set('form.fields.current_team_id', $this->user->currentTeam->id)
            ->set('form.fields.password', 'Password123!XX')
            ->set('form.fields.password_confirmation', 'Password123!XX')
            ->set('form.fields.roles', [$role->id])
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'roletest@example.com')->first();

        expect($user->roles)->toHaveCount(1);
        expect($user->roles->first()->id)->toBe($role->id);
    });
});

describe('User Creation Validation', function () {
    it('cannot create user with invalid email', function () {
        $role = Role::first();

        livewire(Create::class, ['slug' => 'user'])
            ->set('form.fields.name', 'Test User')
            ->set('form.fields.email', 'invalid-email')
            ->set('form.fields.password', 'Password123!XX')
            ->set('form.fields.password_confirmation', 'Password123!XX')
            ->set('form.fields.roles', [$role->id])
            ->call('save')
            ->assertHasErrors(['form.fields.email']);
    });

    it('requires name field', function () {
        $role = Role::first();

        livewire(Create::class, ['slug' => 'user'])
            ->set('form.fields.name', '')
            ->set('form.fields.email', 'test@example.com')
            ->set('form.fields.password', 'Password123!XX')
            ->set('form.fields.password_confirmation', 'Password123!XX')
            ->set('form.fields.roles', [$role->id])
            ->call('save')
            ->assertHasErrors(['form.fields.name']);
    });

    it('requires email field', function () {
        $role = Role::first();

        livewire(Create::class, ['slug' => 'user'])
            ->set('form.fields.name', 'Test User')
            ->set('form.fields.email', '')
            ->set('form.fields.password', 'Password123!XX')
            ->set('form.fields.password_confirmation', 'Password123!XX')
            ->set('form.fields.roles', [$role->id])
            ->call('save')
            ->assertHasErrors(['form.fields.email']);
    });
});

describe('User Deletion', function () {
    it('can delete a user', function () {
        $initialCount = User::withoutGlobalScopes()->count();

        $user = User::factory()->create([
            'name' => 'User to Delete',
            'email' => 'delete@example.com',
            'password' => Hash::make('password'),
        ]);

        if (config('aura.teams') && $this->user->currentTeam) {
            $role = Role::where('team_id', $this->user->currentTeam->id)->first()
                ?? Role::factory()->create(['team_id' => $this->user->currentTeam->id]);

            $user->teams()->attach($this->user->currentTeam->id, ['role_id' => $role->id]);
            $user->update(['current_team_id' => $this->user->currentTeam->id]);
        }

        expect(User::withoutGlobalScopes()->count())->toBe($initialCount + 1);

        $user->delete();

        expect(User::withoutGlobalScopes()->count())->toBe($initialCount);
        expect(User::withoutGlobalScopes()->where('email', 'delete@example.com')->first())->toBeNull();
    });
});

describe('User Editing', function () {
    it('can edit user without changing password', function () {
        $user = User::factory()->create([
            'name' => 'Original Name',
            'email' => 'original@example.com',
            'password' => Hash::make('OriginalPass123!'),
        ]);

        if (config('aura.teams') && $this->user->currentTeam) {
            $role = Role::where('team_id', $this->user->currentTeam->id)->first()
                ?? Role::factory()->create(['team_id' => $this->user->currentTeam->id]);

            $user->teams()->attach($this->user->currentTeam->id, ['role_id' => $role->id]);
            $user->update(['current_team_id' => $this->user->currentTeam->id]);
        }

        $originalPassword = $user->password;

        Aura::fake();
        Aura::setModel($user);

        $this->withoutExceptionHandling();

        livewire(Edit::class, ['slug' => 'user', 'id' => $user->id])
            ->set('form.fields.name', 'Updated Name')
            ->set('form.fields.email', 'updated@example.com')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        expect($user->name)->toBe('Updated Name');
        expect($user->email)->toBe('updated@example.com');
        expect($user->password)->toBe($originalPassword);
    });

    it('can change user password when explicitly set', function () {
        $user = $this->user;

        $user->update(['password' => Hash::make('CurrentPass123!')]);

        Aura::fake();
        Aura::setModel($user);

        livewire(Edit::class, ['slug' => 'user', 'id' => $user->id])
            ->set('form.fields.current_password', 'CurrentPass123!')
            ->set('form.fields.password', 'NewPass123!qerqw')
            ->set('form.fields.password_confirmation', 'NewPass123!qerqw')
            ->call('save')
            ->assertHasNoErrors();

        $user->refresh();

        expect(Hash::check('NewPass123!qerqw', $user->password))->toBeTrue();
        expect(Hash::check('CurrentPass123!', $user->password))->toBeFalse();
    });

});

describe('User Team Association', function () {
    it('associates new user with current team', function () {
        $role = Role::first();

        livewire(Create::class, ['slug' => 'user'])
            ->set('form.fields.name', 'Team User')
            ->set('form.fields.email', 'teamuser@example.com')
            ->set('form.fields.current_team_id', $this->user->currentTeam->id)
            ->set('form.fields.password', 'Password123!XX')
            ->set('form.fields.password_confirmation', 'Password123!XX')
            ->set('form.fields.roles', [$role->id])
            ->call('save')
            ->assertHasNoErrors();

        $user = User::where('email', 'teamuser@example.com')->first();

        expect($user->current_team_id)->toBe($this->user->currentTeam->id);
    });
});
