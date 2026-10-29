<?php

use Aura\Base\Livewire\Profile;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\TeamInvitation;
use Aura\Base\Resources\User;
use Aura\Base\Support\EnvironmentFile;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\URL;
use Illuminate\Validation\ValidationException;

use function Pest\Livewire\livewire;

test('the config gate trims addresses and ignores case and the legacy database flag', function () {
    $listed = User::factory()->create(['email' => 'OPS@example.com']);
    $unlisted = User::factory()->create();
    expect(Schema::hasColumn('users', 'global_admin'))->toBeFalse();
    Schema::table('users', fn (Blueprint $table) => $table->boolean('global_admin')->default(false));
    DB::table('users')->where('id', $unlisted->id)->update(['global_admin' => true]);
    config(['aura.global_admin.emails' => ['  ops@EXAMPLE.com  ']]);

    expect(Gate::forUser($listed)->allows('AuraGlobalAdmin'))->toBeTrue()
        ->and(Gate::forUser($unlisted->fresh())->allows('AuraGlobalAdmin'))->toBeFalse()
        ->and(collect((new User)->getFields())->contains('slug', 'global_admin'))->toBeFalse();
});

test('public registration cannot claim a configured Global Admin email', function () {
    config(['aura.global_admin.emails' => ['ops@example.com'], 'aura.auth.registration' => true]);
    auth()->logout();
    $this->post(route('aura.register.post'), [
        'name' => 'Attempt', 'team' => 'Attempt Team', 'email' => 'OPS@example.com',
        'password' => 'password', 'password_confirmation' => 'password',
    ])->assertSessionHasErrors('email');
    expect(User::withoutGlobalScopes()->whereRaw('lower(email) = ?', ['ops@example.com'])->exists())->toBeFalse();
});

test('invitation registration cannot claim a configured Global Admin email', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team invitations require teams.');
    }
    $actor = createSuperAdmin();
    config(['aura.global_admin.emails' => ['ops@example.com'], 'aura.auth.user_invitations' => true]);
    $invitation = TeamInvitation::create([
        'team_id' => $actor->current_team_id, 'email' => 'OPS@example.com', 'role' => Role::firstOrCreateCatalogRole('user')->id,
    ]);
    $url = URL::signedRoute('aura.invitation.register', [$actor->currentTeam, $invitation]);
    auth()->logout();
    $this->post($url, ['name' => 'Attempt', 'password' => 'password', 'password_confirmation' => 'password'])
        ->assertSessionHasErrors('email');
    expect(User::withoutGlobalScopes()->whereRaw('lower(email) = ?', ['ops@example.com'])->exists())->toBeFalse()
        ->and(TeamInvitation::withoutGlobalScopes()->whereKey($invitation->id)->exists())->toBeTrue();
});

test('profile email changes cannot take over a configured Global Admin address', function () {
    $this->actingAs($actor = createSuperAdmin());
    $original = $actor->email;
    config(['aura.global_admin.emails' => ['ops@example.com']]);
    livewire(Profile::class)->set('form.fields.email', 'OPS@example.com')->call('save')->assertHasErrors('email');
    expect($actor->fresh()->email)->toBe($original);
});

test('a self edit cannot authorize itself by mutating the authenticated model', function () {
    $this->actingAs($actor = createSuperAdmin());
    config(['aura.global_admin.emails' => ['ops@example.com']]);
    $actor->email = 'ops@example.com';
    expect(fn () => $actor->save())->toThrow(ValidationException::class);
    expect($actor->fresh()->email)->not->toBe('ops@example.com');
});

test('an existing Global Admin may create or change a reserved email', function () {
    $this->actingAs($actor = createGlobalAdmin());
    config(['aura.global_admin.emails' => [$actor->email, 'ops@example.com', 'another@example.com']]);
    $created = User::factory()->create(['email' => 'ops@example.com']);
    $created->update(['email' => 'another@example.com']);
    expect($created->fresh()->isAuraGlobalAdmin())->toBeTrue();
});

test('aura user bootstraps a reserved address and appends the env list without duplicates', function () {
    $directory = storage_path('global-admin-env');
    if (! is_dir($directory)) {
        mkdir($directory, 0755, true);
    }
    app()->useEnvironmentPath($directory);
    file_put_contents(app()->environmentFilePath(), "OTHER=unchanged\nAURA_GLOBAL_ADMINS=\"existing@example.com, OPS@example.com\"\n");
    config(['aura.global_admin.emails' => ['existing@example.com', 'ops@example.com']]);
    auth()->logout();
    $this->artisan('aura:user', [
        '--name' => 'Operator', '--email' => 'ops@example.com', '--password' => 'password',
        '--global-admin' => true, '--no-interaction' => true,
    ])->expectsOutput('Production must set AURA_GLOBAL_ADMINS to include ops@example.com.')->assertSuccessful();
    $user = User::withoutGlobalScopes()->where('email', 'ops@example.com')->firstOrFail();
    expect($user->isAuraGlobalAdmin())->toBeTrue()->and(Hash::check('password', $user->password))->toBeTrue();
    EnvironmentFile::addGlobalAdmin('OPS@example.com');
    expect(file_get_contents(app()->environmentFilePath()))
        ->toBe("OTHER=unchanged\nAURA_GLOBAL_ADMINS=existing@example.com,ops@example.com\n");
});
