<?php

use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

require_once __DIR__.'/helpers.php';

uses(RefreshDatabase::class);

/**
 * Registration hardening (issue #54, user stories 1 & 34-38).
 *
 * Auth/RegistrationTest already covers the disabled-registration 404s, required
 * fields and exact-case email uniqueness. These complement the tenant-isolation
 * and case-variant edges: two independent registrants must not see each other's
 * users or content, and a case-different email must not mint a duplicate account.
 */
beforeEach(function () {
    if (! Schema::hasTable('teams')) {
        $this->markTestSkipped('Registration-with-team tests require the teams schema (teams-on only).');
    }

    config(['aura.auth.registration' => true]);
});

it('allows two registrants to choose the same team name (distinct teams)', function () {
    hardeningRegisterGuest('First Owner', 'Acme', 'owner-one@example.com');
    hardeningRegisterGuest('Second Owner', 'Acme', 'owner-two@example.com');

    $teams = Team::withoutGlobalScopes()->where('name', 'Acme')->get();

    expect($teams)->toHaveCount(2);
    expect($teams->pluck('id')->unique())->toHaveCount(2);
});

it('isolates each registrant\'s content behind the tenant boundary', function () {
    hardeningRegisterGuest('Owner A', 'Team A', 'iso-a@example.com');
    $ownerA = User::withoutGlobalScopes()->where('email', 'iso-a@example.com')->first();
    $teamA = $ownerA->current_team_id;

    // Owner A is logged in with team A current: create a post in team A.
    Post::create([
        'type' => 'Post', 'title' => 'A secret', 'slug' => 'a-secret',
        'name' => 'A secret', 'description' => 'A only', 'fields' => [],
    ]);

    hardeningRegisterGuest('Owner B', 'Team B', 'iso-b@example.com');
    $ownerB = User::withoutGlobalScopes()->where('email', 'iso-b@example.com')->first();
    $teamB = $ownerB->current_team_id;

    expect($teamA)->not->toBe($teamB);

    // Now acting as Owner B (auto-logged-in), team B is current: A's post is invisible.
    expect(Post::count())->toBe(0);

    // Back as Owner A: exactly their own post is visible.
    $this->actingAs($ownerA);
    expect(Post::count())->toBe(1);

    // The Users index is team-scoped: A does not see B in their team context.
    $visibleToA = app(config('aura.resources.user'))
        ->indexQuery(User::query())
        ->pluck('email');

    expect($visibleToA)->toContain('iso-a@example.com');
    expect($visibleToA)->not->toContain('iso-b@example.com');
});

it('rejects a case-variant email at registration (no duplicate account)', function () {
    User::factory()->create(['email' => 'casereg@example.com']);

    $before = User::withoutGlobalScopes()->count();

    auth()->logout();
    $this->post(route('aura.register'), [
        'name' => 'Case Variant',
        'team' => 'Case Team',
        'email' => 'CaseReg@Example.com',
        'password' => 'Password123!XX',
        'password_confirmation' => 'Password123!XX',
    ])->assertSessionHasErrors('email');

    // Case-insensitive uniqueness (consistent with case-insensitive invitation
    // matching): no second account is minted for the same address.
    expect(User::withoutGlobalScopes()->count())->toBe($before);
    expect(
        User::withoutGlobalScopes()
            ->whereRaw('LOWER(email) = ?', ['casereg@example.com'])
            ->count()
    )->toBe(1);
});

it('registers a fresh registrant into their own team as super admin', function () {
    hardeningRegisterGuest('Solo Owner', 'Solo Team', 'solo@example.com');

    $owner = User::withoutGlobalScopes()->where('email', 'solo@example.com')->first();

    expect($owner->current_team_id)->not->toBeNull();
    expect($owner->isSuperAdmin())->toBeTrue();

    // Exactly one Membership, in their own team.
    $memberships = DB::table('user_role')->where('user_id', $owner->id)->get();
    expect($memberships)->toHaveCount(1);
    expect($memberships->first()->team_id)->toBe($owner->current_team_id);
});
