<?php

use Aura\Base\Resource;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
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

it('rejects explicitly null team and creator values in ordinary creates', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    expect(fn () => Post::withoutGlobalScopes()->create([
        'title' => 'Unowned global candidate',
        'team_id' => null,
        'user_id' => null,
    ]))->toThrow(LogicException::class);

    expect(fn () => ExplicitNullSharedCustomResource::withoutGlobalScopes()->create([
        'name' => 'Ordinary custom create',
        'team_id' => null,
        'user_id' => null,
    ]))->toThrow(LogicException::class);
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

it('rejects foreign tenancy and ownership during ordinary post and role creates', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $otherOwner = User::factory()->create();
    $otherTeam = Team::factory()->createQuietly(['user_id' => $otherOwner->id]);

    expect(fn () => Post::withoutGlobalScopes()->create([
        'title' => 'Foreign team injection',
        'team_id' => $otherTeam->id,
        'user_id' => $actor->id,
    ]))->toThrow(LogicException::class);

    expect(fn () => Post::withoutGlobalScopes()->create([
        'title' => 'Foreign owner injection',
        'team_id' => $actor->current_team_id,
        'user_id' => $otherOwner->id,
    ]))->toThrow(LogicException::class);

    expect(fn () => Role::withoutGlobalScopes()->create([
        'name' => 'Foreign role',
        'slug' => 'foreign-role',
        'team_id' => $otherTeam->id,
        'permissions' => [],
    ]))->toThrow(LogicException::class);

    $owned = Post::withoutGlobalScopes()->create([
        'title' => 'Matching tenant and owner',
        'team_id' => $actor->current_team_id,
        'user_id' => $actor->id,
    ]);

    expect($owned->team_id)->toBe($actor->current_team_id)
        ->and($owned->user_id)->toBe($actor->id);
});

it('rejects foreign tenancy and ownership during direct fill and update', function () {
    $actor = createSuperAdmin();
    $this->actingAs($actor);

    $otherOwner = User::factory()->create();
    $otherTeam = Team::factory()->createQuietly(['user_id' => $otherOwner->id]);
    $first = Post::withoutGlobalScopes()->create([
        'title' => 'Direct fill target',
        'team_id' => $actor->current_team_id,
        'user_id' => $actor->id,
    ]);
    $second = Post::withoutGlobalScopes()->create([
        'title' => 'Direct update target',
        'team_id' => $actor->current_team_id,
        'user_id' => $actor->id,
    ]);

    expect(function () use ($first, $otherTeam, $otherOwner): void {
        $first->fill([
            'team_id' => $otherTeam->id,
            'user_id' => $otherOwner->id,
        ]);
        $first->save();
    })->toThrow(LogicException::class);

    expect(fn () => $second->update([
        'team_id' => $otherTeam->id,
        'user_id' => $otherOwner->id,
    ]))->toThrow(LogicException::class);

    expect($first->fresh()->team_id)->toBe($actor->current_team_id)
        ->and($first->fresh()->user_id)->toBe($actor->id)
        ->and($second->fresh()->team_id)->toBe($actor->current_team_id)
        ->and($second->fresh()->user_id)->toBe($actor->id);
});

it('supports explicit trusted team creation and movement for infrastructure', function () {
    $actor = createSuperAdmin();
    $otherOwner = User::factory()->create();
    $otherTeam = Team::factory()->createQuietly(['user_id' => $otherOwner->id]);

    auth()->logout();

    $post = Post::createForTeamForSystem($otherTeam->id, [
        'title' => 'Trusted team post',
        'user_id' => $otherOwner->id,
    ]);
    $role = Role::createForTeamForSystem($otherTeam->id, [
        'name' => 'Trusted team role',
        'slug' => 'trusted-team-role',
        'permissions' => [],
    ]);

    expect($post->team_id)->toBe($otherTeam->id)
        ->and($post->user_id)->toBe($otherOwner->id)
        ->and($role->team_id)->toBe($otherTeam->id);

    expect($post->moveToTeamForSystem($actor->current_team_id, [
        'user_id' => $actor->id,
    ]))->toBeTrue();

    expect($post->refresh()->team_id)->toBe($actor->current_team_id)
        ->and($post->user_id)->toBe($actor->id);

    $this->actingAs($actor);

    $ownerOnly = Post::createForOwnerForSystem($otherOwner->id, [
        'title' => 'Trusted owner post',
        'team_id' => $actor->current_team_id,
    ]);

    expect($ownerOnly->user_id)->toBe($otherOwner->id)
        ->and($ownerOnly->assignOwnerForSystem($actor->id))->toBeTrue()
        ->and($ownerOnly->fresh()->user_id)->toBe($actor->id);
});
