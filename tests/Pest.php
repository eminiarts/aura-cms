<?php

use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\BrowserTestCase;
use Aura\Base\Tests\Resources\Post;
use Aura\Base\Tests\TestCase;
use Illuminate\Foundation\Testing\DatabaseMigrations;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Pest\Browser\Api\Webpage;

uses(TestCase::class)->in(__DIR__.'/Feature', __DIR__.'/FeatureWithDatabaseMigrations', __DIR__.'/Unit');

uses(BrowserTestCase::class)->group('browser')->in(__DIR__.'/Browser');

uses()->group('fields')->in('Feature/Fields');
uses()->group('flows')->in('Feature/Flows');
uses()->group('table')->in('Feature/Table');
uses()->group('resource')->in('Feature/Resource');

uses()->beforeEach(function () {
    if (env('AURA_TEAMS') === false) {
        $this->markTestSkipped('This test exercises team-only behavior.');
    }
})->in(
    'Feature/UserRoleConditionalIndexFieldsTest.php',
    'Feature/Aura/MakeUserCommandTest.php',
    'Feature/ThemeTest.php',
    'Feature/TeamSettingsTest.php'
);

uses(RefreshDatabase::class)->in('Feature');
uses(DatabaseMigrations::class)->in('FeatureWithDatabaseMigrations');

// Reset Aura facade after each test to prevent pollution
uses()->afterEach(function () {
    // Reset the Aura facade to its original state by rebinding the service
    app()->forgetInstance(Aura\Base\Aura::class);
    app()->singleton(Aura\Base\Aura::class);
    Aura\Base\Facades\Aura::clearResolvedInstances();

    Aura\Base\Facades\Aura::flushState();
})->in('Feature', 'FeatureWithDatabaseMigrations');

// uses(\Illuminate\Foundation\Testing\RefreshDatabase::class);

// uses(\Illuminate\Foundation\Testing\LazilyRefreshDatabase::class)->in('Feature');

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

function createPost(array $attributes = []): Post
{
    return Post::factory()->create($attributes);
}

/**
 * The shared global `admin` Global Role (team_id = null) that backs the
 * attach-don't-mint model. Returned unscoped so it is visible regardless of the
 * current team context.
 */
function globalAdminRole(): ?Role
{
    return Role::withoutGlobalScopes()
        ->whereNull('team_id')
        ->where('slug', 'admin')
        ->first();
}

/**
 * Promote a fresh user to Global Admin the trusted way: a direct,
 * pipeline-bypassing write (`saveQuietly`), the same posture the CLI bootstrap
 * uses. The flag is never granted through a user-facing write path in tests.
 */
function createGlobalAdmin(array $attributes = []): User
{
    $user = User::factory()->create($attributes);
    $user->forceFill(['global_admin' => true])->saveQuietly();

    return $user->refresh();
}

/**
 * A team owned by someone else, with the current user holding no Membership —
 * for asserting cross-team visibility and Global Admin visitation. Created
 * quietly so the team-creation hook does not attach the acting user.
 */
function foreignTeam(): Team
{
    return Team::factory()->createQuietly(['user_id' => User::factory()->create()->id]);
}

/**
 * A user whose only Membership is in the given team (pivot role + current team).
 */
function soleMemberOf(Team $team): User
{
    $role = Role::where('team_id', $team->id)->first()
        ?? Role::factory()->create(['team_id' => $team->id]);

    $member = User::factory()->create();
    $member->roles()->attach($role->id, ['team_id' => $team->id]);
    $member->forceFill(['current_team_id' => $team->id])->save();

    return $member->refresh();
}

function createSuperAdmin()
{
    if (! config('aura.teams')) {
        return createSuperAdminWithoutTeam();
    }

    $user = User::factory()->create();

    auth()->login($user);

    // Attach-don't-mint: creating the team attaches the creator to the shared
    // global `admin` role via Team::booted (self-healing the Global Role when the
    // catalog was not seeded). No per-team admin row is minted.
    $team = Team::factory()->create();

    // Set current_team_id of the user
    $user->update(['current_team_id' => $team->id]);

    // Clear the cache for the user's current_team_id to ensure TeamScope uses the updated value
    Cache::forget("user_{$user->id}_current_team_id");

    $user->refresh();

    return $user;
}

function bootstrapApp()
{
    $app = require_once base_path('bootstrap/app.php'); // Using base_path to ensure correct location
    $kernel = $app->make(Kernel::class);
    $kernel->bootstrap();

    // Run migrations
    // Artisan::call('migrate:fresh', ['--force' => true]);
}

function createSuperAdminWithoutTeam()
{
    $user = User::factory()->create();

    auth()->login($user);

    // Reuse the seeded admin Global Role when present (Teams-off has a unique
    // slug constraint), otherwise create it.
    $role = Role::firstOrCreate(['slug' => 'admin'], [
        'type' => 'Role',
        'title' => 'Admin',
        'name' => 'Admin',
        'description' => 'Admin can perform everything.',
        'super_admin' => true,
        'permissions' => [],
        'user_id' => $user->id,
    ]);

    // Attach the role to the user using the relationship
    $user->roles()->sync([$role->id]);

    $user->refresh();

    return $user;
}

function createAdmin()
{
    $user = User::factory()->create();

    $team = null;
    if (config('aura.teams')) {
        $team = Team::first() ?: Team::factory()->create();
        $user->update(['current_team_id' => $team->id]);
    }

    $role = Role::create([...($team ? ['team_id' => $team->id] : []), 'type' => 'Role', 'title' => 'Editor', 'slug' => 'editor', 'name' => 'Editor', 'description' => 'Editor has limited permissions.', 'super_admin' => false, 'permissions' => [
        'view-attachment' => true,
        'viewAny-attachment' => true,
        'create-attachment' => true,
        'update-attachment' => true,
        'restore-attachment' => true,
        'delete-attachment' => true,
        'forceDelete-attachment' => true,
        'scope-attachment' => false,
        'view-option' => false,
        'viewAny-option' => false,
        'create-option' => false,
        'update-option' => false,
        'restore-option' => false,
        'delete-option' => false,
        'forceDelete-option' => false,
        'scope-option' => false,
        'view-post' => false,
        'viewAny-post' => false,
        'create-post' => false,
        'update-post' => false,
        'restore-post' => false,
        'delete-post' => false,
        'forceDelete-post' => false,
        'scope-post' => false,
        'view-permission' => false,
        'viewAny-permission' => false,
        'create-permission' => false,
        'update-permission' => false,
        'restore-permission' => false,
        'delete-permission' => false,
        'forceDelete-permission' => false,
        'scope-permission' => false,
        'view-role' => false,
        'viewAny-role' => false,
        'create-role' => false,
        'update-role' => false,
        'restore-role' => false,
        'delete-role' => false,
        'forceDelete-role' => false,
        'scope-role' => false,
        'view-user' => true,
        'viewAny-user' => true,
        'create-user' => true,
        'update-user' => true,
        'restore-user' => true,
        'delete-user' => true,
        'forceDelete-user' => true,
        'scope-user' => true,
        'view-team' => false,
        'viewAny-team' => false,
        'create-team' => false,
        'update-team' => false,
        'restore-team' => false,
        'delete-team' => false,
        'forceDelete-team' => false,
        'scope-team' => false,
        'view-TeamInvitation' => false,
        'viewAny-TeamInvitation' => false,
        'create-TeamInvitation' => false,
        'update-TeamInvitation' => false,
        'restore-TeamInvitation' => false,
        'delete-TeamInvitation' => false,
        'forceDelete-TeamInvitation' => false,
        'scope-TeamInvitation' => false,
    ]]);

    // Associate the role with the user using the proper relationship
    if (config('aura.teams')) {
        $user->roles()->syncWithPivotValues([$role->id], ['team_id' => $team->id]);
    } else {
        $user->roles()->sync([$role->id]);
    }

    $user->refresh();

    return $user;
}

/**
 * Attach real files to a file input in a Pest browser test.
 *
 * Playwright's remote server rejects local file paths (`localPaths are not
 * allowed when the client is not local`), so this injects the file contents
 * into the input via JavaScript and fires a `change` event — the same thing
 * a user's file-picker selection does.
 *
 * @param  Webpage  $page
 * @param  string  $selector  CSS selector of the file input
 * @param  array<int, string>|string  $paths  Fixture file path(s)
 */
function browserAttachFiles($page, string $selector, array|string $paths): void
{
    $payload = collect((array) $paths)->map(fn (string $path) => [
        'name' => basename($path),
        'type' => mime_content_type($path) ?: 'application/octet-stream',
        'data' => base64_encode((string) file_get_contents($path)),
    ])->toJson();

    $files = json_encode($payload);
    $target = json_encode($selector);

    $page->script(<<<JS
        (() => {
            const input = document.querySelector({$target});
            const dataTransfer = new DataTransfer();

            for (const file of JSON.parse({$files})) {
                const bytes = Uint8Array.from(atob(file.data), (c) => c.charCodeAt(0));
                dataTransfer.items.add(new File([bytes], file.name, { type: file.type }));
            }

            input.files = dataTransfer.files;
            input.dispatchEvent(new Event('change', { bubbles: true }));
        })()
    JS);
}
