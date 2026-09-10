# Testing

Aura CMS has two separate test targets:

- A host application test loads your Laravel application, its published Aura migrations, and its configured User model.
- The Aura package test runs under Orchestra Testbench and uses fixtures that live inside this repository.

This page starts with host application tests. That is the test setup to use for Resources and Fields in an application that installs Aura through Composer. The package-only section at the end describes the maintainer setup so the two environments are not mixed.

## Set up host application tests

### Install test dependencies

Install Pest and the Laravel plugin in the host application if they are not already present:

```bash
composer require --dev pestphp/pest pestphp/pest-plugin-laravel
```

Livewire 4 provides the Livewire\Livewire test facade. The examples on this page use that facade, so they do not require pestphp/pest-plugin-livewire.

If you prefer Pest's livewire() function, install the plugin in the host application and import Pest\Livewire\livewire in the test file:

```bash
composer require --dev pestphp/pest-plugin-livewire
```

The plugin is a convenience layer. It does not make the package's internal tests or helpers available to the host application.

### Use a test database

Set test-only database and service values in the host application's .env.testing. SQLite in memory is suitable for ordinary feature tests:

```dotenv
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_STORE=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
AURA_TEAMS=true
```

Publish and run Aura's migrations as part of the host application's normal installation. RefreshDatabase then migrates the test database and resets it between tests:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
```

Use a dedicated test database when SQLite is not suitable. Never point tests at a development or production database. Do not use a generic destructive command such as migrate:fresh in a troubleshooting recipe for a real application database.

### Test both teams modes

The aura.teams setting comes from AURA_TEAMS and defaults to true. It changes the schema as well as role and membership resolution:

- Teams on creates the teams table, team columns, and a team_id column on the user_role pivot.
- Teams off omits those tables and columns and uses one flat role catalog.

Choose the value before the test database is migrated. Run the same focused test file in a second process with AURA_TEAMS=false, or maintain a second PHPUnit configuration with that environment value. Do not toggle the setting inside a test after the schema has been migrated.

The package's own tests use RefreshDatabase for tests/Feature and DatabaseMigrations for tests/FeatureWithDatabaseMigrations. Use RefreshDatabase for normal host application tests. Use a migration-aware test setup when a test creates a table or otherwise changes the schema.

### Reset Aura's process state

Aura::fake() and resource registration change process-level state. If a test file registers a fake Resource, clear that state after each test. This is the reset pattern used by the package suite:

```php
afterEach(function (): void {
    app()->forgetInstance(\Aura\Base\Aura::class);
    app()->singleton(\Aura\Base\Aura::class);
    \Aura\Base\Facades\Aura::clearResolvedInstances();
    \Aura\Base\Facades\Aura::flushState();
});
```

Keep this reset in the host application's tests/Pest.php when several test files use Aura::fake().

## Build a self-contained Resource fixture

The following test defines its Resource, creates an authenticated Aura user, and handles both teams modes. It does not use createSuperAdmin(), Aura\Base\Tests\Resources\Post, or any other package test helper.

The fixture assumes the application uses Aura's built-in User model. If the application configures a custom User Resource, create that configured class and preserve its roles relationship.

```php
<?php

use Aura\Base\Facades\Aura;
use Aura\Base\Livewire\Resource\Create;
use Aura\Base\Resource;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Livewire\Livewire;

uses(RefreshDatabase::class);

final class Article extends Resource
{
    public static ?string $slug = 'article';

    public static string $type = 'Article';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'title',
                'validation' => 'required|max:255',
                'conditional_logic' => [],
            ],
        ];
    }
}

beforeEach(function (): void {
    $this->user = User::query()->create([
        'name' => 'Test admin',
        'email' => 'test-admin@example.test',
        'password' => Hash::make('password'),
    ]);

    $this->actingAs($this->user);

    if (config('aura.teams')) {
        Team::query()->create([
            'name' => 'Test team',
            'user_id' => $this->user->id,
        ]);

        $this->user->refresh();
        Cache::forget(User::currentTeamCacheKey($this->user->id));
    } else {
        $role = Role::firstOrCreateGlobalAdmin();
        $this->user->roles()->sync([$role->id]);
        $this->user->refresh();
    }

    Aura::fake();
    Aura::setModel(new Article);
});

test('creates an article', function (): void {
    Livewire::test(Create::class, ['slug' => 'article'])
        ->set('form.fields.title', 'Hello World')
        ->call('save')
        ->assertHasNoErrors();

    $this->assertDatabaseHas('posts', ['type' => 'Article']);

    $article = Article::query()->latest('id')->firstOrFail();

    expect($article->fields['title'])->toBe('Hello World');
});

test('requires a title', function (): void {
    Livewire::test(Create::class, ['slug' => 'article'])
        ->set('form.fields.title', '')
        ->call('save')
        ->assertHasErrors(['form.fields.title']);
});
```

The teams-on branch creates a Team while the test user is authenticated. Team creation attaches the user to the shared admin Global Role and sets the current team. The explicit cache clear keeps TeamScope reads aligned with the new current team. In teams-off mode, Role::firstOrCreateGlobalAdmin() creates or reuses the flat admin role, and the user receives it through the roles relationship.

The Article field definition is an array. Aura does not provide a fluent field builder such as Text::make()->rules(...).

## Assert Resource storage

Resources use the shared posts table by default. A Resource with meta storage keeps declared field values in the meta table and uses posts.type to identify the Resource:

```php
$this->assertDatabaseHas('posts', [
    'type' => 'Article',
]);

$article = Article::query()->latest('id')->firstOrFail();

expect($article->fields['title'])->toBe('Hello World');
```

Read a meta-backed field through the Resource's fields accessor or its field accessor. Do not assert that a declared field has its own physical column.

A custom-table Resource declares public static $customTable = true and normally sets public static bool $usesMeta = false. Its host migration must create the physical columns used by the Resource. Assert those columns in the custom table, and use a migration-aware test setup when the test owns that schema.

## Test fields and validation

Fields are declared in getFields() as arrays with a type class and a slug. Test a field's validation through the Resource Livewire component:

```php
use Aura\Base\Livewire\Resource\Create;
use Livewire\Livewire;

test('rejects an invalid email', function (): void {
    Livewire::test(Create::class, ['slug' => 'article'])
        ->set('form.fields.email', 'not-an-email')
        ->call('save')
        ->assertHasErrors(['form.fields.email']);
});
```

The Resource used in that test must declare an Email field with validation set to a rule such as required|email. Use withViewErrors([])->blade(...) when you need to assert a field view's rendered markup. Instantiate the field class through the container and call its edit() or view() method. Conditional visibility is represented by a conditional_logic array or closure, and can be checked through Aura\Base\ConditionalLogic.

## Test Livewire resource pages

Use Livewire's facade for component tests:

```php
use Aura\Base\Livewire\Resource\Edit;
use Livewire\Livewire;

$article = Article::query()->latest('id')->firstOrFail();

Livewire::test(Edit::class, [
    'slug' => 'article',
    'id' => $article->id,
])
    ->set('form.fields.title', 'Updated title')
    ->call('save')
    ->assertHasNoErrors();
```

Create, Edit, Index, and View are the Resource page components. Create::save() redirects to the edit route when it is not running in a modal:

```php
Livewire::test(Create::class, ['slug' => 'article'])
    ->set('form.fields.title', 'Hello World')
    ->call('save')
    ->assertRedirect(route('aura.article.edit', Article::query()->latest('id')->value('id')));
```

When testing the registered page route, set the fake model first and then assert the normal HTTP response:

```php
$this->get(route('aura.article.index'))
    ->assertOk()
    ->assertSeeLivewire(\Aura\Base\Livewire\Resource\Index::class);
```

The route names are aura.{slug}.index, aura.{slug}.create, aura.{slug}.edit, and aura.{slug}.view. Aura does not add store or destroy routes for these pages. Saving and deletion happen in the Livewire components or Resource actions.

## Test permissions and memberships

Aura uses several distinct concepts in permission tests:

- A Super Admin is a role-level grant. Its super_admin flag grants every permission in the current team.
- A Global Admin is an instance-level user flag in users.global_admin. It can cross the team boundary and is separate from the Super Admin role.
- A Global Role has no team and belongs to the Role Catalog. A Team Role has a team_id and exists only in that team.
- A Membership is a user-to-team pivot row with one role. A Global Admin entering a team without a pivot row is visiting that team, not becoming a member.

Test a normal role through the same relationship that the application uses:

```php
use Aura\Base\Resources\Role;

$roleAttributes = [
    'name' => 'Writer',
    'slug' => 'writer',
    'description' => 'Can view articles.',
    'super_admin' => false,
    'permissions' => [
        'viewAny-article' => true,
        'view-article' => true,
    ],
];

if (config('aura.teams')) {
    $roleAttributes['team_id'] = $this->user->current_team_id;
}

$role = Role::query()->create($roleAttributes);

if (config('aura.teams')) {
    $this->user->roles()->syncWithPivotValues(
        [$role->id],
        ['team_id' => $this->user->current_team_id],
    );
} else {
    $this->user->roles()->sync([$role->id]);
}

$this->user->refresh();

expect($this->user->hasPermission('viewAny-article'))->toBeTrue();
```

syncWithPivotValues is required when teams are on because the user_role pivot stores team_id. Teams-off uses sync because that column does not exist. To exercise Global Admin behavior, set global_admin in a trusted fixture with forceFill(['global_admin' => true])->saveQuietly(). The field is intentionally not mass assignable.

## Test uploads

Fake both the configured media disk and Livewire's temporary test disk before mounting the uploader:

```php
use Aura\Base\Livewire\MediaUploader;
use Aura\Base\Resources\Attachment;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('uploads an image', function (): void {
    Storage::fake('public');
    Storage::fake('tmp-for-tests');

    Livewire::test(MediaUploader::class)
        ->set('media', [UploadedFile::fake()->image('avatar.png')])
        ->assertHasNoErrors();

    $attachment = Attachment::query()->firstOrFail();

    expect($attachment->mime_type)->toBe('image/png');
    Storage::disk('public')->assertExists($attachment->url);
});
```

## Test Artisan commands

Assert the command signature and the generated artifact. For example, aura:resource accepts a name and an optional --custom flag:

```php
test('generates a Resource', function (): void {
    $this->artisan('aura:resource', ['name' => 'Article'])
        ->assertSuccessful();

    $this->assertFileExists(app_path('Aura/Resources/Article.php'));
});
```

The package also registers aura:field, aura:user, aura:install-config, and aura:publish. Read the command signature before adding options to a test. A command test should use a test filesystem or a temporary application path when the command writes files.

## Package maintainer tests

The package repository has a separate Testbench application. Aura\Base\Tests\TestCase extends Orchestra\Testbench\TestCase, uses LazilyRefreshDatabase and InteractsWithViews, registers the package providers, and loads database/migrations/create_aura_tables.php.stub in its environment setup.

The package tests/Pest.php binds that Testbench case to tests/Feature, tests/FeatureWithDatabaseMigrations, and tests/Unit. It applies RefreshDatabase to Feature and DatabaseMigrations to FeatureWithDatabaseMigrations.

The package composer.json maps Aura\Base\Tests\\ through autoload-dev. The namespace, Aura\Base\Tests\TestCase, Aura\Base\Tests\Resources\Post, and the helper functions in tests/Pest.php are therefore package test code. Composer does not autoload them into an application that only requires eminiarts/aura-cms.

The current main branch declares these test-related versions:

| Package | Constraint |
| --- | --- |
| PHP | ^8.4 |
| Laravel | ^13.0 |
| Livewire | ^4.0 |
| Pest | ^4.0 |
| Pest Laravel plugin | ^4.0 |
| Pest Livewire plugin | ^4.0 |
| Orchestra Testbench | ^11.0 |

Run the package scripts from the package checkout:

```bash
composer test
composer test-coverage
composer analyse
composer format
```

composer test is the package's default Pest command. It runs without coverage, uses parallel workers, and excludes the separate Browser testsuite. The coverage script is also defined by the package. These scripts are not installed in a customer application.

The package helpers are implementation fixtures, not a public API. In the current suite, createSuperAdmin() creates an authenticated user and, when teams are enabled, a Team that attaches the shared Global Role with slug admin. With teams disabled it delegates to createSuperAdminWithoutTeam(), which reuses or creates that role and syncs it without a team pivot. createAdmin() creates an Editor role with an explicit permission map. createPost() returns the package's Aura\Base\Tests\Resources\Post fixture. Other helpers such as createGlobalAdmin(), foreignTeam(), and soleMemberOf() are also defined for package tests only.

## Focused test commands

Use a focused file or filter while developing:

```bash
vendor/bin/pest tests/Feature/Fields/TextFieldTest.php
vendor/bin/pest --filter "requires a title"
vendor/bin/pest --group=fields
```

Run the same focused test with the teams-off environment in a separate process after the schema has been rebuilt for that mode.

## Related guides

- [Resources](/docs/resources) explains Resource definitions and storage.
- [Fields](/docs/fields) lists the available field types and options.
- [Custom tables](/docs/custom-tables) covers dedicated Resource tables.
- [Roles and permissions](/docs/roles-permissions) describes permission keys and role resolution.
- [Teams](/docs/teams) covers team context and teams-off mode.
