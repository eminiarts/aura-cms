<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Resources\Option;
use Aura\Base\Resources\Permission;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;

beforeEach(function () {
    config(['aura.teams' => false]);

    $this->artisan('migrate:fresh');

    $migration = require __DIR__.'/../../database/migrations/create_aura_tables.php.stub';
    $migration->up();

    $this->actingAs($this->user = createSuperAdminWithoutTeam());

    Aura::fake();
    Aura::setModel(new Post);
});

afterEach(function () {
    config(['aura.teams' => true]);
});

describe('page accessibility without teams', function () {
    it('confirms teams are disabled', function () {
        expect(config('aura.teams'))->toBeFalse();
    });

    describe('dashboard and core pages', function () {
        it('renders the dashboard', function () {
            $this->withoutExceptionHandling();

            $this->get(config('aura.path'))
                ->assertOk();
        });

        it('renders the settings page', function () {
            $this->get(route('aura.settings'))
                ->assertOk();
        });

        it('renders the profile page', function () {
            $this->get(route('aura.profile'))
                ->assertOk();
        });
    });

    describe('index pages', function () {
        it('renders user index page', function () {
            $this->get(route('aura.user.index'))
                ->assertOk();
        });

        it('renders post index page', function () {
            $this->get(route('aura.post.index'))
                ->assertOk();
        });

        it('renders role index page', function () {
            $this->get(route('aura.role.index'))
                ->assertOk();
        });

        it('renders permission index page', function () {
            $this->get(route('aura.permission.index'))
                ->assertOk();
        });

        it('renders attachment index page', function () {
            $this->get(route('aura.attachment.index'))
                ->assertOk();
        });

        it('renders option index page', function () {
            $this->get(route('aura.option.index'))
                ->assertOk();
        });
    });

    describe('create pages', function () {
        it('renders user create page', function () {
            $this->get(route('aura.user.create'))
                ->assertOk();
        });

        it('renders post create page', function () {
            $this->get(route('aura.post.create'))
                ->assertOk();
        });

        it('renders permission create page', function () {
            Aura::setModel(new Permission);

            $this->get(route('aura.permission.create'))
                ->assertOk();
        });

        it('renders role create page', function () {
            Aura::setModel(new Role);

            $this->get(route('aura.role.create'))
                ->assertOk();
        });

        it('renders option create page', function () {
            Aura::setModel(new Option);

            $this->get(route('aura.option.create'))
                ->assertOk();
        });
    });

    describe('edit pages', function () {
        beforeEach(function () {
            $this->post = Post::create([
                'title' => 'Test Post',
                'slug' => 'test-post',
                'content' => 'Test Post Content',
            ]);

            $this->permission = Permission::create([
                'name' => 'Test Permission',
                'slug' => 'test-permission',
                'description' => 'Test Permission Description',
            ]);

            $this->option = Option::create([
                'name' => 'Test Option',
                'value' => 'test-option',
            ]);
        });

        it('renders post edit page', function () {
            $this->get(route('aura.post.edit', ['id' => $this->post->id]))
                ->assertOk();
        });

        it('renders user edit page', function () {
            $user = User::first();

            $this->get(route('aura.user.edit', ['id' => $user->id]))
                ->assertOk();
        });

        it('renders role edit page', function () {
            $role = Role::first();

            $this->get(route('aura.role.edit', ['id' => $role->id]))
                ->assertOk();
        });

        it('renders permission edit page', function () {
            $this->get(route('aura.permission.edit', ['id' => $this->permission->id]))
                ->assertOk();
        });

        it('renders option edit page', function () {
            $this->get(route('aura.option.edit', ['id' => $this->option->id]))
                ->assertOk();
        });
    });

    describe('view pages', function () {
        beforeEach(function () {
            $this->post = Post::create([
                'title' => 'Test Post for View',
                'slug' => 'test-post-view',
                'content' => 'Test Post Content for View',
            ]);

            $this->permission = Permission::create([
                'name' => 'View Test Permission',
                'slug' => 'view-test-permission',
                'description' => 'View Test Permission Description',
            ]);

            $this->option = Option::create([
                'name' => 'View Test Option',
                'value' => 'view-test-option',
            ]);
        });

        it('renders post view page', function () {
            $this->get(route('aura.post.view', ['id' => $this->post->id]))
                ->assertOk();
        });

        it('renders user view page', function () {
            $user = User::first();

            $this->get(route('aura.user.view', ['id' => $user->id]))
                ->assertOk();
        });

        it('renders role view page', function () {
            $role = Role::first();

            $this->get(route('aura.role.view', ['id' => $role->id]))
                ->assertOk();
        });

        it('renders permission view page', function () {
            $this->get(route('aura.permission.view', ['id' => $this->permission->id]))
                ->assertOk();
        });

        it('renders option view page', function () {
            $this->get(route('aura.option.view', ['id' => $this->option->id]))
                ->assertOk();
        });
    });
});

describe('data integrity without teams', function () {
    it('creates resources without team_id', function () {
        $post = Post::create([
            'title' => 'No Team Post',
            'slug' => 'no-team-post',
            'content' => 'Content without team',
        ]);

        expect($post)->not->toBeNull()
            ->and($post->team_id)->toBeNull();
    });

    it('creates permissions without team_id', function () {
        $permission = Permission::create([
            'name' => 'No Team Permission',
            'slug' => 'no-team-permission',
            'description' => 'Permission without team',
        ]);

        expect($permission)->not->toBeNull()
            ->and($permission->team_id)->toBeNull();
    });

    it('creates options without team_id', function () {
        $option = Option::create([
            'name' => 'No Team Option',
            'value' => 'option-without-team',
        ]);

        expect($option)->not->toBeNull()
            ->and($option->team_id)->toBeNull();
    });
});
