<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

it('has a searchable name', function () {
    $user = User::first();

    expect($user->getSearchableFields())->toHaveCount(2);
    expect($user->getSearchableFields()->pluck('slug')->toArray())->toMatchArray(['name', 'email']);
});

test('check User Fields', function () {
    $user = new User;

    $fields = collect($user->getFields());

    expect($fields->firstWhere('slug', 'avatar'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'name'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'email'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'roles'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'password'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'teams'))->not->toBeNull();
    expect($fields->firstWhere('slug', '2fa'))->not->toBeNull();
});

test('User isSuperAdmin()', function () {
    expect($this->user->isSuperAdmin())->toBeTrue();
});

test('User isSuperAdmin() false', function () {
    $user = User::factory()->create();
    expect($user->isSuperAdmin())->toBeFalse();
});

test('User roles()', function () {
    $role = Role::first();

    expect($this->user->roles->pluck('id')->toArray())->toBe([$role->id]);
});

test('User hasAnyRole()', function () {
    expect($this->user->hasAnyRole(['test', 'admin']))->toBeTrue();
    expect($this->user->hasAnyRole(['test']))->toBeFalse();
    expect($this->user->hasRole('test'))->toBeFalse();
    expect($this->user->hasRole('admin'))->toBeTrue();
});

test('User hasPermission()', function () {
    expect($this->user->hasPermission('test'))->toBeTrue();
});

test('User hasPermissionTo()', function () {
    expect($this->user->hasPermissionTo('test', Role::first()))->toBeTrue();
});

test('user uses custom table', function () {
    $user = new User;

    expect($user->usesCustomTable())->toBeTrue();
    expect($user->getTable())->toBe('users');
});

test('user uses meta for additional fields', function () {
    // User resource uses meta for additional dynamic fields
    expect(User::$usesMeta)->toBeTrue();
});

test('user has correct type and slug', function () {
    expect(User::getType())->toBe('User');
    expect(User::getSlug())->toBe('user');
});

test('user can be created with factory', function () {
    $user = User::factory()->create([
        'name' => 'Test User',
        'email' => 'testuser@example.com',
    ]);

    expect($user->name)->toBe('Test User');
    expect($user->email)->toBe('testuser@example.com');

    $this->assertDatabaseHas('users', [
        'name' => 'Test User',
        'email' => 'testuser@example.com',
    ]);
});

test('user can be updated', function () {
    $user = User::factory()->create([
        'name' => 'Original Name',
    ]);

    $user->update([
        'name' => 'Updated Name',
    ]);

    $user->refresh();

    expect($user->name)->toBe('Updated Name');
});

test('user title method returns name', function () {
    expect($this->user->title())->toContain($this->user->name);
});

test('user has actions defined', function () {
    $user = new User;

    $actions = $user->actions();
    expect($actions)->toBeArray();
});

test('user can have team relationship', function () {
    expect($this->user->currentTeam)->not->toBeNull();
});

test('user has current_team_id attribute', function () {
    expect($this->user->current_team_id)->not->toBeNull();
});
