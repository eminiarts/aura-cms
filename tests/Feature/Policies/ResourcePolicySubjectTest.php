<?php

use Aura\Base\BaseResource;
use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Policies\TeamPolicy;
use Aura\Base\Resource;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Auth\Access\Events\GateEvaluated;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;

class Core05PolicySubjectResource extends Resource
{
    public static ?string $slug = 'core05-policy-subject';

    public static string $type = 'Core05PolicySubject';

    public static function getFields(): array
    {
        return [];
    }
}

class Core05SpecificResourcePolicy
{
    public function create(User $user, int $categoryId): bool
    {
        return $user->exists && $categoryId === 7;
    }

    public function update(User $user, Core05PolicySubjectResource $resource): bool
    {
        return $user->exists && $resource->exists;
    }

    public function viewAny(User $user): bool
    {
        return $user->exists;
    }
}

class Core05InheritedResourcePolicyWithBefore extends ResourcePolicy
{
    public function before(User $user, string $ability, mixed $subject, mixed $context = null): ?bool
    {
        if ($ability === 'create' && is_string($subject) && $context === 'deny') {
            return false;
        }

        return null;
    }
}

class Core05PolicySubjectBaseResource extends BaseResource
{
    public static ?string $slug = 'core05-policy-subject-base';

    public static string $type = 'Core05PolicySubjectBase';

    public static function getFields(): array
    {
        return [];
    }
}

class Core05OverriddenResourcePolicy extends ResourcePolicy
{
    public function create($user, $resource): bool
    {
        return $user->exists && $resource instanceof Core05PolicySubjectResource;
    }

    public function viewAny($user, $resource): bool
    {
        return $user->exists && $resource instanceof Core05PolicySubjectResource;
    }
}

class Core05ContextResourcePolicy extends ResourcePolicy
{
    public function create($user, $resource, string $context = ''): bool
    {
        return $user->exists
            && $resource instanceof Core05PolicySubjectResource
            && $context === 'expected-context';
    }

    public function viewAny($user, $resource, string $context = ''): bool
    {
        return $user->exists
            && $resource instanceof Core05PolicySubjectResource
            && $context === 'expected-context';
    }
}

trait Core05ResourcePolicyMethods
{
    public function create($user, $resource): bool
    {
        return $user->exists && $resource instanceof Core05PolicySubjectResource;
    }

    public function viewAny($user, $resource): bool
    {
        return $user->exists && $resource instanceof Core05PolicySubjectResource;
    }
}

class Core05TraitResourcePolicy extends ResourcePolicy
{
    use Core05ResourcePolicyMethods;
}

class Core05DelegatingResourcePolicy extends ResourcePolicy
{
    public function create($user, $resource): bool
    {
        return $this->authorizeAuraSubject($user, $resource);
    }

    public function viewAny($user, $resource): bool
    {
        return $this->authorizeAuraSubject($user, $resource);
    }

    protected function authorizeAuraSubject($user, $resource): bool
    {
        return $user->exists && $resource instanceof Core05PolicySubjectResource;
    }
}

class Core05ProxiedResourcePolicy extends Core05DelegatingResourcePolicy {}

class Core05OverriddenTeamPolicy extends TeamPolicy
{
    public function create(User $user, $team): bool
    {
        return $user->exists && $team instanceof Team;
    }

    public function viewAny(User $user, Team $team): bool
    {
        return $user->exists;
    }
}

test('viewAny accepts a resource class string as its policy subject', function () {
    $user = createSuperAdmin();

    expect(Gate::forUser($user)->allows('viewAny', Core05PolicySubjectResource::class))
        ->toBeTrue();
});

test('resource class strings and instances retain the same permission semantics', function () {
    $user = createAdmin();
    $this->actingAs($user);
    $user->roles()->firstOrFail()->update([
        'permissions' => [
            'viewAny-core05-policy-subject' => true,
            'create-core05-policy-subject' => false,
        ],
    ]);
    $user->refresh();

    expect(Gate::forUser($user)->allows('viewAny', Core05PolicySubjectResource::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('viewAny', new Core05PolicySubjectResource))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', Core05PolicySubjectResource::class))->toBeFalse()
        ->and(Gate::forUser($user)->allows('create', new Core05PolicySubjectResource))->toBeFalse();
});

test('overridden inherited trait and proxied Aura policy methods receive resource subjects', function (string $policy) {
    $user = createSuperAdmin();
    Gate::policy(Core05PolicySubjectResource::class, $policy);

    expect(Gate::forUser($user)->allows('viewAny', Core05PolicySubjectResource::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('viewAny', new Core05PolicySubjectResource))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', Core05PolicySubjectResource::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', new Core05PolicySubjectResource))->toBeTrue();
})->with([
    'overridden methods' => Core05OverriddenResourcePolicy::class,
    'trait methods' => Core05TraitResourcePolicy::class,
    'proxied inherited methods' => Core05ProxiedResourcePolicy::class,
]);

test('overridden Aura policy methods retain trailing context after class and instance subjects', function () {
    $user = createSuperAdmin();
    Gate::policy(Core05PolicySubjectResource::class, Core05ContextResourcePolicy::class);
    $resource = new Core05PolicySubjectResource;

    expect(Gate::forUser($user)->allows('viewAny', [Core05PolicySubjectResource::class, 'expected-context']))->toBeTrue()
        ->and(Gate::forUser($user)->allows('viewAny', [$resource, 'expected-context']))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', [Core05PolicySubjectResource::class, 'expected-context']))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', [$resource, 'expected-context']))->toBeTrue();
});

test('BaseResource class strings use an explicitly mapped Aura resource policy', function () {
    $user = createSuperAdmin();
    Gate::policy(Core05PolicySubjectBaseResource::class, ResourcePolicy::class);

    expect(Gate::forUser($user)->allows('viewAny', Core05PolicySubjectBaseResource::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('viewAny', new Core05PolicySubjectBaseResource))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', Core05PolicySubjectBaseResource::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', new Core05PolicySubjectBaseResource))->toBeTrue();
});

test('team resource class strings use their package policy without an argument error', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('The Team policy is only registered when teams are enabled.');
    }

    $user = createGlobalAdmin();

    expect(Gate::forUser($user)->allows('viewAny', Team::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', Team::class))->toBeTrue();
});

test('overridden team policy methods receive class and instance subjects in every team mode', function () {
    $user = createGlobalAdmin();
    Gate::policy(Team::class, Core05OverriddenTeamPolicy::class);

    expect(Gate::forUser($user)->allows('viewAny', Team::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('viewAny', new Team))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', Team::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', new Team))->toBeTrue();
});

test('class subject normalization preserves explicit policy arguments and resolution', function () {
    $user = createSuperAdmin();
    Gate::policy(Core05PolicySubjectResource::class, Core05SpecificResourcePolicy::class);

    $resource = Core05PolicySubjectResource::create(['title' => 'Policy instance']);

    expect(Gate::forUser($user)->allows('viewAny', Core05PolicySubjectResource::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('viewAny', new Core05PolicySubjectResource))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', [Core05PolicySubjectResource::class, 7]))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', [Core05PolicySubjectResource::class, 8]))->toBeFalse()
        ->and(Gate::forUser($user)->allows('update', $resource))->toBeTrue();
});

test('class subject normalization evaluates gate after callbacks once', function () {
    $user = createSuperAdmin();
    $evaluations = 0;
    $evaluatedSubject = null;
    Gate::policy(Core05PolicySubjectResource::class, Core05OverriddenResourcePolicy::class);

    Gate::after(function ($user, string $ability, mixed $result, array $arguments) use (&$evaluations, &$evaluatedSubject): void {
        $evaluations++;
        $evaluatedSubject = $arguments[0];
    });

    expect(Gate::forUser($user)->allows('viewAny', Core05PolicySubjectResource::class))->toBeTrue()
        ->and($evaluations)->toBe(1)
        ->and($evaluatedSubject)->toBe(Core05PolicySubjectResource::class);
});

test('class subject normalization dispatches one gate evaluated event', function () {
    $user = createSuperAdmin();
    Gate::policy(Core05PolicySubjectResource::class, Core05OverriddenResourcePolicy::class);
    Event::fake([GateEvaluated::class]);

    expect(Gate::forUser($user)->allows('viewAny', Core05PolicySubjectResource::class))->toBeTrue();

    Event::assertDispatchedTimes(GateEvaluated::class, 1);
    Event::assertDispatched(
        GateEvaluated::class,
        fn (GateEvaluated $event): bool => $event->arguments[0] === Core05PolicySubjectResource::class,
    );
});

test('a later host gate before callback can deny a normalized class subject', function () {
    $user = createSuperAdmin();
    $evaluations = [];
    Gate::policy(Core05PolicySubjectResource::class, Core05OverriddenResourcePolicy::class);

    Gate::before(function ($user, string $ability, array $arguments) use (&$evaluations): null {
        $evaluations[] = 'early-null';

        expect($arguments[0])->toBe(Core05PolicySubjectResource::class);

        return null;
    });
    Gate::before(function ($user, string $ability, array $arguments) use (&$evaluations): bool {
        $evaluations[] = 'late-deny';

        expect($arguments[0])->toBe(Core05PolicySubjectResource::class);

        return false;
    });

    expect(Gate::forUser($user)->allows('viewAny', Core05PolicySubjectResource::class))->toBeFalse()
        ->and($evaluations)->toBe(['early-null', 'late-deny']);
});

test('host gate before callbacks preserve first non-null ordering', function (
    bool $earlyResult,
    bool $lateResult,
    bool $expected,
) {
    $user = createSuperAdmin();
    $evaluations = [];

    Gate::before(function () use (&$evaluations, $earlyResult): bool {
        $evaluations[] = 'early';

        return $earlyResult;
    });
    Gate::before(function () use (&$evaluations, $lateResult): bool {
        $evaluations[] = 'late';

        return $lateResult;
    });

    expect(Gate::forUser($user)->allows('viewAny', Core05PolicySubjectResource::class))->toBe($expected)
        ->and($evaluations)->toBe(['early']);
})->with([
    'early allow stops late deny' => [true, false, true],
    'early deny stops late allow' => [false, true, false],
]);

test('class subject normalization preserves an inherited host policy before hook arguments', function () {
    $user = createSuperAdmin();
    Gate::policy(Core05PolicySubjectResource::class, Core05InheritedResourcePolicyWithBefore::class);

    expect(Gate::forUser($user)->allows('create', [Core05PolicySubjectResource::class, 'deny']))
        ->toBeFalse()
        ->and(Gate::forUser($user)->allows('create', [Core05PolicySubjectResource::class, 'allow']))
        ->toBeTrue();
});
