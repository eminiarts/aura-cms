<?php

use Aura\Base\ConditionalLogic;
use Aura\Base\Facades\Aura;
use Aura\Base\Models\Scopes\ScopedScope;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resource;
use Aura\Base\Resources\User;

/*
|--------------------------------------------------------------------------
| Octane support
|--------------------------------------------------------------------------
|
| Octane keeps a single PHP worker alive across many requests, so Aura's
| process-level static state must be reset on every request/task/tick. These
| tests cover:
|   (a) Aura::flushState() clears every request-scoped static.
|   (b) The service provider wires Aura::flushState() onto Octane's lifecycle
|       events (stubbed below when laravel/octane is not installed).
|   (c) Two consecutive simulated requests do not leak registrations.
|
*/

// Provide stub Octane event classes when laravel/octane is not installed so the
// service provider's class_exists()-guarded listener wiring activates on boot.
// These are empty markers; only their fully-qualified names matter. Declaring
// them here (at collection time) guarantees they exist before the application
// boots for each test in this file.
foreach (['RequestReceived', 'TaskReceived', 'TickReceived'] as $octaneEvent) {
    $fqcn = 'Laravel\\Octane\\Events\\'.$octaneEvent;

    if (! class_exists($fqcn)) {
        eval('namespace Laravel\\Octane\\Events; class '.$octaneEvent.' {}');
    }
}

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
    // Instantiate without invoking the constructor so this works both with the
    // stubbed marker classes and with real Octane events (whose constructors
    // require the worker, sandbox, request and response).
    return (new ReflectionClass('Laravel\\Octane\\Events\\'.$short))->newInstanceWithoutConstructor();
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
    // The stub events above make class_exists() pass, so the provider should
    // have registered a listener for each event during boot.
    $events = app('events');

    expect($events->hasListeners('Laravel\\Octane\\Events\\RequestReceived'))->toBeTrue();
    expect($events->hasListeners('Laravel\\Octane\\Events\\TaskReceived'))->toBeTrue();
    expect($events->hasListeners('Laravel\\Octane\\Events\\TickReceived'))->toBeTrue();

    // Dispatching the event must flush Aura state via the wired listener.
    Aura::registerResources(['App\\Leaky\\ViaEventResource']);
    expect(Aura::getResources())->toContain('App\\Leaky\\ViaEventResource');

    event(octaneEvent('RequestReceived'));

    expect(Aura::getResources())->not->toContain('App\\Leaky\\ViaEventResource');
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
