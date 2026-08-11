<?php

use Aura\Base\Livewire\Table\Traits\QueryFilters;
use Aura\Base\Models\Scopes\TeamScope;
use Aura\Base\Policies\ResourcePolicy;
use Aura\Base\Resources\Role;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Resources\Post;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

beforeEach(function () {
    $this->actingAs($this->user = createSuperAdmin());
});

it('fails closed for tenant models when the user has no current team', function () {
    $teamId = $this->user->current_team_id;

    Post::withoutGlobalScope(TeamScope::class)->create([
        'title' => 'Tenant Post',
        'type' => 'Post',
        'status' => 'publish',
        'team_id' => $teamId,
    ]);

    // Clear team context without logging out.
    DB::table('users')->where('id', $this->user->id)->update(['current_team_id' => null]);
    Cache::forget(User::currentTeamCacheKey($this->user->id));
    $this->user->refresh();

    expect(Post::query()->count())->toBe(0)
        ->and(Post::query()->toSql())->toContain('1 = 0');
});

it('restricts non-global-admin user queries to self when no current team is set', function () {
    $other = User::factory()->create();

    DB::table('users')->where('id', $this->user->id)->update(['current_team_id' => null]);
    Cache::forget(User::currentTeamCacheKey($this->user->id));
    $this->user->refresh();

    $ids = User::query()->pluck('id')->all();

    expect($ids)->toBe([$this->user->id])
        ->and($ids)->not->toContain($other->id);
});

it('fails closed for Role queries when the user has no current team', function () {
    // Ensure the catalog has at least one global role from bootstrap/seeding.
    expect(Role::withoutGlobalScope(TeamScope::class)->count())->toBeGreaterThan(0);

    DB::table('users')->where('id', $this->user->id)->update(['current_team_id' => null]);
    Cache::forget(User::currentTeamCacheKey($this->user->id));
    $this->user->refresh();

    expect(Role::query()->count())->toBe(0)
        ->and(Role::query()->toSql())->toContain('1 = 0');
});

it('rejects table filters with an unknown operator via fail-closed default', function () {
    $harness = new class
    {
        use QueryFilters;

        public $filters = ['custom' => []];

        public $model;

        public function runOperator(Builder $query, array $filter): void
        {
            $this->applyOperatorCondition($query, $filter);
        }

        public function runTable(Builder $query, array $filter): Builder
        {
            return $this->applyTableFieldFilter($query, $filter);
        }

        public function valid(array $filter): bool
        {
            return $this->isValidFilter($filter);
        }
    };

    $query = Post::query();
    $harness->runOperator($query, [
        'name' => 'title',
        'operator' => 'drop_table',
        'value' => 'x',
    ]);

    expect($query->toSql())->toContain('1 = 0');

    $tableQuery = Post::query();
    $harness->runTable($tableQuery, [
        'name' => 'title',
        'operator' => 'not_a_real_operator',
        'value' => 'x',
    ]);

    expect($tableQuery->toSql())->toContain('1 = 0');

    expect($harness->valid(['name' => 'title', 'value' => 'x']))->toBeFalse()
        ->and($harness->valid(['name' => 'title', 'operator' => '', 'value' => 'x']))->toBeFalse()
        ->and($harness->valid(['name' => 'title', 'operator' => 'contains', 'value' => 'x']))->toBeTrue();
});

it('fails closed for malformed custom filter payloads', function () {
    $harness = new class
    {
        use QueryFilters;

        public $filters = ['custom' => ['not-a-group' => true]];

        public $model;

        public function run(Builder $query): Builder
        {
            return $this->applyCustomFilter($query);
        }
    };

    $query = Post::query();
    $harness->run($query);

    expect($query->toSql())->toContain('1 = 0');
});

it('denies policy abilities when actor and resource use different connections', function () {
    $policy = new ResourcePolicy;

    // Same connection should still authorize for a super admin.
    expect($policy->view($this->user, new Post))->toBeTrue();

    // Pin the resource to a connection name that cannot match the actor.
    $resource = new Post;
    $resource->setConnection('foreign_security_connection');

    expect($this->user->getConnectionName())->not->toBe($resource->getConnectionName())
        ->and($policy->view($this->user, $resource))->toBeFalse()
        ->and($policy->update($this->user, $resource))->toBeFalse()
        ->and($policy->delete($this->user, $resource))->toBeFalse()
        ->and($policy->create($this->user, $resource))->toBeFalse()
        ->and($policy->viewAny($this->user, $resource))->toBeFalse()
        ->and($policy->restore($this->user, $resource))->toBeFalse()
        ->and($policy->forceDelete($this->user, $resource))->toBeFalse();
});
