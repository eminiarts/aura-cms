<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

it('has a searchable name', function () {
    $role = User::first();

    expect($role->getSearchableFields())->toHaveCount(2);
    expect($role->getSearchableFields()->pluck('slug')->toArray())->toMatchArray(['name', 'email']);
});

test('check User Fields', function () {
    $slug = new User;

    $fields = collect($slug->getFields());

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
    expect($this->user->hasAnyRole(['test', 'super_admin']))->toBeTrue();
    expect($this->user->hasAnyRole(['test']))->toBeFalse();
    expect($this->user->hasRole('test'))->toBeFalse();
    expect($this->user->hasRole('super_admin'))->toBeTrue();
});

test('User hasPermission()', function () {
    expect($this->user->hasPermission('test'))->toBeTrue();
});

test('User hasPermissionTo()', function () {
    expect($this->user->hasPermissionTo('test', Role::first()))->toBeTrue();
});
