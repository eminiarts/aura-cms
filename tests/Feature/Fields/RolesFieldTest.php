<?php

namespace Tests\Feature\Fields;

use Aura\Base\Fields\AdvancedSelect;
use Aura\Base\Fields\Roles;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

function rolesFieldDefinition(): array
{
    return [
        'name' => 'Role',
        'slug' => 'roles',
        'resource' => 'Aura\\Base\\Resources\\Role',
        'type' => 'Aura\\Base\\Fields\\Roles',
        'multiple' => false,
        'polymorphic_relation' => true,
    ];
}

describe('Roles Field Configuration', function () {
    test('extends AdvancedSelect and is a relation field', function () {
        $field = new Roles;

        expect($field)->toBeInstanceOf(AdvancedSelect::class)
            ->and($field->isRelation())->toBeTrue();
    });
});

describe('Roles Field Resolve', function () {
    test('display lists the assigned role names for a saved model', function () {
        $field = new Roles;

        // The super admin created in beforeEach carries the "Admin" role.
        expect($field->display(rolesFieldDefinition(), null, $this->user))->toBe('Admin');
    });

    test('display returns empty string for an unsaved model', function () {
        $field = new Roles;

        expect($field->display(rolesFieldDefinition(), null, new User))->toBe('');
    });

    test('getRelation returns a collection of roles for a saved model', function () {
        $field = new Roles;

        $roles = $field->getRelation($this->user, rolesFieldDefinition());

        expect($roles)->toHaveCount(1)
            ->and($roles->first())->toBeInstanceOf(Role::class);
    });

    test('getRelation returns an empty collection for an unsaved model', function () {
        $field = new Roles;

        expect($field->getRelation(new User, rolesFieldDefinition()))->toHaveCount(0);
    });
});

describe('Roles Field Attach', function () {
    test('saved attaches the requested role to the model', function () {
        $teamId = $this->user->current_team_id;

        // A plain (non super-admin) role in the acting team.
        $role = Role::withoutGlobalScope(TeamScope::class)->create([
            'type' => 'Role',
            'title' => 'Editor',
            'slug' => 'editor-role-field',
            'name' => 'Editor Role Field',
            'description' => 'Limited role.',
            'super_admin' => false,
            'permissions' => [],
            'user_id' => $this->user->id,
        ] + (config('aura.teams') ? ['team_id' => $teamId] : []));

        // A fresh user that belongs to the acting team.
        $target = User::factory()->create();
        if (config('aura.teams')) {
            $target->update(['current_team_id' => $teamId]);
        }
        $target->refresh();

        (new Roles)->saved($target, rolesFieldDefinition(), [$role->id]);

        expect($target->fresh()->roles()->pluck('roles.id'))->toContain($role->id);
    });
});
