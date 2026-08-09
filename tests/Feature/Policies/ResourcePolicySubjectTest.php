<?php

use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Resource;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
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

test('viewAny accepts a resource class string as its policy subject', function () {
    $user = createSuperAdmin();

    expect(Gate::forUser($user)->allows('viewAny', Core05PolicySubjectResource::class))
        ->toBeTrue();
});

test('resource class strings and instances retain the same permission semantics', function () {
    $user = createAdmin();
    $user->roles()->first()->update([
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

test('team resource class strings use their package policy without an argument error', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('The Team policy is only registered when teams are enabled.');
    }

    $user = createGlobalAdmin();

    expect(Gate::forUser($user)->allows('viewAny', Team::class))->toBeTrue()
        ->and(Gate::forUser($user)->allows('create', Team::class))->toBeTrue();
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

    Gate::after(function () use (&$evaluations): void {
        $evaluations++;
    });

    expect(Gate::forUser($user)->allows('viewAny', Core05PolicySubjectResource::class))->toBeTrue()
        ->and($evaluations)->toBe(1);
});

test('class subject normalization preserves an inherited host policy before hook arguments', function () {
    $user = createSuperAdmin();
    Gate::policy(Core05PolicySubjectResource::class, Core05InheritedResourcePolicyWithBefore::class);

    expect(Gate::forUser($user)->allows('create', [Core05PolicySubjectResource::class, 'deny']))
        ->toBeFalse()
        ->and(Gate::forUser($user)->allows('create', [Core05PolicySubjectResource::class, 'allow']))
        ->toBeTrue();
});
