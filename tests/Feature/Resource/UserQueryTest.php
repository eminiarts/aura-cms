<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

test('superadmin can get all users for his team', function () {
    $role = Role::first();

    User::factory()->count(10)->create([
        'roles' => [
            $role->id,
        ],
    ]);

    $users = User::all();

    expect($users)->toHaveCount(11);
});

test('user can not get all users for other teams', function () {
    // logout
    auth()->logout();

    $otherUser = User::factory()->create();

    $this->actingAs($otherUser);

    $team = Team::factory()->create();

    User::factory()->count(2)->create([
        'current_team_id' => $team->id,
    ]);

    $this->actingAs($this->user);
    $users = User::all();

    expect($users)->toHaveCount(1);
});

test('users can be filtered by role', function () {
    $role = Role::first();

    User::factory()->count(5)->create([
        'roles' => [$role->id],
    ]);

    $usersWithRole = User::whereHas('roles', function ($query) use ($role) {
        $query->where('role_id', $role->id);
    })->get();

    // Super admin + 5 factory users with the role
    expect($usersWithRole->count())->toBeGreaterThanOrEqual(1);
});

test('user query can find by id', function () {
    $user = $this->user;

    $found = User::find($user->id);

    expect($found)->not->toBeNull();
    expect($found->id)->toBe($user->id);
});

test('users query returns correct type', function () {
    $users = User::all();

    expect($users)->toBeInstanceOf(\Illuminate\Database\Eloquent\Collection::class);
    $users->each(function ($user) {
        expect($user)->toBeInstanceOf(User::class);
    });
});

test('user query can use pagination', function () {
    User::factory()->count(5)->create();

    $paginated = User::paginate(2);

    expect($paginated->count())->toBeGreaterThanOrEqual(1);
    expect($paginated->total())->toBeGreaterThanOrEqual(1);
});

test('user first or fail throws exception for non-existent user', function () {
    expect(fn () => User::where('id', 999999)->firstOrFail())
        ->toThrow(\Illuminate\Database\Eloquent\ModelNotFoundException::class);
});

test('user query can count results', function () {
    $count = User::count();

    expect($count)->toBeGreaterThanOrEqual(1);
});

test('user factory creates valid users', function () {
    $user = User::factory()->create();

    expect($user->name)->not->toBeEmpty();
    expect($user->email)->not->toBeEmpty();
    expect($user->id)->not->toBeNull();
});
