<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

use function Pest\Livewire\livewire;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

describe('Team Resource Fields', function () {
    it('has a searchable name field', function () {
        $team = Team::first();

        expect($team->getSearchableFields())->toHaveCount(1);
        expect($team->getSearchableFields()->pluck('slug')->toArray())->toMatchArray(['name']);
    });

    it('has required name and description fields', function () {
        $team = new Team;
        $fields = collect($team->getFields());

        expect($fields->firstWhere('slug', 'name'))->not->toBeNull();
        expect($fields->firstWhere('slug', 'name')['validation'])->toBe('required');
        expect($fields->firstWhere('slug', 'description'))->not->toBeNull();
    });

    it('has users HasMany field with correct foreign key', function () {
        $team = new Team;
        $fields = collect($team->getFields());

        $usersField = $fields->firstWhere('slug', 'users');
        expect($usersField)->not->toBeNull();
        expect($usersField['type'])->toBe('Aura\\Base\\Fields\\HasMany');
        expect($usersField['foreign_key'])->toBe('team_id');
        expect($usersField['resource'])->toBe('Aura\\Base\\Resources\\User');
    });

    it('has tab structure for team and users', function () {
        $team = new Team;
        $fields = collect($team->getFields());

        expect($fields->firstWhere('slug', 'tab-team'))->not->toBeNull();
        expect($fields->firstWhere('slug', 'tab-users'))->not->toBeNull();
    });
});

describe('Team SoftDeletes', function () {
    it('uses soft deletes', function () {
        $team = Team::create([
            'name' => 'Soft Delete Test Team',
            'user_id' => $this->user->id,
        ]);

        $teamId = $team->id;
        $team->delete();

        expect(Team::find($teamId))->toBeNull();
        expect(Team::withTrashed()->find($teamId))->not->toBeNull();
        expect(Team::withTrashed()->find($teamId)->deleted_at)->not->toBeNull();
    });

    it('can be restored after soft delete', function () {
        $team = Team::create([
            'name' => 'Restore Test Team',
            'user_id' => $this->user->id,
        ]);

        $teamId = $team->id;
        $team->delete();

        expect(Team::find($teamId))->toBeNull();

        Team::withTrashed()->find($teamId)->restore();

        expect(Team::find($teamId))->not->toBeNull();
        expect(Team::find($teamId)->deleted_at)->toBeNull();
    });
});

describe('Team Creation Side Effects', function () {
    it('creates an admin role when team is created', function () {
        $team = Team::create([
            'name' => 'New Team With Role',
            'user_id' => $this->user->id,
        ]);

        $role = Role::withoutGlobalScopes()
            ->where('slug', 'admin')
            ->where('team_id', $team->id)
            ->first();

        expect($role)->not->toBeNull();
        expect($role->name)->toEqual('Admin');
        expect($role->super_admin)->toBeTrue();
        expect($role->permissions)->toBeArray();
    });

    it('sets the creator as team owner via user_id', function () {
        $team = Team::create([
            'name' => 'Owner Test Team',
        ]);

        expect($team->user_id)->toBe($this->user->id);
    });

    it('updates the user current_team_id when team is created', function () {
        $originalTeamId = $this->user->current_team_id;

        $team = Team::create([
            'name' => 'Current Team Test',
            'user_id' => $this->user->id,
        ]);

        $this->user->refresh();

        expect($this->user->current_team_id)->toBe($team->id);
        expect($this->user->current_team_id)->not->toBe($originalTeamId);
    });

    it('attaches the creator to the team with admin role', function () {
        $team = Team::create([
            'name' => 'Attachment Test Team',
            'user_id' => $this->user->id,
        ]);

        $pivotData = DB::table('user_role')
            ->where('team_id', $team->id)
            ->where('user_id', $this->user->id)
            ->first();

        expect($pivotData)->not->toBeNull();

        $role = Role::withoutGlobalScopes()
            ->where('team_id', $team->id)
            ->where('slug', 'admin')
            ->first();

        expect($pivotData->role_id)->toBe($role->id);
    });

    it('clears the user teams cache when team is created', function () {
        $cacheKey = 'user.'.$this->user->id.'.teams';

        Cache::put($cacheKey, 'test-value');
        expect(Cache::has($cacheKey))->toBeTrue();

        Team::create([
            'name' => 'Cache Clear Test',
            'user_id' => $this->user->id,
        ]);

        expect(Cache::has($cacheKey))->toBeFalse();
    });
});

describe('Team Relationships', function () {
    it('has many roles', function () {
        $team = Team::first();

        expect($team->roles())->toBeInstanceOf(HasMany::class);
        expect($team->roles->first())->toBeInstanceOf(Role::class);
    });

    it('has many team invitations', function () {
        $team = Team::first();

        expect($team->teamInvitations())->toBeInstanceOf(HasMany::class);
    });

    it('belongs to many users through user_role pivot', function () {
        $team = Team::first();

        expect($team->users())->toBeInstanceOf(BelongsToMany::class);
        expect($team->users->pluck('id')->toArray())->toContain($this->user->id);
    });
});

describe('Team Resource View', function () {
    it('displays users in the resource view', function () {
        // Create a Role
        $role = Role::create([
            'name' => 'Moderator',
            'slug' => 'moderator',
            'description' => 'Moderator',
            'super_admin' => false,
            'permissions' => [],
            'team_id' => Team::first()->id,
        ]);

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

        $component = livewire('aura::resource-view', [$team->id]);

        expect($component->viewFields)->toBeArray();

        // Check tab structure
        expect($component->viewFields[0]['fields'])->toHaveCount(2);
        expect($component->viewFields[0]['fields'][0]['name'])->toBe('Team');
        expect($component->viewFields[0]['fields'][1]['name'])->toBe('Users');

        // Check HasMany field configuration
        expect($component->viewFields[0]['fields'][1]['fields'][0]['type'])->toBe('Aura\Base\Fields\HasMany');
        expect($component->viewFields[0]['fields'][1]['fields'][0]['name'])->toBe('Users');
        expect($component->viewFields[0]['fields'][1]['fields'][0]['resource'])->toBe('Aura\Base\Resources\User');
    });
});

describe('Team Title and Icon', function () {
    it('returns the name as title', function () {
        $team = Team::first();

        expect($team->title())->toBe($team->name);
    });

    it('has a custom icon', function () {
        $team = new Team;

        expect($team->getIcon())->toBeString();
        expect($team->getIcon())->toContain('svg');
    });
});

describe('Team Static Properties', function () {
    it('uses custom table', function () {
        expect(Team::$customTable)->toBeTrue();
    });

    it('has correct slug', function () {
        expect(Team::$slug)->toBe('team');
    });

    it('has correct type', function () {
        expect(Team::$type)->toBe('Team');
    });

    it('uses meta', function () {
        expect(Team::$usesMeta)->toBeTrue();
    });

    it('disables global search', function () {
        expect(Team::$globalSearch)->toBeFalse();
    });
});

describe('Team Custom Permissions', function () {
    it('defines invite-users custom permission', function () {
        $team = new Team;
        $permissions = $team->customPermissions();

        expect($permissions)->toHaveKey('invite-users');
        expect($permissions['invite-users'])->toBe('Invite users to team');
    });
});
