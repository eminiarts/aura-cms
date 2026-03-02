<?php

use Aura\Base\Resources\Role;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

it('has a searchable name', function () {
    $role = Role::first();

    expect($role->getSearchableFields())->toHaveCount(1);
    expect($role->getSearchableFields()->pluck('slug')->toArray())->toBe(['name']);
});

test('check Role Fields', function () {
    $role = new Role;

    $fields = collect($role->getFields());

    expect($fields->firstWhere('slug', 'name'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'slug'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'description'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'super_admin'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'permissions'))->not->toBeNull();
});

test('role uses custom table', function () {
    $role = new Role;

    expect($role->usesCustomTable())->toBeTrue();
    expect($role->getTable())->toBe('roles');
});

test('role does not use meta', function () {
    expect(Role::$usesMeta)->toBeFalse();
});

test('role has correct type and slug', function () {
    expect(Role::getType())->toBe('Role');
    expect(Role::getSlug())->toBe('role');
});

test('role can be created', function () {
    $role = Role::create([
        'name' => 'Test Role',
        'slug' => 'test-role',
        'description' => 'A test role',
        'super_admin' => false,
        'team_id' => 1,
    ]);

    expect($role->name)->toBe('Test Role');
    expect($role->slug)->toBe('test-role');
    expect($role->description)->toBe('A test role');
    expect($role->super_admin)->toBeFalse();

    $this->assertDatabaseHas('roles', [
        'name' => 'Test Role',
        'slug' => 'test-role',
    ]);
});

test('role can be updated', function () {
    $role = Role::create([
        'name' => 'Original Role',
        'slug' => 'original-role',
        'team_id' => 1,
    ]);

    $role->update([
        'name' => 'Updated Role',
        'description' => 'New description',
    ]);

    $role->refresh();

    expect($role->name)->toBe('Updated Role');
    expect($role->description)->toBe('New description');
});

test('role permissions field is cast to array', function () {
    $role = Role::create([
        'name' => 'Permissions Role',
        'slug' => 'permissions-role',
        'permissions' => ['read', 'write', 'delete'],
        'team_id' => 1,
    ]);

    expect($role->permissions)->toBeArray();
    expect($role->permissions)->toContain('read');
    expect($role->permissions)->toContain('write');
    expect($role->permissions)->toContain('delete');
});

test('role super_admin is cast to boolean', function () {
    $role = Role::create([
        'name' => 'Admin Role',
        'slug' => 'admin-role',
        'super_admin' => true,
        'team_id' => 1,
    ]);

    expect($role->super_admin)->toBe(true);

    $role->update(['super_admin' => false]);
    $role->refresh();

    expect($role->super_admin)->toBe(false);
});

test('role title method returns formatted name', function () {
    $role = Role::create([
        'name' => 'Editor',
        'slug' => 'editor',
        'team_id' => 1,
    ]);

    expect($role->title())->toContain('Editor');
    expect($role->title())->toContain("#{$role->id}");
});

test('role has actions defined', function () {
    $role = new Role;

    expect($role->actions)->toBeArray();
    expect(array_key_exists('createMissingPermissions', $role->actions))->toBeTrue();
    expect(array_key_exists('delete', $role->actions))->toBeTrue();
});

test('role has bulk actions defined', function () {
    $role = new Role;

    expect($role->bulkActions)->toBeArray();
    expect($role->bulkActions)->toHaveKey('deleteSelected');
});
