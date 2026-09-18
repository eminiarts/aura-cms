# Testing

Aura CMS has two separate test targets:

- A host application test loads your Laravel application, its published Aura migrations, and its configured user model.
- The Aura package test runs under Orchestra Testbench and uses fixtures that live inside this repository.

Use the host application setup below to test resources and fields in a Laravel application that installs Aura through Composer. The [package maintainer section](#package-maintainer-tests) describes how to test Aura itself.

## Set up host application tests

### Install test dependencies

Install Pest and the Laravel plugin in the host application if they are not already present:

```bash
composer require --dev pestphp/pest pestphp/pest-plugin-laravel
```

The examples use Livewire 4's test facade, so you do not need the Pest Livewire plugin.

If you prefer Pest's `livewire()` function, install the plugin and import `Pest\Livewire\livewire` in your test file:

```bash
composer require --dev pestphp/pest-plugin-livewire
```

The plugin is a convenience layer. It does not make the package's internal tests or helpers available to the host application.

### Use a test database

Configure a separate testing environment in your application's `.env.testing` file. An in-memory SQLite database is suitable for ordinary feature tests:

```dotenv
APP_ENV=testing
DB_CONNECTION=sqlite
DB_DATABASE=:memory:
CACHE_STORE=array
QUEUE_CONNECTION=sync
SESSION_DRIVER=array
AURA_TEAMS=true
```

Publish and run Aura's migrations during the normal installation. Laravel's `RefreshDatabase` trait then migrates the test database and resets it between tests:

```php
<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);
```

Use a dedicated test database when SQLite is not suitable. Never point tests at a development or production database. Do not run destructive commands such as `migrate:fresh` against a real application database while troubleshooting tests.

### Test both teams modes

The `AURA_TEAMS` environment variable controls the `aura.teams` setting and defaults to `true`. Enabling teams changes the database schema and how Aura resolves roles and memberships:

- With teams enabled, migrations create the teams table, team columns, and a `team_id` column on the `user_role` pivot table.
- With teams disabled, migrations omit those tables and columns. Roles belong to one flat catalog.

Choose the value before the test database is migrated. Run the same focused test file in a second process with `AURA_TEAMS=false`, or maintain a second PHPUnit configuration with that environment value. Do not toggle the setting inside a test after the schema has been migrated.

Use `RefreshDatabase` for normal host application tests. If a test creates tables or changes the schema, use a setup that reruns migrations, such as `DatabaseMigrations`. The package follows this distinction in its feature tests, as described in the [maintainer setup](#package-maintainer-tests).

### Reset Aura's process state

Faking Aura or registering resources changes state that persists for the rest of the process. Clear it after each test that registers a fake resource. The package suite uses this reset:

```php
afterEach(function (): void {
    app()->forgetInstance(\Aura\Base\Aura::class);
    app()->singleton(\Aura\Base\Aura::class);
    \Aura\Base\Facades\Aura::clearResolvedInstances();
    \Aura\Base\Facades\Aura::flushState();
});
```

Keep this reset in your application's `tests/Pest.php` when several test files use `Aura::fake()`.

## Build a self-contained Resource fixture

The following test defines an article resource and creates an authenticated Aura user. It works with teams enabled or disabled and defines everything it needs without relying on package test helpers.

The fixture assumes your application uses Aura's built-in user model. If you use a custom user resource, create that class instead and preserve its `roles` relationship.

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

When teams are enabled, creating a team for the authenticated user assigns the shared global admin role and sets the current team. Clearing the cache makes subsequent team-scoped queries use that team.

With teams disabled, the fixture creates or reuses the admin role in the flat catalog and assigns it through the user's roles relationship.

Define fields as arrays, as shown above. Aura does not provide a fluent field builder such as `Text::make()->rules(...)`.

## Assert Resource storage

Resources use the shared `posts` table by default. With meta storage, declared field values live in the `meta` table, while `posts.type` identifies the resource:

```php
$this->assertDatabaseHas('posts', [
    'type' => 'Article',
]);

$article = Article::query()->latest('id')->firstOrFail();

expect($article->fields['title'])->toBe('Hello World');
```

Read fields stored as metadata through the resource's `fields` accessor or the individual field accessor. These values do not have their own physical columns.

For a resource that uses a custom table, set `public static $customTable = true`. These resources normally disable meta storage with `public static bool $usesMeta = false`, so your application migration must create columns for their fields. Assert values in those columns. If the test creates the table, use a setup that reruns migrations.

## Test fields and validation

Declare each field in `getFields()` with its type class and slug, then test validation through the resource's Livewire component:

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

The resource used in this test must declare an email field with a validation rule such as `required|email`.

To test rendered field markup, resolve the field class through the container and call its `edit()` or `view()` method. Render the view with `withViewErrors([])->blade(...)`.

For conditional visibility, define an array or closure under `conditional_logic` and check it through `Aura\Base\ConditionalLogic`.

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

Aura provides create, edit, index, and view page components. Saving a new record redirects to its edit page unless the create component is running in a modal:

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

Page routes use the names `aura.{slug}.index`, `aura.{slug}.create`, `aura.{slug}.edit`, and `aura.{slug}.view`. Saving and deletion happen through Livewire components or resource actions, so these pages have no store or destroy routes.

## Test permissions and memberships

Aura uses several distinct concepts in permission tests:

- A super admin role grants every permission in the current team through its `super_admin` flag.
- A global admin user can cross team boundaries. This access comes from `users.global_admin`, independently of the super admin role.
- A global role has no team and belongs to the role catalog. A team role belongs to one team through its `team_id`.
- Membership links a user to a team through a pivot row with one role. A global admin who enters a team without that row is visiting it and does not become a member.

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

When teams are enabled, assign the role with `syncWithPivotValues()` to include the team ID on the pivot row. With teams disabled, use `sync()` because the pivot table has no team column.

To test global admin access, set the flag in a trusted fixture with `forceFill(['global_admin' => true])->saveQuietly()`. This field is intentionally protected from mass assignment.

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

Test that the command accepts its documented arguments and produces the expected file. For example, `aura:resource` accepts a name and an optional `--custom` flag:

```php
test('generates a Resource', function (): void {
    $this->artisan('aura:resource', ['name' => 'Article'])
        ->assertSuccessful();

    $this->assertFileExists(app_path('Aura/Resources/Article.php'));
});
```

The package also registers `aura:field`, `aura:user`, `aura:install-config`, and `aura:publish`. Read the command signature before adding options to a test. A command test should use a test filesystem or a temporary application path when the command writes files.

<a id="package-maintainer-tests"></a>

## Package maintainer tests

The package repository runs tests in a separate Orchestra Testbench application. Its base test case, `Aura\Base\Tests\TestCase`, extends the Testbench case and uses the `LazilyRefreshDatabase` and `InteractsWithViews` traits. During setup, it registers the package providers and loads `database/migrations/create_aura_tables.php.stub`.

The package configures its test directories in `tests/Pest.php`. Feature, migration, and unit tests all use the Testbench case. Tests in `tests/Feature` also use `RefreshDatabase`, while those in `tests/FeatureWithDatabaseMigrations` use `DatabaseMigrations`.

The test namespace is registered through Composer's development autoloader. The base test case, resource fixtures, and helpers in `tests/Pest.php` are available only inside the package checkout. Composer does not autoload them into an application that requires `eminiarts/aura-cms`.

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

The `composer test` script runs Pest in parallel without coverage and excludes the separate browser test suite. Use `composer test-coverage` for coverage. These scripts belong to the package checkout and are not installed in your application.

The package helpers are internal fixtures and are not a public API. Their current behavior is:

- `createSuperAdmin()` authenticates a user. With teams enabled, it creates a team and attaches the shared global role whose slug is `admin`.
- `createSuperAdminWithoutTeam()` creates or reuses that admin role and assigns it without a team pivot value. The super admin helper delegates to it when teams are disabled.
- `createAdmin()` creates an editor role with an explicit permission map.
- `createPost()` returns the package's `Aura\Base\Tests\Resources\Post` fixture.

Other helpers, including `createGlobalAdmin()`, `foreignTeam()`, and `soleMemberOf()`, are also available only to package tests.

## Focused test commands

Use a focused file or filter while developing:

```bash
vendor/bin/pest tests/Feature/Fields/TextFieldTest.php
vendor/bin/pest --filter "requires a title"
vendor/bin/pest --group=fields
```

Run the same focused test with the teams-off environment in a separate process after the schema has been rebuilt for that mode.

## Related guides

- [Resources](/docs/resources) explains resource definitions and storage.
- [Fields](/docs/fields) lists the available field types and options.
- [Custom tables](/docs/custom-tables) covers dedicated resource tables.
- [Roles and permissions](/docs/roles-permissions) describes permission keys and role resolution.
- [Teams](/docs/teams) covers team context and teams-off mode.
