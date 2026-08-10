<?php

use Aura\Base\Preferences\PreferenceContext;
use Aura\Base\Preferences\PreferenceManager;
use Aura\Base\Preferences\PreferenceScope;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Auth\Access\AuthorizationException;

beforeEach(function () {
    config(['aura.teams' => false]);
    $this->artisan('migrate:fresh');
    (require __DIR__.'/../../database/migrations/create_aura_tables.php.stub')->up();
});

afterEach(function () {
    config(['aura.teams' => true]);
});

test('preferences work without team columns or ambient authentication', function () {
    $user = createSuperAdminWithoutTeam();
    $context = new PreferenceContext('cli', $user, resource: 'Article');
    $preferences = app(PreferenceManager::class);

    auth()->logout();

    $preferences->set('table.view', 'kanban', PreferenceScope::User, $context, $user);

    expect($preferences->get('table.view', $context))->toBe('kanban')
        ->and(Option::withoutGlobalScopes()->sole()->getAttribute('team_id'))->toBeNull();
});

test('teams-off everyone preferences still require an explicit global admin', function () {
    $admin = createSuperAdminWithoutTeam();
    $admin->forceFill(['global_admin' => true])->saveQuietly();
    $member = User::factory()->create();
    $context = new PreferenceContext('cli', $admin, resource: 'Article');
    $preferences = app(PreferenceManager::class);

    $preferences->set('table.view', 'kanban', PreferenceScope::Everyone, $context, $admin);

    expect($preferences->get('table.view', $context))->toBe('kanban')
        ->and(fn () => $preferences->set('table.view', 'list', PreferenceScope::Everyone, $context, $member))
        ->toThrow(AuthorizationException::class);
});

test('teams-off team reads fall back while set and reset reject before storage', function () {
    $admin = createSuperAdminWithoutTeam();
    $admin->forceFill(['global_admin' => true])->saveQuietly();
    $team = new Team;
    $team->forceFill(['id' => 42, 'user_id' => $admin->id, 'name' => 'Unavailable']);
    $context = new PreferenceContext('cli', $admin, $team, 'Article');
    $preferences = app(PreferenceManager::class);

    expect($preferences->get('table.view', $context))->toBe('list')
        ->and(fn () => $preferences->set('table.view', 'kanban', PreferenceScope::Team, $context, $admin))
        ->toThrow(InvalidArgumentException::class, 'disabled')
        ->and(fn () => $preferences->reset('table.view', PreferenceScope::Team, $context, $admin))
        ->toThrow(InvalidArgumentException::class, 'disabled')
        ->and(Option::withoutGlobalScopes()->count())->toBe(0);
});
