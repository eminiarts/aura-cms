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
    $slug = new User();

    $fields = collect($slug->getFields());

    expect($fields->firstWhere('slug', 'avatar'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'name'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'email'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'roles'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'password'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'teams'))->not->toBeNull();
    expect($fields->firstWhere('slug', '2fa'))->not->toBeNull();
});
