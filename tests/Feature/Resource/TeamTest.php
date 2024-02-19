<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

it('has a searchable name', function () {
    $role = Team::first();

    expect($role->getSearchableFields())->toHaveCount(1);
    expect($role->getSearchableFields()->pluck('slug')->toArray())->toMatchArray(['name']);
});

test('check Team Fields', function () {
    $slug = new Team();

    $fields = collect($slug->getFields());

    expect($fields->firstWhere('slug', 'name'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'description'))->not->toBeNull();
});

test('Team uses SoftDeletes', function () {
    $team = new Team();
    $team->name = 'Test Team';
    $team->save();

    $teamId = $team->id;

    // Soft delete the team
    $team->delete();

    // Assert the team is not accessible through a regular query anymore
    expect(Team::find($teamId))->toBeNull();

    // Assert the team is still in the database and accessible through a withTrashed query
    expect(Team::withTrashed()->find($teamId))->not->toBeNull();
});
