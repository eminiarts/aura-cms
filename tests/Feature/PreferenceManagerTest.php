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
use Illuminate\Cache\ArrayStore;
use Illuminate\Cache\Repository;
use Illuminate\Database\Eloquent\Casts\Json;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Team-scoped preference tests require teams enabled.');
    }
});

function preferenceManager(): PreferenceManager
{
    return app(PreferenceManager::class);
}

function registerPreference(PreferenceDefinition $definition): void
{
    app(PreferenceRegistry::class)->register($definition);
}

function preferenceContext(User $user, Team $team, ?string $resource = 'Article'): PreferenceContext
{
    return new PreferenceContext('crm', $user, $team, $resource);
}

function preferenceGlobalAdmin(): User
{
    $user = createSuperAdmin();
    $user->forceFill(['global_admin' => true])->saveQuietly();

    return $user->refresh();
}

test('resource and owner precedence is deterministic', function () {
    $user = preferenceGlobalAdmin();
    $team = $user->currentTeam;
    $context = preferenceContext($user, $team);
    $preferences = preferenceManager();

    expect($preferences->get('table.view', $context))->toBe('list');

    $preferences->set('table.view', 'kanban', PreferenceScope::Everyone, $context, $user);
    $preferences->set('table.view', 'list', PreferenceScope::Team, $context, $user);
    $preferences->set('table.view', 'kanban', PreferenceScope::User, $context, $user);

    $result = $preferences->resolve('table.view', $context);

    expect($result->value)->toBe('kanban')
        ->and($result->scope)->toBe(PreferenceScope::User)
        ->and($result->resourceSpecific)->toBeTrue();

    $preferences->reset('table.view', PreferenceScope::User, $context, $user);

    expect($preferences->get('table.view', $context))->toBe('list');

    $preferences->reset('table.view', PreferenceScope::Team, $context, $user);

    expect($preferences->get('table.view', $context))->toBe('kanban');
});

test('resource values fall back to application values before defaults', function () {
    $user = createSuperAdmin();
    $context = preferenceContext($user, $user->currentTeam);
    $applicationContext = $context->forApplication();
    $preferences = preferenceManager();

    $preferences->set('table.view', 'kanban', PreferenceScope::User, $context, $user);

    $preferences->set('table.view', 'list', PreferenceScope::User, $applicationContext, $user);

    expect($preferences->get('table.view', preferenceContext($user, $context->team, 'Contact')))->toBe('list')
        ->and($preferences->resolve(
            'table.view',
            new PreferenceContext('support', $user, $context->team, 'Contact'),
        )->isDefault)->toBeTrue();
});

test('false zero and null remain stored values', function () {
    registerPreference(new PreferenceDefinition(
        key: 'test.boolean',
        type: PreferenceValueType::Boolean,
        default: true,
        scopes: [PreferenceScope::User],
    ));
    registerPreference(new PreferenceDefinition(
        key: 'test.integer',
        type: PreferenceValueType::Integer,
        default: 1,
        scopes: [PreferenceScope::User],
    ));
    registerPreference(new PreferenceDefinition(
        key: 'test.nullable',
        type: PreferenceValueType::String,
        default: null,
        scopes: [PreferenceScope::User],
        nullable: true,
    ));

    $user = createSuperAdmin();
    $context = preferenceContext($user, $user->currentTeam, null);
    $preferences = preferenceManager();

    $preferences->set('test.boolean', false, PreferenceScope::User, $context, $user);
    $preferences->set('test.integer', 0, PreferenceScope::User, $context, $user);
    $preferences->set('test.nullable', null, PreferenceScope::User, $context, $user);

    expect($preferences->get('test.boolean', $context))->toBeFalse()
        ->and($preferences->get('test.integer', $context))->toBe(0)
        ->and($preferences->resolve('test.nullable', $context)->isDefault)->toBeFalse()
        ->and($preferences->get('test.nullable', $context))->toBeNull();
});

test('explicit reads ignore ambient auth and team switches', function () {
    $firstUser = createSuperAdmin();
    $firstTeam = $firstUser->currentTeam;
    $secondTeam = Team::factory()->create(['user_id' => $firstUser->id]);
    $secondUser = soleMemberOf($firstTeam);
    $preferences = preferenceManager();
    $firstContext = preferenceContext($firstUser, $firstTeam);
    $secondContext = preferenceContext($firstUser, $secondTeam);
    $secondUserContext = preferenceContext($secondUser, $firstTeam);

    $preferences->set('table.view', 'kanban', PreferenceScope::User, $firstContext, $firstUser);
    $preferences->set('table.view', 'list', PreferenceScope::User, $secondContext, $firstUser);
    $preferences->set('table.view', 'list', PreferenceScope::User, $secondUserContext, $secondUser);

    Auth::login($secondUser);
    $firstUser->forceFill(['current_team_id' => $secondTeam->id]);

    expect($preferences->get('table.view', $firstContext))->toBe('kanban')
        ->and($preferences->get('table.view', $secondContext))->toBe('list')
        ->and($preferences->get('table.view', $secondUserContext))->toBe('list');

    Auth::logout();

    expect($preferences->get('table.view', $firstContext))->toBe('kanban');
});

test('writes authorize the explicit actor and exact target scope', function () {
    $owner = createSuperAdmin();
    $team = $owner->currentTeam;
    $member = soleMemberOf($team);
    $other = User::factory()->create();
    $preferences = preferenceManager();
    $context = preferenceContext($owner, $team);

    Auth::login($other);
    $preferences->set('table.view', 'kanban', PreferenceScope::User, $context, $owner);

    expect(fn () => $preferences->set('table.view', 'list', PreferenceScope::User, $context, $other))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $preferences->set('table.view', 'list', PreferenceScope::Team, $context, $member))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $preferences->set('table.view', 'list', PreferenceScope::Everyone, $context, $member))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $preferences->set('table.view', 'list', PreferenceScope::User, $context, null))
        ->toThrow(AuthorizationException::class);
});

test('team writes reject a former owner from a stale explicit context', function () {
    $formerOwner = createSuperAdmin();
    $team = $formerOwner->currentTeam;
    $context = preferenceContext($formerOwner, $team);
    $newOwner = User::factory()->create();
    $preferences = preferenceManager();

    DB::table('teams')->where('id', $team->id)->update(['user_id' => $newOwner->id]);

    expect(fn () => $preferences->set('table.view', 'kanban', PreferenceScope::Team, $context, $formerOwner))
        ->toThrow(AuthorizationException::class)
        ->and(Option::withoutGlobalScopes()->count())->toBe(0);
});

test('team writes reject forged in-memory ownership for a cross-team actor', function () {
    $owner = createSuperAdmin();
    $team = $owner->currentTeam;
    $outsider = User::factory()->create();
    $team->forceFill(['user_id' => $outsider->id]);
    $context = preferenceContext($owner, $team);

    expect(fn () => preferenceManager()->set(
        'table.view',
        'kanban',
        PreferenceScope::Team,
        $context,
        $outsider,
    ))->toThrow(AuthorizationException::class)
        ->and(Option::withoutGlobalScopes()->count())->toBe(0);
});

test('writes use persisted actor privilege membership and target existence', function () {
    $admin = preferenceGlobalAdmin();
    $team = $admin->currentTeam;
    $member = soleMemberOf($team);
    $preferences = preferenceManager();

    DB::table('users')->where('id', $admin->id)->update(['global_admin' => false]);

    expect(fn () => $preferences->set(
        'table.view',
        'kanban',
        PreferenceScope::Everyone,
        preferenceContext($admin, $team),
        $admin,
    ))->toThrow(AuthorizationException::class);

    $memberContext = preferenceContext($member, $team);
    DB::table('user_role')
        ->where('team_id', $team->id)
        ->where('user_id', $member->id)
        ->delete();

    expect(fn () => $preferences->set('table.view', 'kanban', PreferenceScope::User, $memberContext, $member))
        ->toThrow(AuthorizationException::class);

    $team->deleteQuietly();

    expect(fn () => $preferences->set('table.view', 'kanban', PreferenceScope::Team, preferenceContext($admin, $team), $admin))
        ->toThrow(AuthorizationException::class)
        ->and(Option::withoutGlobalScopes()->count())->toBe(0);
});

test('unsaved actors cannot borrow a persisted user identity', function (PreferenceScope $scope) {
    $persistedUser = preferenceGlobalAdmin();
    $team = $persistedUser->currentTeam;
    $forgedActor = new User;
    $forgedActor->forceFill(['id' => $persistedUser->id]);
    $context = preferenceContext($persistedUser, $team);
    $preferences = preferenceManager();

    DB::enableQueryLog();
    DB::flushQueryLog();

    expect(fn () => $preferences->set('table.view', 'kanban', $scope, $context, $forgedActor))
        ->toThrow(AuthorizationException::class);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($queries)->toBeEmpty()
        ->and(Option::withoutGlobalScopes()->count())->toBe(0);
})->with([
    'user scope' => PreferenceScope::User,
    'team scope' => PreferenceScope::Team,
    'everyone scope' => PreferenceScope::Everyone,
]);

test('mutated persisted actor keys and unsaved target users fail before database access', function () {
    $persistedUser = preferenceGlobalAdmin();
    $team = $persistedUser->currentTeam;
    $mutatedActor = User::factory()->create();
    $mutatedActor->forceFill(['id' => $persistedUser->id]);
    $unsavedTarget = new User;
    $unsavedTarget->forceFill(['id' => $persistedUser->id]);
    $preferences = preferenceManager();

    DB::enableQueryLog();
    DB::flushQueryLog();

    expect(fn () => $preferences->set(
        'table.view',
        'kanban',
        PreferenceScope::Everyone,
        preferenceContext($persistedUser, $team),
        $mutatedActor,
    ))->toThrow(AuthorizationException::class)
        ->and(fn () => $preferences->set(
            'table.view',
            'kanban',
            PreferenceScope::User,
            preferenceContext($unsavedTarget, $team),
            $persistedUser,
        ))->toThrow(AuthorizationException::class);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($queries)->toBeEmpty()
        ->and(Option::withoutGlobalScopes()->count())->toBe(0);
});

test('reserved everyone team identity cannot be used by normal models', function () {
    $user = preferenceGlobalAdmin();
    $reservedTeam = new Team;
    $reservedTeam->forceFill([
        'id' => Option::EVERYONE_TEAM_ID,
        'user_id' => $user->id,
        'name' => 'Reserved collision',
    ]);

    expect(fn () => $reservedTeam->save())->toThrow(InvalidArgumentException::class, 'reserved')
        ->and(fn () => $user->forceFill(['current_team_id' => Option::EVERYONE_TEAM_ID])->save())
        ->toThrow(InvalidArgumentException::class, 'reserved')
        ->and($user->switchTeam($reservedTeam))->toBeFalse()
        ->and(fn () => preferenceContext($user, $reservedTeam))
        ->toThrow(InvalidArgumentException::class, 'reserved')
        ->and(fn () => $reservedTeam->updateOption('table.view', 'kanban'))
        ->toThrow(InvalidArgumentException::class, 'reserved');
});

test('reserved everyone identity fails closed in team scope and invalidates only global preferences', function () {
    $admin = preferenceGlobalAdmin();
    $firstTeam = $admin->currentTeam;
    $secondTeam = Team::factory()->createQuietly(['user_id' => $admin->id]);
    $context = preferenceContext($admin, $firstTeam);
    $preferences = preferenceManager();

    $firstTeam->updateOption('tenant-probe', 'first');
    $secondTeam->updateOption('tenant-probe', 'second');
    $preferences->set('table.view', 'kanban', PreferenceScope::Everyone, $context, $admin);
    expect($preferences->get('table.view', $context))->toBe('kanban');

    DB::table('teams')->insert([
        'id' => Option::EVERYONE_TEAM_ID,
        'user_id' => $admin->id,
        'name' => 'Hostile collision',
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table('users')->where('id', $admin->id)->update([
        'current_team_id' => Option::EVERYONE_TEAM_ID,
    ]);
    User::clearCurrentTeamCache($admin->id);

    expect(Option::query()->count())->toBe(0);

    $reservedTeam = Team::withoutGlobalScopes()->findOrFail(Option::EVERYONE_TEAM_ID);

    expect(fn () => $reservedTeam->forceFill(['name' => 'Forged update'])->save())
        ->toThrow(InvalidArgumentException::class, 'reserved')
        ->and(fn () => $reservedTeam->delete())
        ->toThrow(InvalidArgumentException::class, 'reserved');

    $everyone = Option::withoutGlobalScopes()
        ->where('team_id', Option::EVERYONE_TEAM_ID)
        ->where('name', 'like', 'preference.v1.%')
        ->sole();
    $everyone->update(['value' => 'list']);

    expect($preferences->get('table.view', $context))->toBe('list');

    DB::table('users')->where('id', $admin->id)->update(['current_team_id' => $firstTeam->id]);
    User::clearCurrentTeamCache($admin->id);

    expect(Option::query()->where('name', 'like', 'team.%.tenant-probe')->pluck('team_id')->all())
        ->toBe([$firstTeam->id]);
});

test('schemas reject unknown keys invalid types and invalid values', function () {
    $user = createSuperAdmin();
    $context = preferenceContext($user, $user->currentTeam);
    $preferences = preferenceManager();

    expect(fn () => $preferences->get('unknown.key', $context))
        ->toThrow(InvalidArgumentException::class, 'not registered')
        ->and(fn () => $preferences->set('table.view', false, PreferenceScope::User, $context, $user))
        ->toThrow(InvalidArgumentException::class, 'must be string')
        ->and(fn () => $preferences->set('table.view', 'grid', PreferenceScope::User, $context, $user))
        ->toThrow(InvalidArgumentException::class, 'outside its schema')
        ->and(fn () => $preferences->set('table.columns', ['title' => true], PreferenceScope::User, $context, $user))
        ->toThrow(InvalidArgumentException::class, 'must be a list')
        ->and(fn () => $preferences->set('table.columns', ['title', 1], PreferenceScope::User, $context, $user))
        ->toThrow(InvalidArgumentException::class, 'invalid item')
        ->and(fn () => new PreferenceDefinition('Bad Key', PreferenceValueType::String, 'x'))
        ->toThrow(InvalidArgumentException::class, 'Invalid preference key');
});

test('declarations reject contradictory array schemas', function () {
    expect(fn () => new PreferenceDefinition(
        key: 'test.string-list',
        type: PreferenceValueType::String,
        default: 'value',
        list: true,
    ))->toThrow(InvalidArgumentException::class, 'array type')
        ->and(fn () => new PreferenceDefinition(
            key: 'test.boolean-items',
            type: PreferenceValueType::Boolean,
            default: true,
            itemType: PreferenceValueType::String,
        ))->toThrow(InvalidArgumentException::class, 'array type');
});

test('finite floats preserve their exact type across option storage and fresh cache reads', function (float $value, string $json) {
    registerPreference(new PreferenceDefinition(
        key: 'test.float',
        type: PreferenceValueType::Float,
        default: 2.5,
        scopes: [PreferenceScope::User],
    ));
    $user = createSuperAdmin();
    $context = preferenceContext($user, $user->currentTeam, null);
    $preferences = preferenceManager();

    Json::encodeUsing(fn (mixed $value): mixed => json_encode($value));

    try {
        $preferences->set('test.float', $value, PreferenceScope::User, $context, $user);

        expect($preferences->get('test.float', $context))->toBe($value)
            ->and(Option::withoutGlobalScopes()->sole()->getRawOriginal('value'))->toBe($json);

        Cache::flush();

        expect(preferenceManager()->get('test.float', $context))->toBe($value);
    } finally {
        Json::encodeUsing(null);
    }
})->with([
    'positive zero' => [0.0, '0.0'],
    'negative zero' => [-0.0, '-0.0'],
    'whole float' => [1.0, '1.0'],
    'fractional float' => [1.25, '1.25'],
]);

test('float declarations reject integers non-finite values and other invalid types', function (mixed $value) {
    registerPreference(new PreferenceDefinition(
        key: 'test.finite-float',
        type: PreferenceValueType::Float,
        default: 0.5,
        scopes: [PreferenceScope::User],
    ));
    $user = createSuperAdmin();
    $context = preferenceContext($user, $user->currentTeam, null);

    expect(fn () => preferenceManager()->set(
        'test.finite-float',
        $value,
        PreferenceScope::User,
        $context,
        $user,
    ))->toThrow(InvalidArgumentException::class, 'must be float');
})->with([
    'integer zero' => 0,
    'numeric string' => '0.0',
    'boolean' => false,
    'positive infinity' => INF,
    'negative infinity' => -INF,
    'not a number' => NAN,
]);

test('global writes require a global admin and reset cleanly', function () {
    $admin = preferenceGlobalAdmin();
    $context = preferenceContext($admin, $admin->currentTeam);
    $preferences = preferenceManager();

    $preferences->set('table.view', 'kanban', PreferenceScope::Everyone, $context, $admin);

    expect($preferences->get('table.view', $context))->toBe('kanban');

    $preferences->reset('table.view', PreferenceScope::Everyone, $context, $admin);

    expect($preferences->get('table.view', $context))->toBe('list');
});

test('legacy user options are migration-safe reads', function () {
    $user = createSuperAdmin();
    $context = preferenceContext($user, $user->currentTeam);

    $user->updateOption('columns.Article', ['title', 'status']);

    $result = preferenceManager()->resolve('table.columns', $context);

    expect($result->value)->toBe(['title', 'status'])
        ->and($result->scope)->toBe(PreferenceScope::User)
        ->and($result->isLegacy)->toBeTrue();

    preferenceManager()->reset('table.columns', PreferenceScope::User, $context, $user);

    expect(preferenceManager()->resolve('table.columns', $context)->isDefault)->toBeTrue();
});

test('direct everyone option edits invalidate the global scalar cache', function () {
    $admin = preferenceGlobalAdmin();
    $context = preferenceContext($admin, $admin->currentTeam);
    $preferences = preferenceManager();

    $preferences->set('table.view', 'kanban', PreferenceScope::Everyone, $context, $admin);
    expect($preferences->get('table.view', $context))->toBe('kanban');

    $record = Option::withoutGlobalScopes()
        ->where('team_id', Option::EVERYONE_TEAM_ID)
        ->sole();
    $record->update(['value' => 'list']);

    expect($preferences->get('table.view', $context))->toBe('list');
});

test('serialized caches rebind to fresh scalar values', function () {
    $constructor = new ReflectionMethod(ArrayStore::class, '__construct');
    $store = $constructor->getNumberOfParameters() === 1
        ? new ArrayStore(serializesValues: true)
        : new ArrayStore(serializesValues: true, serializableClasses: false);
    Cache::swap(new Repository($store));
    $user = createSuperAdmin();
    $context = preferenceContext($user, $user->currentTeam);
    $preferences = preferenceManager();

    $preferences->set('table.view', 'kanban', PreferenceScope::User, $context, $user);
    expect($preferences->get('table.view', $context))->toBe('kanban');

    $preferences->set('table.view', 'list', PreferenceScope::User, $context, $user);

    expect($preferences->get('table.view', $context))->toBe('list')
        ->and(Option::withoutGlobalScopes()->count())->toBe(1);
});
