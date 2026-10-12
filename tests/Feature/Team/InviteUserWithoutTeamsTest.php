<?php

use Aura\Base\Livewire\InviteUser;
use Aura\Base\Mail\UserInvitation as UserInvitationMail;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Aura\Base\Resources\UserInvitation;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Livewire\Livewire;

beforeEach(function () {
    if (Schema::hasColumn('user_role', 'team_id')) {
        $this->markTestSkipped('Run teams-off invitations with phpunit-without-teams.xml.');
    }

    config(['aura.teams' => false, 'aura.auth.user_invitations' => true]);

    Mail::fake();
    $this->actingAs($this->user = createSuperAdminWithoutTeam());
    $this->role = Role::factory()->create(['super_admin' => false]);
});

it('renders the invitation dialog with roles when teams are disabled', function () {
    Livewire::test(InviteUser::class)
        ->assertOk()
        ->assertSee($this->role->name);
});

it('creates and emails a teams-off invitation with a signed registration link', function () {
    Livewire::test(InviteUser::class)
        ->set('form.fields.email', 'invitee@example.test')
        ->set('form.fields.role', $this->role->id)
        ->call('save')
        ->assertHasNoErrors()
        ->assertDispatched('closeModal');

    $invitation = UserInvitation::sole();
    expect($invitation->email)->toBe('invitee@example.test')
        ->and((int) $invitation->role)->toBe($this->role->id)
        ->and(User::where('email', $invitation->email)->exists())->toBeFalse();

    Mail::assertSent(UserInvitationMail::class, function (UserInvitationMail $mail) use ($invitation) {
        $mail->assertTo($invitation->email);
        $mail->assertSeeInHtml('signature=');
        $mail->assertSeeInHtml('/user-invitations/'.$invitation->id);

        return true;
    });
});

it('requires permission to create users before inviting', function () {
    $this->actingAs(User::factory()->create());

    Livewire::test(InviteUser::class)->assertForbidden();
    expect(UserInvitation::count())->toBe(0);
    Mail::assertNothingSent();
});

it('validates missing and malformed invitation input', function () {
    Livewire::test(InviteUser::class)
        ->call('save')
        ->assertHasErrors(['form.fields.email', 'form.fields.role'])
        ->set('form.fields.email', ['invitee@example.test'])
        ->set('form.fields.role', $this->role->id)
        ->call('save')
        ->assertHasErrors('form.fields.email');

    Mail::assertNothingSent();
});

it('rejects nonexistent roles and admin role escalation', function () {
    $manager = User::factory()->create();
    $managerRole = Role::factory()->create([
        'super_admin' => false,
        'permissions' => ['create-user' => true],
    ]);
    $manager->roles()->attach($managerRole);
    $this->actingAs($manager);
    $adminRole = Role::where('slug', 'admin')->sole();

    Livewire::test(InviteUser::class)
        ->set('form.fields.email', 'invitee@example.test')
        ->set('form.fields.role', $adminRole->id)
        ->call('save')
        ->assertHasErrors('form.fields.role')
        ->set('form.fields.role', 999999)
        ->call('save')
        ->assertHasErrors('form.fields.role')
        ->set('form.fields.role', $this->role->id)
        ->call('save')
        ->assertHasNoErrors();

    expect(UserInvitation::count())->toBe(1);
});

it('rejects existing accounts and duplicate pending invitations case-insensitively', function () {
    User::factory()->create(['email' => 'existing@example.test']);

    Livewire::test(InviteUser::class)
        ->set('form.fields.role', $this->role->id)
        ->set('form.fields.email', 'EXISTING@example.test')
        ->call('save')
        ->assertHasErrors('form.fields.email')
        ->set('form.fields.email', 'invitee@example.test')
        ->call('save')
        ->assertHasNoErrors()
        ->set('form.fields.email', 'INVITEE@example.test')
        ->call('save')
        ->assertHasErrors('form.fields.email');

    expect(UserInvitation::count())->toBe(1);
    Mail::assertSent(UserInvitationMail::class, 1);
});

it('registers the invited account with exactly the carried role and consumes the invitation', function () {
    config(['aura.auth.registration' => false]);
    $invitation = UserInvitation::create(['email' => 'invitee@example.test', 'role' => $this->role->id]);
    $url = URL::temporarySignedRoute('aura.user-invitations.register', now()->addDay(), ['invitation' => $invitation]);
    $this->app['auth']->logout();

    $this->get($url)->assertOk()->assertSee($invitation->email)->assertDontSee('name="team"', false);
    $this->post($url, [
        'name' => 'Invited User',
        'email' => 'attacker@example.test',
        'role' => Role::where('slug', 'admin')->value('id'),
        'global_admin' => true,
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertRedirect(config('aura.auth.redirect'));

    $user = User::where('email', 'invitee@example.test')->sole();
    expect($user->roles->modelKeys())->toBe([$this->role->id])
        ->and($user->global_admin)->toBeFalse()
        ->and(Hash::check('password', $user->password))->toBeTrue()
        ->and(UserInvitation::find($invitation->id))->toBeNull();
    $this->assertAuthenticatedAs($user);

    $this->app['auth']->logout();
    $this->post($url, [])->assertNotFound();
});

it('rejects unsigned and expired invitation links', function (bool $expired) {
    $invitation = UserInvitation::create(['email' => 'invitee@example.test', 'role' => $this->role->id]);
    $url = $expired
        ? URL::temporarySignedRoute('aura.user-invitations.register', now()->subSecond(), ['invitation' => $invitation])
        : route('aura.user-invitations.register', ['invitation' => $invitation]);
    $this->app['auth']->logout();

    $this->get($url)->assertForbidden();
    $this->post($url, ['name' => 'Invited User', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertForbidden();
    expect(User::where('email', $invitation->email)->exists())->toBeFalse();
})->with([false, true]);

it('refuses registration if the invited email is already registered', function () {
    $invitation = UserInvitation::create(['email' => 'invitee@example.test', 'role' => $this->role->id]);
    User::factory()->create(['email' => 'INVITEE@example.test']);
    $url = URL::temporarySignedRoute('aura.user-invitations.register', now()->addDay(), ['invitation' => $invitation]);
    $this->app['auth']->logout();

    $this->post($url, ['name' => 'Invited User', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertSessionHasErrors('email');
    expect(UserInvitation::find($invitation->id))->not->toBeNull();
});

it('does not create an account if the invited role was deleted', function () {
    $invitation = UserInvitation::create(['email' => 'invitee@example.test', 'role' => $this->role->id]);
    $url = URL::temporarySignedRoute('aura.user-invitations.register', now()->addDay(), ['invitation' => $invitation]);
    $this->role->delete();
    $this->app['auth']->logout();

    $this->post($url, ['name' => 'Invited User', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertNotFound();
    expect(User::where('email', $invitation->email)->exists())->toBeFalse();
});

it('disables invitation creation and registration when invitations are disabled', function () {
    $invitation = UserInvitation::create(['email' => 'invitee@example.test', 'role' => $this->role->id]);
    $url = URL::temporarySignedRoute('aura.user-invitations.register', now()->addDay(), ['invitation' => $invitation]);
    config(['aura.auth.user_invitations' => false]);

    Livewire::test(InviteUser::class)->assertNotFound();
    $this->app['auth']->logout();
    $this->get($url)->assertNotFound();
    $this->post($url, [])->assertNotFound();
});

it('keeps team invitations hidden when teams are disabled', function () {
    expect(TeamInvitation::getShowInNavigation())->toBeFalse();
});

it('refuses teams-off invitation registration when teams are enabled', function () {
    $invitation = UserInvitation::create(['email' => 'invitee@example.test', 'role' => $this->role->id]);
    $url = URL::temporarySignedRoute('aura.user-invitations.register', now()->addDay(), ['invitation' => $invitation]);
    $this->app['auth']->logout();
    config(['aura.teams' => true]);

    $this->get($url)->assertNotFound();
    $this->post($url, [])->assertNotFound();
});
