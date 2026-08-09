<?php

use Aura\Base\ConditionalLogic;
use Aura\Base\Facades\Aura;
use Aura\Base\Models\Scopes\ScopedScope;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Laravel\Octane\Events\RequestHandled;
use Laravel\Octane\Events\RequestReceived;
use Laravel\Octane\Events\RequestTerminated;
use Laravel\Octane\Events\TaskReceived;
use Laravel\Octane\Events\TaskTerminated;
use Laravel\Octane\Events\TickReceived;
use Laravel\Octane\Events\TickTerminated;
use Laravel\Octane\Events\WorkerErrorOccurred;
use Symfony\Component\HttpFoundation\Response;

/*
|--------------------------------------------------------------------------
| Octane support
|--------------------------------------------------------------------------
|
| Octane keeps a single PHP worker alive across many requests, so Aura's
| process-level static state must be reset on every request/task/tick. These
| tests cover:
|   (a) Aura::flushState() clears every request-scoped static.
|   (b) The service provider clears authentication and Aura state on Octane's
|       real lifecycle event classes.
|   (c) Two consecutive simulated requests do not leak registrations.
|
*/

function readStatic(string $class, string $property): mixed
{
    $reflection = new ReflectionProperty($class, $property);
    $reflection->setAccessible(true);

    return $reflection->getValue();
}

function seedStatic(string $class, string $property, mixed $value): void
{
    $reflection = new ReflectionProperty($class, $property);
    $reflection->setAccessible(true);
    $reflection->setValue(null, $value);
}

function octaneEvent(string $short): object
{
    $application = app();
    $request = Request::create('/octane-boundary');
    $response = new Response;

    return match ($short) {
        'RequestReceived' => new RequestReceived($application, $application, $request),
        'RequestHandled' => new RequestHandled($application, $request, $response),
        'RequestTerminated' => new RequestTerminated($application, $application, $request, $response),
        'TaskReceived' => new TaskReceived($application, $application, fn () => null),
        'TaskTerminated' => new TaskTerminated($application, $application, fn () => null, null),
        'TickReceived' => new TickReceived($application, $application),
        'TickTerminated' => new TickTerminated($application, $application),
        'WorkerErrorOccurred' => new WorkerErrorOccurred(new RuntimeException('Octane boundary'), $application),
        default => throw new InvalidArgumentException("Unknown Octane event [{$short}]."),
    };
}

test('flushState clears request-scoped process statics', function () {
    // Populate the field caches through normal resource usage.
    $post = createPost();
    $post->fieldsCollection();
    $post->fieldBySlug('title');
    $post->fieldClassBySlug('title');
    $post->inputFieldsSlugs();
    $post->mappedFields();

    // Populate the Aura singleton registrations and the user model.
    Aura::registerResources(['App\\Leaky\\LeakyResource']);
    Aura::useUserModel('App\\Models\\LeakyUser');

    // Seed the harder-to-drive caches directly so we can prove they are reset.
    seedStatic(ConditionalLogic::class, 'shouldDisplayFieldCache', ['dummy' => true]);
    seedStatic(TeamScope::class, 'applying', true);
    seedStatic(ScopedScope::class, 'decisionCache', new WeakMap);

    // Sanity: state is actually populated before the flush.
    expect(readStatic(Resource::class, 'fieldsCollectionCache'))->not->toBeEmpty();
    expect(readStatic(Resource::class, 'fieldsBySlug'))->not->toBeEmpty();
    expect(Aura::getResources())->toContain('App\\Leaky\\LeakyResource');
    expect(Aura::userModel())->toBe('App\\Models\\LeakyUser');

    Aura::flushState();

    // Field caches (src/Traits/InputFieldsHelpers.php).
    expect(readStatic(Resource::class, 'fieldClassesBySlug'))->toBe([]);
    expect(readStatic(Resource::class, 'fieldsBySlug'))->toBe([]);
    expect(readStatic(Resource::class, 'fieldsCollectionCache'))->toBe([]);
    expect(readStatic(Resource::class, 'inputFieldSlugs'))->toBe([]);
    expect(readStatic(Resource::class, 'mappedFields'))->toBe([]);

    // Conditional-logic cache (src/ConditionalLogic.php).
    expect(readStatic(ConditionalLogic::class, 'shouldDisplayFieldCache'))->toBe([]);

    // Scope statics.
    expect(readStatic(TeamScope::class, 'applying'))->toBeFalse();
    expect(readStatic(ScopedScope::class, 'decisionCache'))->toBeNull();

    // User model and singleton registrations reset to the boot baseline.
    expect(Aura::userModel())->toBe(User::class);
    expect(Aura::getResources())->not->toContain('App\\Leaky\\LeakyResource');
});

test('the service provider wires flushState onto the octane request lifecycle', function () {
    $events = app('events');

    foreach ([
        RequestReceived::class,
        RequestHandled::class,
        RequestTerminated::class,
        TaskReceived::class,
        TaskTerminated::class,
        TickReceived::class,
        TickTerminated::class,
        WorkerErrorOccurred::class,
    ] as $octaneEvent) {
        expect($events->hasListeners($octaneEvent))->toBeTrue();
    }

    // Dispatching the event must flush Aura state via the wired listener.
    Aura::registerResources(['App\\Leaky\\ViaEventResource']);
    expect(Aura::getResources())->toContain('App\\Leaky\\ViaEventResource');

    event(octaneEvent('RequestReceived'));

    expect(Aura::getResources())->not->toContain('App\\Leaky\\ViaEventResource');
});

test('octane received boundaries cannot inherit an authenticated caller', function () {
    $user = createSuperAdmin();
    Auth::logout();
    Auth::forgetGuards();

    foreach (['RequestReceived', 'TaskReceived', 'TickReceived'] as $octaneEvent) {
        Auth::setUser($user);
        Aura::registerResources(['App\\Leaky\\'.$octaneEvent]);

        event(octaneEvent($octaneEvent));

        expect(Auth::id())->toBeNull()
            ->and(Aura::getResources())->not->toContain('App\\Leaky\\'.$octaneEvent);
    }
});

test('octane completion and error boundaries clear authentication and aura state', function () {
    $user = createSuperAdmin();
    Auth::logout();
    Auth::forgetGuards();

    foreach ([
        'RequestHandled',
        'RequestTerminated',
        'TaskTerminated',
        'TickTerminated',
        'WorkerErrorOccurred',
    ] as $octaneEvent) {
        Auth::setUser($user);
        Aura::registerResources(['App\\Leaky\\'.$octaneEvent]);

        event(octaneEvent($octaneEvent));

        expect(Auth::id())->toBeNull()
            ->and(Aura::getResources())->not->toContain('App\\Leaky\\'.$octaneEvent);
    }
});

test('two consecutive simulated octane requests do not leak registrations or fields', function () {
    $baseline = Aura::getResources();

    // --- Request 1 (e.g. Team A) ---
    Aura::registerResources(['App\\TeamA\\SecretResource']);
    createPost()->fieldsCollection();

    expect(Aura::getResources())->toContain('App\\TeamA\\SecretResource');
    expect(readStatic(Resource::class, 'fieldsCollectionCache'))->not->toBeEmpty();

    // Worker boundary: Octane fires RequestReceived -> Aura::flushState().
    event(octaneEvent('RequestReceived'));

    expect(Aura::getResources())->toEqualCanonicalizing($baseline);
    expect(readStatic(Resource::class, 'fieldsCollectionCache'))->toBe([]);

    // --- Request 2 (e.g. Team B) ---
    Aura::registerResources(['App\\TeamB\\OtherResource']);

    // Request 1's registration must not have leaked into request 2.
    expect(Aura::getResources())->toContain('App\\TeamB\\OtherResource');
    expect(Aura::getResources())->not->toContain('App\\TeamA\\SecretResource');

    event(octaneEvent('RequestReceived'));

    expect(Aura::getResources())->toEqualCanonicalizing($baseline);
});
