<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Permissions\PermissionRegistry;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\Gate;

test('custom permission definitions are stored untranslated in a singleton with normalized descriptions', function () {
    app('translator')->addLines(['features.view' => 'Translated evals'], 'en');

    Aura::registerPermissions([
        'box.evals.view' => ['name' => 'features.view', 'group' => 'Evals'],
        'Run-Evals' => ['name' => 'Run evals', 'group' => 'Evals', 'description' => 'Start an eval'],
    ]);

    expect(Aura::permissions()['box.evals.view'])->toBe([
        'name' => 'features.view', 'group' => 'Evals', 'description' => null,
    ])->and(Aura::permissions()['Run-Evals'])->toBe([
        'name' => 'Run evals', 'group' => 'Evals', 'description' => 'Start an eval',
    ])->and(app(PermissionRegistry::class))->toBe(app(PermissionRegistry::class))
        ->and(app(PermissionRegistry::class)->all())->toBe(Aura::permissions());
});

test('invalid custom permission definitions are rejected', function (string $key, array $definition) {
    expect(fn () => Aura::registerPermissions([$key => $definition]))
        ->toThrow(InvalidArgumentException::class);
})->with([
    'empty key' => ['', ['name' => 'View evals', 'group' => 'Evals']],
    'space in key' => ['box.evals view', ['name' => 'View evals', 'group' => 'Evals']],
    'tab in key' => ["box.evals\tview", ['name' => 'View evals', 'group' => 'Evals']],
    'wildcard key' => ['box.evals.*', ['name' => 'View evals', 'group' => 'Evals']],
    'missing name' => ['box.evals.view', ['group' => 'Evals']],
    'empty name' => ['box.evals.view', ['name' => '', 'group' => 'Evals']],
    'invalid name' => ['box.evals.view', ['name' => 42, 'group' => 'Evals']],
    'missing group' => ['box.evals.view', ['name' => 'View evals']],
    'empty group' => ['box.evals.view', ['name' => 'View evals', 'group' => ' ']],
    'invalid group' => ['box.evals.view', ['name' => 'View evals', 'group' => 42]],
    'invalid description' => ['box.evals.view', ['name' => 'View evals', 'group' => 'Evals', 'description' => 42]],
]);

test('a custom permission cannot be registered twice', function () {
    $permissions = ['box.evals.view' => ['name' => 'View evals', 'group' => 'Evals']];
    Aura::registerPermissions($permissions);

    expect(fn () => Aura::registerPermissions($permissions))->toThrow(InvalidArgumentException::class);
});

test('a custom permission cannot replace an existing Gate', function () {
    Gate::define('box.evals.view', fn (object $user): bool => false);

    expect(fn () => Aura::registerPermissions([
        'box.evals.view' => ['name' => 'View evals', 'group' => 'Evals'],
    ]))->toThrow(InvalidArgumentException::class);
});

test('custom permission Gates check the effective role grant', function (bool $granted) {
    $user = createAdmin();
    $user->roles->first()->update(['permissions' => $granted ? ['box.evals.view' => true] : []]);

    Aura::registerPermissions([
        'box.evals.view' => ['name' => 'View evals', 'group' => 'Evals'],
    ]);

    expect($user->can('box.evals.view'))->toBe($granted);
})->with([true, false]);

test('a Super Admin passes a custom permission Gate without an explicit grant', function () {
    $user = createSuperAdmin();
    Aura::registerPermissions([
        'box.evals.view' => ['name' => 'View evals', 'group' => 'Evals'],
    ]);

    expect($user->can('box.evals.view'))->toBeTrue();
});

test('a Global Admin passes a custom permission Gate without a role', function () {
    $user = User::factory()->create();
    Gate::define(User::GLOBAL_ADMIN_GATE, fn (object $candidate): bool => $candidate->id === $user->id);
    Aura::registerPermissions([
        'box.evals.view' => ['name' => 'View evals', 'group' => 'Evals'],
    ]);

    expect($user->hasPermission('box.evals.view'))->toBeFalse()
        ->and($user->can('box.evals.view'))->toBeTrue();
});

test('an unregistered key denies even when its grant is stored', function () {
    $user = createAdmin();
    $role = $user->roles->first();
    $role->update(['permissions' => ['box.evals.view' => true]]);

    expect($user->hasPermission('box.evals.view'))->toBeTrue()
        ->and($user->can('box.evals.view'))->toBeFalse()
        ->and($role->fresh()->permissions)->toBe(['box.evals.view' => true]);
});

test('guests are denied custom permission Gates', function () {
    Aura::registerPermissions([
        'box.evals.view' => ['name' => 'View evals', 'group' => 'Evals'],
    ]);

    expect(Gate::forUser(null)->allows('box.evals.view'))->toBeFalse();
});

test('team invitation permission is registered only when teams are enabled', function () {
    expect(array_key_exists('invite-users-team', Aura::permissions()))->toBe((bool) config('aura.teams'))
        ->and(Gate::has('invite-users-team'))->toBe((bool) config('aura.teams'));
});
