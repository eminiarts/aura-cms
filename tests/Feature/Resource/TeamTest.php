<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\DB;
use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

it('has a searchable name', function () {
    $role = Team::first();

    expect($role->getSearchableFields())->toHaveCount(1);
    expect($role->getSearchableFields()->pluck('slug')->toArray())->toMatchArray(['name']);
});

test('check Team Fields', function () {
    $slug = new Team;

    $fields = collect($slug->getFields());

    expect($fields->firstWhere('slug', 'name'))->not->toBeNull();
    expect($fields->firstWhere('slug', 'description'))->not->toBeNull();
});

test('Team uses SoftDeletes', function () {
    $team = new Team;
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

test('Team create also creates a super_admin Role', function () {
    $team = new Team;
    $team->name = 'Test Team';
    $team->save();

    $role = Role::where('slug', 'super_admin')->where('team_id', $team->id)->first();

    expect($role)->not->toBeNull();
    expect($role->name)->toEqual('Super Admin');
    expect($role->super_admin)->toBeTrue();
});


test('team users are displayed in the resource view', function () {

    // Create a Role
    $role = Role::create([
        'name' => 'Moderator',
        'slug' => 'moderator',
        'description' => 'Moderator',
        'super_admin' => false,
        'permissions' => [],
        'team_id' => Team::first()->id,
    ]);

    ray()->clearScreen();

    // Create a User
    $user = User::factory()->create();

    $user->update(['roles' => [$role->id]]);

    expect($user->hasRole('moderator'))->toBeTrue();

    expect(Role::count())->toBe(2);

    expect(User::count())->toBe(2);

    $db = DB::table('user_role')->where('team_id', Team::first()->id)->get();

    expect($db)->toHaveCount(2);

    $team = Team::first();

    Aura::fake();
    Aura::setModel($team);

    // LiveWire Component
    $component = livewire('aura::resource-view', [$team->id]);

    // Expect $component->viewFields to be an array
    expect($component->viewFields)->toBeArray();

    ray($component->viewFields)->blue();

    // Check if there are exactly 2 tabs
    expect($component->viewFields[0]['fields'])->toHaveCount(2);

    // Check if first tab is named "Team"
    expect($component->viewFields[0]['fields'][0]['name'])->toBe('Team');

    // Check if second tab is named "Users"
    expect($component->viewFields[0]['fields'][1]['name'])->toBe('Users');

    // Check if second tab has HasMany field
    expect($component->viewFields[0]['fields'][1]['fields'][0]['type'])->toBe('Aura\Base\Fields\HasMany');
    expect($component->viewFields[0]['fields'][1]['fields'][0]['name'])->toBe('Users');
    expect($component->viewFields[0]['fields'][1]['fields'][0]['resource'])->toBe('Aura\Base\Resources\User');

});