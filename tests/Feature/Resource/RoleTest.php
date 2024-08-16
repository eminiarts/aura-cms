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
    $slug = new Role;

    $fields = collect($slug->getFields());

    expect($fields->firstWhere('slug', 'name'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'slug'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'description'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'super_admin'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'permissions'))->not->toBeNull();
});
