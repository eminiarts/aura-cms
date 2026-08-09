<?php

use Aura\Base\Resource;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class ExplicitNullSharedPost extends Resource
{
    public static bool $sharedAcrossTeams = true;

    public static string $type = 'ExplicitNullSharedPost';
}

class ExplicitNullSharedCustomResource extends Resource
{
    public static $customTable = true;

    public static bool $sharedAcrossTeams = true;

    public static bool $usesMeta = false;

    protected $fillable = ['name', 'team_id', 'user_id'];

    protected $table = 'explicit_null_shared_custom_resources';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Name',
                'slug' => 'name',
                'type' => 'Aura\\Base\\Fields\\Text',
                'validation' => 'required',
            ],
        ];
    }
}

class ThrowingGlobalCustomResource extends ExplicitNullSharedCustomResource
{
    protected static function booted(): void
    {
        parent::booted();

        static::saving(function (): void {
            throw new Error('global write failure');
        });
    }
}

beforeEach(function () {
    if (! Schema::hasColumn('posts', 'team_id')) {
        $this->markTestSkipped('Initial team defaults require the teams schema.');
    }

    Schema::create('explicit_null_shared_custom_resources', function (Blueprint $table) {
        $table->id();
        $table->string('name');
        $table->foreignId('team_id')->nullable();
        $table->foreignId('user_id')->nullable();
        $table->timestamps();
    });
});

afterEach(function () {
    Schema::dropIfExists('explicit_null_shared_custom_resources');
});

it('defaults explicitly null team and creator values in ordinary creates', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $post = Post::withoutGlobalScopes()->create([
        'title' => 'Unowned global candidate',
        'team_id' => null,
        'user_id' => null,
    ]);

    $post = Post::withoutGlobalScopes()->findOrFail($post->id);

    $custom = ExplicitNullSharedCustomResource::withoutGlobalScopes()->create([
        'name' => 'Ordinary custom create',
        'team_id' => null,
        'user_id' => null,
    ])->refresh();

    expect($post->getAttribute('team_id'))->toBe($actor->current_team_id)
        ->and($post->getAttribute('user_id'))->toBe($actor->id)
        ->and($custom->getAttribute('team_id'))->toBe($actor->current_team_id)
        ->and($custom->getAttribute('user_id'))->toBe($actor->id);
});

it('defaults omitted team and creator values from the authenticated user', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $post = Post::withoutGlobalScopes()->create([
        'title' => 'Owned team post',
    ]);

    $post = Post::withoutGlobalScopes()->findOrFail($post->id);

    expect($post->getAttribute('team_id'))->toBe($actor->current_team_id)
        ->and($post->getAttribute('user_id'))->toBe($actor->id);
});

it('preserves explicit null tenancy and ownership through the privileged global create contract', function () {
    $globalAdmin = createSuperAdmin();
    $globalAdmin->forceFill(['global_admin' => true])->saveQuietly();
    $this->actingAs($globalAdmin->refresh());

    $post = ExplicitNullSharedPost::createGlobal([
        'title' => 'Privileged global post',
        'user_id' => null,
    ]);
    $custom = ExplicitNullSharedCustomResource::createGlobal([
        'name' => 'Privileged global custom row',
        'user_id' => null,
    ]);

    expect($post->getAttribute('team_id'))->toBeNull()
        ->and($post->getAttribute('user_id'))->toBeNull()
        ->and($custom->getAttribute('team_id'))->toBeNull()
        ->and($custom->getAttribute('user_id'))->toBeNull();
});

it('refuses the privileged global create contract to a team admin', function () {
    $teamAdmin = createSuperAdmin();
    $this->actingAs($teamAdmin);

    expect(fn () => ExplicitNullSharedCustomResource::createGlobal([
        'name' => 'Forbidden global custom row',
    ]))->toThrow(AuthorizationException::class);
});

it('requires an explicit trusted contract for unauthenticated global creation', function () {
    auth()->logout();

    expect(fn () => ExplicitNullSharedCustomResource::withoutGlobalScopes()->create([
        'name' => 'Accidental background global row',
        'team_id' => null,
    ]))->toThrow(LogicException::class, 'Use createGlobal() or createGlobalForSystem()');

    $global = ExplicitNullSharedCustomResource::createGlobalForSystem([
        'name' => 'Intentional background global row',
    ]);
    $firstOrCreated = ExplicitNullSharedCustomResource::firstOrCreateGlobalForSystem(
        ['name' => 'Intentional first-or-create row'],
        ['team_id' => 12345],
    );

    expect($global->getAttribute('team_id'))->toBeNull()
        ->and($firstOrCreated->getAttribute('team_id'))->toBeNull();
});

it('updates a global custom row through the trusted system contract', function () {
    $global = ExplicitNullSharedCustomResource::createGlobalForSystem([
        'name' => 'Old catalog value',
    ]);

    $updated = ExplicitNullSharedCustomResource::updateOrCreateGlobalForSystem(
        ['id' => $global->id],
        ['name' => 'New catalog value', 'team_id' => 12345],
    );

    expect($updated->id)->toBe($global->id)
        ->and($updated->name)->toBe('New catalog value')
        ->and($updated->getAttribute('team_id'))->toBeNull();
});

it('restores the global-write invariant after a model event throws an Error', function () {
    expect(fn () => ThrowingGlobalCustomResource::createGlobalForSystem([
        'name' => 'Throwing global row',
    ]))->toThrow(Error::class, 'global write failure');

    expect(Resource::isGlobalWriteInProgress())->toBeFalse();
});
