<?php

namespace Tests\Feature\Fields;

use Aura\Base\Facades\Aura;
use Aura\Base\Fields\Permissions;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\Role;

use function Pest\Livewire\livewire;

describe('Permissions Field Configuration', function () {
    test('has correct edit and view properties', function () {
        $field = new Permissions;

        expect($field->edit)->toBe('aura::fields.permissions')
            ->and($field->view)->toBe('aura::fields.permissions-view');
    });

    test('has resource configuration field', function () {
        $fields = collect((new Permissions)->getFields());

        expect($fields->firstWhere('slug', 'resource'))->not->toBeNull();
    });
});

describe('Custom Permissions in the Role Form', function () {
    beforeEach(function () {
        createSuperAdmin();
    });

    test('features render translated groups names descriptions and literal keys', function () {
        app('translator')->addLines([
            'features.group' => 'Translated evals',
            'features.view' => 'Translated view',
            'features.description' => 'Translated description',
        ], 'en');

        Aura::registerPermissions([
            'box.evals.view' => ['name' => 'features.view', 'group' => 'features.group', 'description' => 'features.description'],
        ]);

        livewire(Create::class, ['slug' => 'role'])
            ->assertSee('Features')
            ->assertSee('Translated evals')
            ->assertSee('Translated view')
            ->assertSee('box.evals.view')
            ->assertSeeHtml('title="Translated description"');
    });

    test('registered definitions replace database rows with the same slug in the matrix', function () {
        Permission::create(['name' => 'Legacy invite permission', 'slug' => 'invite-users-team', 'group' => 'Legacy team']);

        if (! config('aura.teams')) {
            Aura::registerPermissions([
                'invite-users-team' => ['name' => 'Invite users to team', 'group' => 'Team'],
            ]);
        }

        $form = livewire(Create::class, ['slug' => 'role'])
            ->assertSee('Invite users to team')
            ->assertDontSee('Legacy invite permission')
            ->assertDontSee('Legacy team');

        expect(substr_count($form->html(), 'id="permissions_invite-users-team"'))->toBe(1);
    });

    test('feature groups and resource groups have separate Alpine toggle keys', function () {
        Permission::create(['name' => 'View Posts', 'slug' => 'view-post', 'group' => 'Posts']);
        Aura::registerPermissions([
            'box.posts.export' => ['name' => 'Export posts', 'group' => 'Posts'],
        ]);

        livewire(Create::class, ['slug' => 'role'])
            ->assertSeeHtml('feature:Posts')
            ->assertSeeHtml('resource:Posts')
            ->assertSeeHtml('id="permissions_box.posts.export"')
            ->assertSeeHtml('id="permissions_view-post"');
    });

    test('saving a role preserves dotted permission keys literally', function () {
        Aura::registerPermissions([
            'box.evals.view' => ['name' => 'View evals', 'group' => 'Evals'],
        ]);

        livewire(Create::class, ['slug' => 'role'])
            ->set('form.fields.name', 'Eval Reviewer')
            ->set('form.fields.slug', 'eval-reviewer')
            ->set('form.fields.permissions', ['box.evals.view' => true])
            ->call('save')
            ->assertHasNoErrors();

        $role = Role::where('slug', 'eval-reviewer')->firstOrFail();

        expect($role->getAttribute('permissions'))->toBe(['box.evals.view' => true])
            ->and(json_decode($role->getRawOriginal('permissions'), true))->toBe(['box.evals.view' => true]);
    });

    test('team invitations appear without a permission database row', function () {
        expect(Permission::where('slug', 'invite-users-team')->exists())->toBeFalse();

        livewire(Create::class, ['slug' => 'role'])
            ->assertSee('Features')
            ->assertSee('Team')
            ->assertSee('Invite users to team')
            ->assertSeeHtml('id="permissions_invite-users-team"');
    })->skip(fn () => ! config('aura.teams'), 'Requires teams feature');

    test('no Features section renders when no permissions are registered', function () {
        expect(Aura::permissions())->toBe([]);

        livewire(Create::class, ['slug' => 'role'])
            ->assertDontSee('Features');
    })->skip(fn () => config('aura.teams'), 'Teams registers its invitation permission');
});

describe('Permissions Field Value Handling', function () {
    test('get decodes JSON strings into arrays', function () {
        $field = new Permissions;

        expect($field->get(null, '{"view-post":true}'))->toBe(['view-post' => true]);
    });

    test('get returns arrays unchanged', function () {
        $field = new Permissions;

        expect($field->get(null, ['view-post' => true]))->toBe(['view-post' => true]);
    });

    test('set encodes value as JSON', function () {
        $field = new Permissions;

        expect($field->set(null, [], ['view-post' => true]))->toBe('{"view-post":true}');
    });

    test('round-trips permissions through set then get', function () {
        $field = new Permissions;
        $value = ['view-post' => true, 'delete-post' => false];

        expect($field->get(null, $field->set(null, [], $value)))->toBe($value);
    });
});

describe('Permissions Field Matrix', function () {
    test('puts generated abilities in their column and custom permissions in other', function () {
        $rows = Permissions::matrix([
            ['name' => 'View Posts', 'description' => null, 'slug' => 'view-post', 'group' => 'Posts'],
            ['name' => 'View Any Posts', 'description' => null, 'slug' => 'viewAny-post', 'group' => 'Posts'],
            ['name' => 'Scope Posts', 'description' => null, 'slug' => 'scope-post', 'group' => 'Posts'],
            ['name' => 'Export Posts', 'description' => null, 'slug' => 'export-post', 'group' => 'Posts'],
        ]);

        expect(array_keys($rows))->toBe(['Posts'])
            ->and($rows['Posts']['cells']['view']['slug'])->toBe('view-post')
            ->and($rows['Posts']['cells']['viewAny']['slug'])->toBe('viewAny-post')
            ->and($rows['Posts']['cells']['scope']['slug'])->toBe('scope-post')
            ->and($rows['Posts']['cells']['delete'])->toBeNull()
            ->and(array_column($rows['Posts']['other'], 'slug'))->toBe(['export-post']);
    });

    test('role form keeps custom permissions assignable next to the matrix', function () {
        $this->actingAs(createSuperAdmin());

        Permission::create(['name' => 'View Posts', 'slug' => 'view-post', 'group' => 'Posts']);
        Permission::create(['name' => 'Export Posts', 'slug' => 'export-post', 'group' => 'Posts']);

        livewire(Create::class, ['slug' => 'role'])
            ->assertSeeHtml('id="permissions_view-post"')
            ->assertSeeHtml('id="permissions_export-post"');
    });
});
