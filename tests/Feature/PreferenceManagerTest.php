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

class EmailPreferenceUser extends User
{
    public function getAuthIdentifierName(): string
    {
        return 'email';
    }
}

class StringKeyPreferenceTeam extends Team
{
    protected $keyType = 'string';
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
    Auth::login($secondUser);
    $preferences->set('table.view', 'list', PreferenceScope::User, $secondUserContext, $secondUser);

    Auth::login($secondUser);
    $firstUser->forceFill(['current_team_id' => $secondTeam->id]);

    expect($preferences->get('table.view', $firstContext))->toBe('kanban')
        ->and($preferences->get('table.view', $secondContext))->toBe('list')
        ->and($preferences->get('table.view', $secondUserContext))->toBe('list');

    Auth::logout();

    expect($preferences->get('table.view', $firstContext))->toBe('kanban');
});

test('writes bind the explicit actor to authentication and exact target scope', function () {
    $owner = createSuperAdmin();
    $team = $owner->currentTeam;
    $member = soleMemberOf($team);
    $other = User::factory()->create();
    $preferences = preferenceManager();
    $context = preferenceContext($owner, $team);

    Auth::login($owner);
    $preferences->set('table.view', 'kanban', PreferenceScope::User, $context, $owner);

    Auth::login($other);

    expect(fn () => $preferences->set('table.view', 'list', PreferenceScope::User, $context, $owner))
        ->toThrow(AuthorizationException::class)
        ->and(fn () => $preferences->set('table.view', 'list', PreferenceScope::User, $context, $other))
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

    Auth::login($member);

    expect(fn () => $preferences->set('table.view', 'kanban', PreferenceScope::User, $memberContext, $member))
        ->toThrow(AuthorizationException::class);

    $team->deleteQuietly();
    Auth::login($admin);

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

test('writes bind fabricated actors to the authenticated principal before database access', function () {
    $admin = preferenceGlobalAdmin();
    $team = $admin->currentTeam;
    $authenticatedUser = User::factory()->create();
    Auth::login($authenticatedUser);

    $fabricatedFromBuilder = (new User)->newFromBuilder(['id' => $admin->id]);
    $spoofedExists = new User;
    $spoofedExists->setRawAttributes(['id' => $admin->id], true);
    $spoofedExists->exists = true;
    $synchronizedMutation = User::factory()->create();
    $synchronizedMutation->forceFill(['id' => $admin->id]);
    $synchronizedMutation->syncOriginalAttribute('id', $admin->id);

    foreach ([$fabricatedFromBuilder, $spoofedExists, $synchronizedMutation] as $fabricatedActor) {
        DB::enableQueryLog();
        DB::flushQueryLog();

        expect(fn () => preferenceManager()->set(
            'table.view',
            'kanban',
            PreferenceScope::Everyone,
            preferenceContext($admin, $team),
            $fabricatedActor,
        ))->toThrow(AuthorizationException::class);

        $queries = DB::getQueryLog();
        DB::disableQueryLog();

        expect($queries)->toBeEmpty();
    }

    Auth::logout();
    DB::enableQueryLog();
    DB::flushQueryLog();

    expect(fn () => preferenceManager()->set(
        'table.view',
        'kanban',
        PreferenceScope::Everyone,
        preferenceContext($admin, $team),
        $admin,
    ))->toThrow(AuthorizationException::class);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($queries)->toBeEmpty()
        ->and(Option::withoutGlobalScopes()->count())->toBe(0);
});

test('writes reject an authenticated actor whose session identity was synchronized to another key', function () {
    $admin = preferenceGlobalAdmin();
    $team = $admin->currentTeam;
    $authenticatedUser = User::factory()->create();
    Auth::login($authenticatedUser);
    $authenticatedUser->forceFill(['id' => $admin->id]);
    $authenticatedUser->syncOriginalAttribute('id', $admin->id);

    DB::enableQueryLog();
    DB::flushQueryLog();

    expect(fn () => preferenceManager()->set(
        'table.view',
        'kanban',
        PreferenceScope::Everyone,
        preferenceContext($admin, $team),
        $authenticatedUser,
    ))->toThrow(AuthorizationException::class);

    $queries = DB::getQueryLog();
    DB::disableQueryLog();

    expect($queries)->toBeEmpty()
        ->and(Option::withoutGlobalScopes()->count())->toBe(0);
});

test('authenticated writes authorize the canonical user instead of mutable actor attributes', function () {
    $admin = preferenceGlobalAdmin();
    $member = soleMemberOf($admin->currentTeam);
    Auth::login($member);

    $forgedActor = (new User)->newFromBuilder(array_replace(
        $member->getAttributes(),
        ['global_admin' => true],
    ));
    $context = preferenceContext($forgedActor, $admin->currentTeam);

    preferenceManager()->set('table.view', 'kanban', PreferenceScope::User, $context, $forgedActor);

    expect(preferenceManager()->get('table.view', $context))->toBe('kanban')
        ->and(fn () => preferenceManager()->set(
            'table.view',
            'list',
            PreferenceScope::Everyone,
            $context,
            $forgedActor,
        ))->toThrow(AuthorizationException::class);
});

test('non-primary authentication identifiers canonicalize every write scope', function () {
    $baseUser = preferenceGlobalAdmin();
    config()->set('aura.resources.user', EmailPreferenceUser::class);
    config()->set('auth.providers.users.model', EmailPreferenceUser::class);
    $user = EmailPreferenceUser::withoutGlobalScopes()->findOrFail($baseUser->getKey());
    Auth::login($user);
    $context = preferenceContext($user, $user->currentTeam);

    foreach (PreferenceScope::cases() as $scope) {
        preferenceManager()->set('table.view', 'kanban', $scope, $context, $user);
        expect(preferenceManager()->resolve('table.view', $context)->scope)->toBe($scope);
        preferenceManager()->reset('table.view', $scope, $context, $user);
    }

    $forgedActor = (new EmailPreferenceUser)->newFromBuilder(array_replace(
        $user->getAttributes(),
        ['global_admin' => true],
    ));
    DB::table('users')->where('id', $user->getKey())->update(['global_admin' => false]);

    expect(fn () => preferenceManager()->set(
        'table.view',
        'kanban',
        PreferenceScope::Everyone,
        $context,
        $forgedActor,
    ))->toThrow(AuthorizationException::class);
});

test('a configured custom auth provider authorizes its non-global canonical team owner', function () {
    $baseUser = preferenceGlobalAdmin();
    $baseUser->forceFill(['global_admin' => false])->saveQuietly();
    config()->set('aura.resources.user', EmailPreferenceUser::class);
    config()->set('auth.providers.users.model', EmailPreferenceUser::class);
    $user = EmailPreferenceUser::withoutGlobalScopes()->findOrFail($baseUser->getKey());
    $team = $user->currentTeam;
    Auth::login($user);
    $context = preferenceContext($user, $team);

    preferenceManager()->set('table.view', 'kanban', PreferenceScope::Team, $context, $user);

    expect(preferenceManager()->get('table.view', $context))->toBe('kanban')
        ->and($team->getAttribute('user_id'))->toBe($user->getKey());

    $wrongModelActor = User::withoutGlobalScopes()->findOrFail($user->getKey());
    Auth::login($wrongModelActor);

    expect(fn () => preferenceManager()->set(
        'table.view',
        'list',
        PreferenceScope::Team,
        preferenceContext($wrongModelActor, $team),
        $wrongModelActor,
    ))->toThrow(AuthorizationException::class);
});

test('serialized authenticated actors remain valid write assertions', function () {
    $admin = preferenceGlobalAdmin();
    $context = preferenceContext($admin, $admin->currentTeam);
    $serializedActor = unserialize(serialize($admin));

    preferenceManager()->set('table.view', 'kanban', PreferenceScope::Everyone, $context, $serializedActor);

    expect(preferenceManager()->get('table.view', $context))->toBe('kanban');
});

test('team writes reject unauthentic target models', function () {
    $owner = createSuperAdmin();
    $team = $owner->currentTeam;
    $otherTeam = Team::factory()->createQuietly(['user_id' => $owner->id]);
    $unsavedCopy = new Team;
    $unsavedCopy->forceFill(['id' => $team->id, 'user_id' => $owner->id, 'name' => $team->name]);
    $spoofedExists = new Team;
    $spoofedExists->setRawAttributes(['id' => $team->id], true);
    $spoofedExists->exists = true;
    $mutatedKey = $team->replicate();
    $mutatedKey->exists = true;
    $mutatedKey->setRawAttributes($team->getRawOriginal(), true);
    $mutatedKey->forceFill(['id' => $otherTeam->id]);
    $synchronizedMutation = clone $team;
    $synchronizedMutation->forceFill(['id' => $otherTeam->id]);
    $synchronizedMutation->syncOriginalAttribute('id', $otherTeam->id);
    $typeJuggledOwner = (new Team)->newFromBuilder(array_replace(
        $team->getAttributes(),
        ['user_id' => $team->user_id.'e0'],
    ));
    $crossConnection = clone $team;
    $crossConnection->setConnection('preference-hostile');
    config()->set('database.connections.preference-hostile', [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
    ]);

    foreach ([
        $unsavedCopy,
        $spoofedExists,
        $mutatedKey,
        $synchronizedMutation,
        $typeJuggledOwner,
        $crossConnection,
    ] as $target) {
        expect(fn () => preferenceManager()->set(
            'table.view',
            'kanban',
            PreferenceScope::Team,
            preferenceContext($owner, $target),
            $owner,
        ))->toThrow(AuthorizationException::class);
    }

    expect(Option::withoutGlobalScopes()->count())->toBe(0);
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
    config()->set('aura.resources.team', StringKeyPreferenceTeam::class);
    $zeroPaddedTarget = (new StringKeyPreferenceTeam)->newFromBuilder(array_replace(
        $reservedTeam->getAttributes(),
        ['id' => '00'],
    ));
    registerPreference(new PreferenceDefinition(
        key: 'test.reserved-float',
        type: PreferenceValueType::Float,
        default: 2.5,
        scopes: [PreferenceScope::Team],
    ));

    expect(fn () => $reservedTeam->forceFill(['name' => 'Forged update'])->save())
        ->toThrow(InvalidArgumentException::class, 'reserved')
        ->and(fn () => $reservedTeam->delete())
        ->toThrow(InvalidArgumentException::class, 'reserved')
        ->and(fn () => $preferences->set(
            'test.reserved-float',
            1.0,
            PreferenceScope::Team,
            preferenceContext($admin, $zeroPaddedTarget),
            $admin,
        ))->toThrow(AuthorizationException::class);

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

test('finite floats preserve their exact type across every scope and fresh cache reads', function (
    float $value,
    string $json,
    PreferenceScope $scope,
) {
    registerPreference(new PreferenceDefinition(
        key: 'test.float',
        type: PreferenceValueType::Float,
        default: 2.5,
        scopes: PreferenceScope::cases(),
    ));
    $user = preferenceGlobalAdmin();
    $context = preferenceContext($user, $user->currentTeam, null);
    $preferences = preferenceManager();

    Json::encodeUsing(fn (mixed $value): mixed => json_encode($value));

    try {
        $preferences->set('test.float', $value, $scope, $context, $user);

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
    'positive exponent' => [1.0e+30, '1.0e+30'],
    'negative exponent' => [-3.125e-9, '-3.125e-9'],
])->with([
    'user scope' => PreferenceScope::User,
    'team scope' => PreferenceScope::Team,
    'everyone scope' => PreferenceScope::Everyone,
]);

test('preference float transport is unavailable to generic option APIs', function () {
    $user = createSuperAdmin();
    $payload = (object) ['value' => 12.0];

    $user->updateOption('generic-object', $payload);

    expect(class_exists('Aura\\Base\\Preferences\\PreferenceFloatValue'))->toBeFalse()
        ->and(method_exists(Option::class, 'setPreferenceFloatValue'))->toBeFalse()
        ->and(method_exists(User::class, 'updatePreferenceFloatOptionForTeam'))->toBeFalse()
        ->and(method_exists(Team::class, 'updatePreferenceFloatOption'))->toBeFalse()
        ->and($user->getOption('generic-object'))->toBeArray()
        ->and($user->getOption('generic-object'))->toHaveKey('value');
});

test('float model events observe the same exact value committed to storage', function (PreferenceScope $scope) {
    registerPreference(new PreferenceDefinition(
        key: 'test.float-events',
        type: PreferenceValueType::Float,
        default: 2.5,
        scopes: PreferenceScope::cases(),
    ));
    $user = preferenceGlobalAdmin();
    $context = preferenceContext($user, $user->currentTeam, null);
    $observed = [];

    Option::saving(function (Option $option) use (&$observed): void {
        if (str_contains((string) $option->getAttribute('name'), 'preference.v1.')) {
            $observed[] = [
                'raw' => $option->getAttributes()['value'],
                'value' => $option->getAttributeValue('value'),
            ];
        }
    });

    try {
        preferenceManager()->set('test.float-events', 1.0, $scope, $context, $user);

        expect($observed)->toBe([['raw' => '1.0', 'value' => 1.0]])
            ->and(Option::withoutGlobalScopes()->sole()->getRawOriginal('value'))->toBe('1.0');
    } finally {
        Option::flushEventListeners();
        Option::clearBootedModels();
    }
})->with([
    'user scope' => PreferenceScope::User,
    'team scope' => PreferenceScope::Team,
    'everyone scope' => PreferenceScope::Everyone,
]);

test('a float observer veto rolls back storage and cache without a hidden rewrite', function (PreferenceScope $scope) {
    registerPreference(new PreferenceDefinition(
        key: 'test.float-veto',
        type: PreferenceValueType::Float,
        default: 2.5,
        scopes: PreferenceScope::cases(),
    ));
    $user = preferenceGlobalAdmin();
    $context = preferenceContext($user, $user->currentTeam, null);
    $preferences = preferenceManager();

    $preferences->set('test.float-veto', 1.0, $scope, $context, $user);

    Option::updating(function (Option $option): void {
        if (str_contains((string) $option->getAttribute('name'), 'preference.v1.')) {
            expect($option->getAttributes()['value'])->toBe('2.0')
                ->and($option->getAttributeValue('value'))->toBe(2.0);

            throw new RuntimeException('Preference float veto.');
        }
    });

    try {
        expect(fn () => $preferences->set(
            'test.float-veto',
            2.0,
            $scope,
            $context,
            $user,
        ))->toThrow(RuntimeException::class, 'veto')
            ->and(Option::withoutGlobalScopes()->sole()->getRawOriginal('value'))->toBe('1.0');

        Cache::flush();

        expect($preferences->get('test.float-veto', $context))->toBe(1.0);
    } finally {
        Option::flushEventListeners();
        Option::clearBootedModels();
    }
})->with([
    'user scope' => PreferenceScope::User,
    'team scope' => PreferenceScope::Team,
    'everyone scope' => PreferenceScope::Everyone,
]);

test('a false float observer veto aborts without alias cleanup or cache invalidation', function (PreferenceScope $scope) {
    registerPreference(new PreferenceDefinition(
        key: 'test.float-false-veto',
        type: PreferenceValueType::Float,
        default: 2.5,
        scopes: PreferenceScope::cases(),
    ));
    $user = preferenceGlobalAdmin();
    $context = preferenceContext($user, $user->currentTeam, null);
    $preferences = preferenceManager();

    $preferences->set('test.float-false-veto', 1.0, $scope, $context, $user);
    expect($preferences->get('test.float-false-veto', $context))->toBe(1.0);

    $storedOption = Option::withoutGlobalScopes()->sole();
    $storedName = (string) $storedOption->getRawOriginal('name');
    $aliasName = null;

    if ($scope === PreferenceScope::User) {
        $storageKey = substr($storedName, strlen(User::optionNamePrefixFor($user->getKey())));
        $aliasName = 'user.'.$user->getKey().'.'.$storageKey;
        $aliasAttributes = $storedOption->getAttributes();
        unset($aliasAttributes[$storedOption->getKeyName()]);
        $aliasAttributes['name'] = $aliasName;
        DB::table($storedOption->getTable())->insert($aliasAttributes);
    }

    Option::updating(function (Option $option): bool {
        return ! str_contains((string) $option->getAttribute('name'), 'preference.v1.');
    });

    try {
        expect(fn () => $preferences->set(
            'test.float-false-veto',
            2.0,
            $scope,
            $context,
            $user,
        ))->toThrow(RuntimeException::class, 'vetoed')
            ->and(Option::withoutGlobalScopes()
                ->where('name', $storedName)
                ->sole()
                ->getRawOriginal('value'))->toBe('1.0')
            ->and($preferences->get('test.float-false-veto', $context))->toBe(1.0);

        if ($aliasName !== null) {
            expect(Option::withoutGlobalScopes()->where('name', $aliasName)->exists())->toBeTrue();
        }
    } finally {
        Option::flushEventListeners();
        Option::clearBootedModels();
    }
})->with([
    'user scope' => PreferenceScope::User,
    'team scope' => PreferenceScope::Team,
    'everyone scope' => PreferenceScope::Everyone,
]);

test('a false restore veto leaves a float option deleted', function (PreferenceScope $scope) {
    registerPreference(new PreferenceDefinition(
        key: 'test.float-restore-veto',
        type: PreferenceValueType::Float,
        default: 2.5,
        scopes: PreferenceScope::cases(),
    ));
    $user = preferenceGlobalAdmin();
    $context = preferenceContext($user, $user->currentTeam, null);
    $preferences = preferenceManager();

    $preferences->set('test.float-restore-veto', 1.0, $scope, $context, $user);
    $storedOption = Option::withoutGlobalScopes()->sole();
    $storedOption->deleteQuietly();
    Cache::flush();

    Option::restoring(function (Option $option): bool {
        return ! str_contains((string) $option->getAttribute('name'), 'preference.v1.');
    });

    try {
        expect(fn () => $preferences->set(
            'test.float-restore-veto',
            2.0,
            $scope,
            $context,
            $user,
        ))->toThrow(RuntimeException::class, 'vetoed')
            ->and(Option::withoutGlobalScopes()
                ->withTrashed()
                ->sole()
                ->trashed())->toBeTrue()
            ->and(Option::withoutGlobalScopes()
                ->withTrashed()
                ->sole()
                ->getRawOriginal('value'))->toBe('1.0')
            ->and($preferences->get('test.float-restore-veto', $context))->toBe(2.5);
    } finally {
        Option::flushEventListeners();
        Option::clearBootedModels();
    }
})->with([
    'user scope' => PreferenceScope::User,
    'team scope' => PreferenceScope::Team,
]);

test('a false alias deletion veto rolls back the float update and preserves the alias', function () {
    registerPreference(new PreferenceDefinition(
        key: 'test.float-delete-veto',
        type: PreferenceValueType::Float,
        default: 2.5,
        scopes: [PreferenceScope::User],
    ));
    $user = preferenceGlobalAdmin();
    $context = preferenceContext($user, $user->currentTeam, null);
    $preferences = preferenceManager();

    $preferences->set('test.float-delete-veto', 1.0, PreferenceScope::User, $context, $user);
    expect($preferences->get('test.float-delete-veto', $context))->toBe(1.0);

    $canonical = Option::withoutGlobalScopes()->sole();
    $canonicalName = (string) $canonical->getRawOriginal('name');
    $storageKey = substr($canonicalName, strlen(User::optionNamePrefixFor($user->getKey())));
    $aliasName = 'user.'.$user->getKey().'.'.$storageKey;
    $aliasAttributes = $canonical->getAttributes();
    unset($aliasAttributes[$canonical->getKeyName()]);
    $aliasAttributes['name'] = $aliasName;
    DB::table($canonical->getTable())->insert($aliasAttributes);

    Option::deleting(function (Option $option) use ($aliasName): bool {
        return $option->getRawOriginal('name') !== $aliasName;
    });

    try {
        expect(fn () => $preferences->set(
            'test.float-delete-veto',
            2.0,
            PreferenceScope::User,
            $context,
            $user,
        ))->toThrow(RuntimeException::class, 'vetoed')
            ->and(Option::withoutGlobalScopes()
                ->where('name', $canonicalName)
                ->sole()
                ->getRawOriginal('value'))->toBe('1.0')
            ->and(Option::withoutGlobalScopes()->where('name', $aliasName)->exists())->toBeTrue()
            ->and($preferences->get('test.float-delete-veto', $context))->toBe(1.0);
    } finally {
        Option::flushEventListeners();
        Option::clearBootedModels();
    }
});

test('a false non-float save veto rolls back storage and preserves the warmed value', function (PreferenceScope $scope) {
    registerPreference(new PreferenceDefinition(
        key: 'test.string-save-veto',
        type: PreferenceValueType::String,
        default: 'default',
        scopes: PreferenceScope::cases(),
    ));
    $user = preferenceGlobalAdmin();
    $context = preferenceContext($user, $user->currentTeam, null);
    $preferences = preferenceManager();
    $preferences->set('test.string-save-veto', 'before', $scope, $context, $user);
    $storedValue = Option::withoutGlobalScopes()->sole()->getRawOriginal('value');

    expect($preferences->get('test.string-save-veto', $context))->toBe('before');

    Option::updating(function (Option $option): bool {
        return ! str_contains((string) $option->getAttribute('name'), 'preference.v1.');
    });

    try {
        expect(fn () => $preferences->set(
            'test.string-save-veto',
            'after',
            $scope,
            $context,
            $user,
        ))->toThrow(RuntimeException::class, 'vetoed')
            ->and(Option::withoutGlobalScopes()->sole()->getRawOriginal('value'))->toBe($storedValue)
            ->and($preferences->get('test.string-save-veto', $context))->toBe('before');
    } finally {
        Option::flushEventListeners();
        Option::clearBootedModels();
    }
})->with([
    'user scope' => PreferenceScope::User,
    'team scope' => PreferenceScope::Team,
    'everyone scope' => PreferenceScope::Everyone,
]);

test('a false non-float restore veto leaves the stored option deleted', function (PreferenceScope $scope) {
    registerPreference(new PreferenceDefinition(
        key: 'test.string-restore-veto',
        type: PreferenceValueType::String,
        default: 'default',
        scopes: PreferenceScope::cases(),
    ));
    $user = preferenceGlobalAdmin();
    $context = preferenceContext($user, $user->currentTeam, null);
    $preferences = preferenceManager();
    $preferences->set('test.string-restore-veto', 'before', $scope, $context, $user);
    Option::withoutGlobalScopes()->sole()->deleteQuietly();
    Cache::flush();

    Option::restoring(function (Option $option): bool {
        return ! str_contains((string) $option->getAttribute('name'), 'preference.v1.');
    });

    try {
        expect(fn () => $preferences->set(
            'test.string-restore-veto',
            'after',
            $scope,
            $context,
            $user,
        ))->toThrow(RuntimeException::class, 'vetoed')
            ->and(Option::withoutGlobalScopes()->withTrashed()->sole()->trashed())->toBeTrue()
            ->and($preferences->get('test.string-restore-veto', $context))->toBe('default');
    } finally {
        Option::flushEventListeners();
        Option::clearBootedModels();
    }
})->with([
    'user scope' => PreferenceScope::User,
    'team scope' => PreferenceScope::Team,
    'everyone scope' => PreferenceScope::Everyone,
]);

test('a false reset deletion veto preserves non-float storage and cache', function (PreferenceScope $scope) {
    registerPreference(new PreferenceDefinition(
        key: 'test.string-reset-veto',
        type: PreferenceValueType::String,
        default: 'default',
        scopes: PreferenceScope::cases(),
    ));
    $user = preferenceGlobalAdmin();
    $context = preferenceContext($user, $user->currentTeam, null);
    $preferences = preferenceManager();
    $preferences->set('test.string-reset-veto', 'stored', $scope, $context, $user);

    expect($preferences->get('test.string-reset-veto', $context))->toBe('stored');

    Option::deleting(function (Option $option): bool {
        return ! str_contains((string) $option->getAttribute('name'), 'preference.v1.');
    });

    try {
        expect(fn () => $preferences->reset(
            'test.string-reset-veto',
            $scope,
            $context,
            $user,
        ))->toThrow(RuntimeException::class, 'vetoed')
            ->and(Option::withoutGlobalScopes()->count())->toBe(1)
            ->and($preferences->get('test.string-reset-veto', $context))->toBe('stored');
    } finally {
        Option::flushEventListeners();
        Option::clearBootedModels();
    }
})->with([
    'user scope' => PreferenceScope::User,
    'team scope' => PreferenceScope::Team,
    'everyone scope' => PreferenceScope::Everyone,
]);

test('a false alias force-delete veto rolls back a non-float user update', function () {
    registerPreference(new PreferenceDefinition(
        key: 'test.string-alias-veto',
        type: PreferenceValueType::String,
        default: 'default',
        scopes: [PreferenceScope::User],
    ));
    $user = preferenceGlobalAdmin();
    $context = preferenceContext($user, $user->currentTeam, null);
    $preferences = preferenceManager();
    $preferences->set('test.string-alias-veto', 'before', PreferenceScope::User, $context, $user);
    $canonical = Option::withoutGlobalScopes()->sole();
    $canonicalName = (string) $canonical->getRawOriginal('name');
    $storageKey = substr($canonicalName, strlen(User::optionNamePrefixFor($user->getKey())));
    $aliasName = 'user.'.$user->getKey().'.'.$storageKey;
    $aliasAttributes = $canonical->getAttributes();
    unset($aliasAttributes[$canonical->getKeyName()]);
    $aliasAttributes['name'] = $aliasName;
    DB::table($canonical->getTable())->insert($aliasAttributes);

    Option::deleting(fn (Option $option): bool => $option->getRawOriginal('name') !== $aliasName);

    try {
        expect(fn () => $preferences->set(
            'test.string-alias-veto',
            'after',
            PreferenceScope::User,
            $context,
            $user,
        ))->toThrow(RuntimeException::class, 'vetoed')
            ->and(Option::withoutGlobalScopes()->where('name', $aliasName)->exists())->toBeTrue()
            ->and($preferences->get('test.string-alias-veto', $context))->toBe('before');
    } finally {
        Option::flushEventListeners();
        Option::clearBootedModels();
    }
});

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
