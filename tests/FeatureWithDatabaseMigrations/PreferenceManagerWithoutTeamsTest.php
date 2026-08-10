<?php

use Aura\Base\Preferences\PreferenceContext;
use Aura\Base\Preferences\PreferenceDefinition;
use Aura\Base\Preferences\PreferenceManager;
use Aura\Base\Preferences\PreferenceRegistry;
use Aura\Base\Preferences\PreferenceScope;
use Aura\Base\Preferences\PreferenceValueType;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    config(['aura.teams' => false]);
    $this->artisan('migrate:fresh');
    (require __DIR__.'/../../database/migrations/create_aura_tables.php.stub')->up();
});

afterEach(function () {
    config(['aura.teams' => true]);
});

test('teams-off reads ignore authentication while writes require it', function () {
    $user = createSuperAdminWithoutTeam();
    $context = new PreferenceContext('cli', $user, resource: 'Article');
    $preferences = app(PreferenceManager::class);

    auth()->logout();

    DB::enableQueryLog();
    DB::flushQueryLog();

    expect(fn () => $preferences->set('table.view', 'kanban', PreferenceScope::User, $context, $user))
        ->toThrow(AuthorizationException::class);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($queries)->toBeEmpty()
        ->and($preferences->get('table.view', $context))->toBe('list');

    Auth::login($user);
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

    Auth::login($member);

    expect($preferences->get('table.view', $context))->toBe('kanban')
        ->and(fn () => $preferences->set('table.view', 'list', PreferenceScope::Everyone, $context, $member))
        ->toThrow(AuthorizationException::class);
});

test('teams-off float preferences preserve exact JSON for user and everyone scopes', function () {
    app(PreferenceRegistry::class)->register(new PreferenceDefinition(
        key: 'test.float-without-teams',
        type: PreferenceValueType::Float,
        default: 2.5,
        scopes: [PreferenceScope::User, PreferenceScope::Everyone],
    ));
    $admin = createSuperAdminWithoutTeam();
    $admin->forceFill(['global_admin' => true])->saveQuietly();
    $context = new PreferenceContext('cli', $admin);
    $preferences = app(PreferenceManager::class);

    Json::encodeUsing(fn (mixed $value): mixed => json_encode($value));

    try {
        $preferences->set('test.float-without-teams', 1.0, PreferenceScope::User, $context, $admin);
        $preferences->set('test.float-without-teams', -0.0, PreferenceScope::Everyone, $context, $admin);

        $rawValues = Option::withoutGlobalScopes()
            ->get()
            ->map(fn (Option $option): mixed => $option->getRawOriginal('value'))
            ->all();

        expect($rawValues)->toContain('1.0', '-0.0')
            ->and($preferences->get('test.float-without-teams', $context))->toBe(1.0);

        Cache::flush();

        expect($preferences->get('test.float-without-teams', $context))->toBe(1.0);
    } finally {
        Json::encodeUsing(null);
    }
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
