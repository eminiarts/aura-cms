<?php

use Aura\Base\Contracts\GlobalSearchAdapter;
use Aura\Base\Exceptions\GlobalSearchExecutionTimedOut;
use Aura\Base\Exceptions\GlobalSearchExecutionUnavailable;
use Aura\Base\Facades\Aura;
use Aura\Base\GlobalSearch\DatabaseGlobalSearchAdapter;
use Aura\Base\GlobalSearch\DatabaseStatementDeadline;
use Aura\Base\GlobalSearch\ForkedGlobalSearchExecutor;
use Aura\Base\GlobalSearch\GlobalSearchBudget;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Models\Meta;
use Aura\Base\Resource;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Symfony\Component\Process\Process;

abstract class HardeningSearchResource extends Resource
{
    public static $singularName = 'Hardening Search Record';

    public static ?string $slug = 'hardening-search-record';

    public static string $type = 'HardeningSearchRecord';

    protected static array $searchable = ['title'];

    public static function getFields()
    {
        return [
            [
                'name' => 'Title',
                'type' => 'Aura\\Base\\Fields\\Text',
                'searchable' => true,
                'slug' => 'title',
            ],
        ];
    }

    public function title()
    {
        return $this->title;
    }
}

class HardeningAllowedResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-allowed';

    public static string $type = 'HardeningAllowed';
}

class HardeningDeniedResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-denied';

    public static string $type = 'HardeningDenied';
}

class HardeningRowPolicyResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-row-policy';

    public static string $type = 'HardeningRowPolicy';
}

class HardeningSqlVisibilityResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-sql-visibility';

    public static string $type = 'HardeningSqlVisibility';

    public static bool $visibilityApplied = false;

    public function applyGlobalSearchVisibility($query, $user)
    {
        static::$visibilityApplied = true;

        return $query->where('title', 'not like', 'SQL Hidden%');
    }
}

class HardeningUnscopedResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-unscoped';

    public static string $type = 'HardeningUnscoped';
}

class HardeningTrustedUnscopedResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-trusted-unscoped';

    public static string $type = 'HardeningTrustedUnscoped';

    public function globalSearchAllowsMissingTeamContext($user)
    {
        return true;
    }
}

class HardeningExternalUrlResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-external-url';

    public static string $type = 'HardeningExternalUrl';

    public function globalSearchUrl(): ?string
    {
        return 'https://attacker.example/collect';
    }
}

class HardeningJavascriptUrlResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-javascript-url';

    public static string $type = 'HardeningJavascriptUrl';

    public function globalSearchUrl(): ?string
    {
        return 'javascript:alert(1)';
    }
}

class HardeningDataUrlResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-data-url';

    public static string $type = 'HardeningDataUrl';

    public function globalSearchUrl(): ?string
    {
        return 'data:text/html,unsafe';
    }
}

class HardeningProtocolRelativeUrlResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-protocol-relative-url';

    public static string $type = 'HardeningProtocolRelativeUrl';

    public function globalSearchUrl(): ?string
    {
        return '//attacker.example/collect';
    }
}

class HardeningOpenRedirectUrlResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-open-redirect-url';

    public static string $type = 'HardeningOpenRedirectUrl';

    public function globalSearchUrl(): ?string
    {
        return '/admin/hardening-open-redirect-url/'.$this->getKey().'?redirect=https%3A%2F%2Fattacker.example';
    }
}

class HardeningRelativeUrlResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-relative-url';

    public static string $type = 'HardeningRelativeUrl';

    public function globalSearchUrl(): ?string
    {
        return '/admin/hardening-relative-url/'.$this->getKey();
    }
}

class HardeningSameOriginUrlResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-same-origin-url';

    public static string $type = 'HardeningSameOriginUrl';

    public function globalSearchUrl(): ?string
    {
        return url('/admin/hardening-same-origin-url/'.$this->getKey());
    }
}

class HardeningNamedDestinationResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-named-destination';

    public static string $type = 'HardeningNamedDestination';

    public function globalSearchDestination()
    {
        return [
            'route' => 'aura.'.static::getSlug().'.view',
            'parameters' => ['id' => $this->getKey()],
        ];
    }

    public function globalSearchUrl(): ?string
    {
        return 'javascript:alert(1)';
    }
}

class HardeningMetaTitleResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-meta-title';

    public static string $type = 'HardeningMetaTitle';

    public static function getFields()
    {
        return [
            ...parent::getFields(),
            [
                'name' => 'Label',
                'type' => 'Aura\\Base\\Fields\\Text',
                'slug' => 'label',
            ],
        ];
    }

    public function globalSearchTitleDependencies()
    {
        return ['meta' => ['label'], 'relations' => []];
    }

    public function title()
    {
        return $this->label;
    }
}

class HardeningMalformedTitleResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-malformed-title';

    public static string $type = 'HardeningMalformedTitle';

    public function title()
    {
        return ['not', 'a', 'title'];
    }
}

class HardeningRelationTitleResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-relation-title';

    public static string $type = 'HardeningRelationTitle';

    public function globalSearchTitleDependencies()
    {
        return ['meta' => [], 'relations' => ['user']];
    }

    public function title()
    {
        return $this->user->name;
    }
}

class HardeningMysqlUnicodeResource extends HardeningSearchResource
{
    public static $customTable = true;

    public static ?string $slug = 'hardening-mysql-unicode';

    public static string $type = 'HardeningMysqlUnicode';

    public static bool $usesMeta = false;

    protected $connection = 'global_search_mysql';

    protected $fillable = ['title', 'type', 'status', 'user_id', 'team_id'];

    protected $table = 'global_search_unicode';
}

class HardeningFailingAdapterResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-failing-query';

    public static string $type = 'HardeningFailingQuery';

    public function globalSearchAdapter()
    {
        return HardeningFailingAdapter::class;
    }
}

class HardeningSuccessfulAdapterResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-successful-query';

    public static string $type = 'HardeningSuccessfulQuery';

    public function globalSearchAdapter()
    {
        return HardeningSuccessfulAdapter::class;
    }
}

class HardeningSleepingAdapterResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-sleeping-query';

    public static string $type = 'HardeningSleepingQuery';

    public function globalSearchAdapter()
    {
        return HardeningSleepingAdapter::class;
    }
}

class HardeningSecondSleepingAdapterResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-second-sleeping-query';

    public static string $type = 'HardeningSecondSleepingQuery';

    public function globalSearchAdapter()
    {
        return HardeningSleepingAdapter::class;
    }
}

class HardeningCpuAdapterResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-cpu-query';

    public static string $type = 'HardeningCpuQuery';

    public function globalSearchAdapter()
    {
        return HardeningCpuAdapter::class;
    }
}

class HardeningBlockingAdapterResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-blocking-query';

    public static string $type = 'HardeningBlockingQuery';

    public function globalSearchAdapter()
    {
        return HardeningBlockingAdapter::class;
    }
}

class HardeningFailingAdapter implements GlobalSearchAdapter
{
    public function search(
        Resource $resource,
        Builder $query,
        Collection $fields,
        string $term,
        int $candidateLimit,
        GlobalSearchBudget $budget,
    ): Collection {
        throw new RuntimeException('Simulated search backend failure.');
    }
}

class HardeningSuccessfulAdapter implements GlobalSearchAdapter
{
    public function search(
        Resource $resource,
        Builder $query,
        Collection $fields,
        string $term,
        int $candidateLimit,
        GlobalSearchBudget $budget,
    ): Collection {
        return app(DatabaseGlobalSearchAdapter::class)->search(
            $resource,
            $query,
            $fields,
            $term,
            $candidateLimit,
            $budget,
        );
    }
}

class HardeningSleepingAdapter implements GlobalSearchAdapter
{
    public function search(
        Resource $resource,
        Builder $query,
        Collection $fields,
        string $term,
        int $candidateLimit,
        GlobalSearchBudget $budget,
    ): Collection {
        usleep(1_200_000);

        throw new RuntimeException('Simulated blocked search backend.');
    }
}

class HardeningCpuAdapter implements GlobalSearchAdapter
{
    public function search(
        Resource $resource,
        Builder $query,
        Collection $fields,
        string $term,
        int $candidateLimit,
        GlobalSearchBudget $budget,
    ): Collection {
        $deadline = hrtime(true) + 1_200_000_000;
        $value = $term;

        while (hrtime(true) < $deadline) {
            $value = hash('sha256', $value);
        }

        throw new RuntimeException("Simulated CPU-bound search backend: {$value}");
    }
}

class HardeningBlockingAdapter implements GlobalSearchAdapter
{
    public function search(
        Resource $resource,
        Builder $query,
        Collection $fields,
        string $term,
        int $candidateLimit,
        GlobalSearchBudget $budget,
    ): Collection {
        $sockets = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

        if ($sockets === false) {
            throw new RuntimeException('Unable to construct a blocking adapter fixture.');
        }

        fread($sockets[0], 1);

        throw new RuntimeException('Simulated blocking search backend returned unexpectedly.');
    }
}

class HardeningSearchPolicy
{
    public function view(User $user, Resource $resource): bool
    {
        return ! str_starts_with((string) $resource->getAttribute('title'), 'Policy Denied');
    }

    public function viewAny(User $user, Resource $resource): bool
    {
        return ! $resource instanceof HardeningDeniedResource;
    }
}

function registerHardeningSearchResources(array $resources): void
{
    Aura::fake();
    Aura::registerResources($resources);

    foreach ($resources as $resource) {
        if (! (new ReflectionClass($resource))->isInstantiable()) {
            continue;
        }

        Aura::registerRoutes($resource::getSlug(), $resource);
    }

    $firstInstantiable = collect($resources)
        ->first(fn (string $resource): bool => (new ReflectionClass($resource))->isInstantiable());

    if ($firstInstantiable) {
        Aura::setModel(new $firstInstantiable);
    }
}

beforeEach(function () {
    config([
        'aura.features.global_search' => true,
        'aura.global_search.candidate_limit' => 25,
        'aura.global_search.max_queries_per_resource' => 4,
        'aura.global_search.max_total_queries' => 50,
    ]);

    HardeningSqlVisibilityResource::$visibilityApplied = false;
    $this->actingAs($this->searchUser = createSuperAdmin());
});

test('viewAny authorization happens before the searchable resource cap', function () {
    config(['aura.global_search.max_resources' => 1]);
    registerHardeningSearchResources([
        HardeningDeniedResource::class,
        HardeningAllowedResource::class,
    ]);
    Gate::policy(HardeningDeniedResource::class, HardeningSearchPolicy::class);
    Gate::policy(HardeningAllowedResource::class, HardeningSearchPolicy::class);

    HardeningDeniedResource::create(['title' => 'Authorization Cap Needle Denied']);
    HardeningAllowedResource::create(['title' => 'Authorization Cap Needle Allowed']);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Authorization Cap Needle')
        ->assertDontSee('Authorization Cap Needle Denied')
        ->assertSee('Authorization Cap Needle Allowed');
});

test('sql visibility is applied before the candidate limit', function () {
    config([
        'aura.global_search.candidate_limit' => 1,
        'aura.global_search.per_resource_limit' => 1,
    ]);
    registerHardeningSearchResources([HardeningSqlVisibilityResource::class]);

    HardeningSqlVisibilityResource::create(['title' => 'SQL Hidden Needle']);
    HardeningSqlVisibilityResource::create(['title' => 'SQL Visible Needle']);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Needle')
        ->assertDontSee('SQL Hidden Needle')
        ->assertSee('SQL Visible Needle');

    expect(HardeningSqlVisibilityResource::$visibilityApplied)->toBeTrue();
});

test('row policy denials do not consume result slots inside the candidate budget', function () {
    config([
        'aura.global_search.candidate_limit' => 5,
        'aura.global_search.per_resource_limit' => 2,
    ]);
    registerHardeningSearchResources([HardeningRowPolicyResource::class]);
    Gate::policy(HardeningRowPolicyResource::class, HardeningSearchPolicy::class);

    HardeningRowPolicyResource::create(['title' => 'Policy Denied Needle 1']);
    HardeningRowPolicyResource::create(['title' => 'Policy Denied Needle 2']);
    HardeningRowPolicyResource::create(['title' => 'Policy Allowed Needle 1']);
    HardeningRowPolicyResource::create(['title' => 'Policy Allowed Needle 2']);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Policy')
        ->assertDontSee('Policy Denied Needle')
        ->assertSee('Policy Allowed Needle 1')
        ->assertSee('Policy Allowed Needle 2');
});

test('teams mode fails closed without a current team unless a resource explicitly opts in', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('Missing-team isolation only applies when teams are enabled.');
    }

    registerHardeningSearchResources([
        HardeningUnscopedResource::class,
        HardeningTrustedUnscopedResource::class,
    ]);

    $team = Team::factory()->createQuietly();
    HardeningUnscopedResource::withoutGlobalScopes()->create([
        'team_id' => $team->getKey(),
        'type' => HardeningUnscopedResource::getType(),
        'title' => 'Null Team Hidden Needle',
    ]);
    HardeningTrustedUnscopedResource::withoutGlobalScopes()->create([
        'team_id' => $team->getKey(),
        'type' => HardeningTrustedUnscopedResource::getType(),
        'title' => 'Null Team Trusted Needle',
    ]);

    $operator = User::factory()->create(['current_team_id' => null]);
    $operator->forceFill(['global_admin' => true])->saveQuietly();
    $this->actingAs($operator->refresh());

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Null Team')
        ->assertDontSee('Null Team Hidden Needle')
        ->assertSee('Null Team Trusted Needle');
});

test('legacy destinations reject unsafe schemes and external origins', function () {
    $resources = [
        HardeningExternalUrlResource::class,
        HardeningJavascriptUrlResource::class,
        HardeningDataUrlResource::class,
        HardeningProtocolRelativeUrlResource::class,
        HardeningOpenRedirectUrlResource::class,
        HardeningRelativeUrlResource::class,
        HardeningSameOriginUrlResource::class,
    ];
    registerHardeningSearchResources($resources);

    foreach ($resources as $resource) {
        $resource::create(['title' => $resource::getType().' Destination Needle']);
    }

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Destination Needle')
        ->assertDontSee('HardeningExternalUrl Destination Needle')
        ->assertDontSee('HardeningJavascriptUrl Destination Needle')
        ->assertDontSee('HardeningDataUrl Destination Needle')
        ->assertDontSee('HardeningProtocolRelativeUrl Destination Needle')
        ->assertDontSee('HardeningOpenRedirectUrl Destination Needle')
        ->assertSee('HardeningRelativeUrl Destination Needle')
        ->assertSee('HardeningSameOriginUrl Destination Needle');
});

test('an explicit named destination wins over a legacy arbitrary url', function () {
    registerHardeningSearchResources([HardeningNamedDestinationResource::class]);
    $result = HardeningNamedDestinationResource::create(['title' => 'Named Destination Needle']);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Named Destination Needle')
        ->assertSeeHtml('href="'.route('aura.hardening-named-destination.view', ['id' => $result->getKey()]).'"')
        ->assertDontSeeHtml('javascript:alert(1)');
});

test('candidate search never eager loads unrelated meta rows', function () {
    registerHardeningSearchResources([HardeningAllowedResource::class]);
    $result = HardeningAllowedResource::create(['title' => 'Meta Hydration Needle']);

    foreach (array_chunk(range(1, 2000), 250) as $keys) {
        DB::table('meta')->insert(array_map(fn (int $key): array => [
            'key' => 'unrelated_'.$key,
            'value' => 'unrelated value',
            'metable_type' => $result->getMorphClass(),
            'metable_id' => $result->getKey(),
        ], $keys));
    }

    $retrievedMeta = 0;
    $metaQueries = 0;
    Event::listen('eloquent.retrieved: '.Meta::class, function () use (&$retrievedMeta): void {
        $retrievedMeta++;
    });
    DB::listen(function ($query) use (&$metaQueries): void {
        if (str_contains($query->sql, 'from "meta"')) {
            $metaQueries++;
        }
    });

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Meta Hydration Needle')
        ->assertSee('Meta Hydration Needle');

    expect($retrievedMeta)->toBe(0)
        ->and($metaQueries)->toBe(0);
});

test('declared title meta dependencies load only their retained keys', function () {
    registerHardeningSearchResources([HardeningMetaTitleResource::class]);
    $result = HardeningMetaTitleResource::create([
        'title' => 'Declared Dependency Needle',
        'label' => 'Safe Presentation Label',
    ]);

    DB::table('meta')->insert([
        'key' => 'unrelated_title_data',
        'value' => 'Must not hydrate',
        'metable_type' => $result->getMorphClass(),
        'metable_id' => $result->getKey(),
    ]);

    $metaSql = [];
    DB::listen(function ($query) use (&$metaSql): void {
        if (str_contains($query->sql, 'from "meta"')) {
            $metaSql[] = [$query->sql, $query->bindings];
        }
    });

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Declared Dependency Needle')
        ->assertSee('Safe Presentation Label')
        ->assertDontSee('Must not hydrate');

    expect($metaSql)->toHaveCount(1)
        ->and(collect($metaSql[0][1]))->toContain('label')
        ->not->toContain('unrelated_title_data');
});

test('declared direct title relations are loaded within the query budget', function () {
    registerHardeningSearchResources([HardeningRelationTitleResource::class]);
    HardeningRelationTitleResource::create([
        'title' => 'Declared Relation Needle',
        'user_id' => $this->searchUser->getKey(),
    ]);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Declared Relation Needle')
        ->assertSee($this->searchUser->name);
});

test('the default adapter bounds adversarial table work and exposes an indexed plan', function () {
    config(['aura.global_search.candidate_limit' => 25]);
    registerHardeningSearchResources([HardeningAllowedResource::class]);

    $timestamp = now();
    $rows = collect(range(1, 200))->map(fn (int $index): array => [
        'title' => $index === 200 ? 'Late Adversarial Needle' : "Ordinary row {$index}",
        'content' => null,
        'type' => HardeningAllowedResource::getType(),
        'status' => 'publish',
        'user_id' => $this->searchUser->getKey(),
        ...config('aura.teams') ? ['team_id' => $this->searchUser->current_team_id] : [],
        'created_at' => $timestamp,
        'updated_at' => $timestamp,
    ])->all();

    foreach (array_chunk($rows, 100) as $chunk) {
        DB::table('posts')->insert($chunk);
    }

    $candidateQueries = [];
    DB::listen(function ($query) use (&$candidateQueries): void {
        if (str_contains($query->sql, 'from "posts"') && str_contains($query->sql, 'limit')) {
            $candidateQueries[] = [$query->sql, $query->bindings];
        }
    });

    $component = app(GlobalSearch::class);
    $component->search = 'Late Adversarial Needle';

    expect($component->getSearchResultsProperty())->toBeEmpty()
        ->and($candidateQueries)->toHaveCount(1)
        ->and(strtolower($candidateQueries[0][0]))->not->toContain(' like ')
        ->and(strtolower($candidateQueries[0][0]))->not->toContain('lower(');

    $plan = DB::select('EXPLAIN QUERY PLAN '.$candidateQueries[0][0], $candidateQueries[0][1]);

    expect(strtolower(collect($plan)->pluck('detail')->implode(' ')))->toContain('index');
});

test('the total query budget stops later resources', function () {
    config([
        'aura.global_search.max_total_queries' => 1,
        'aura.global_search.max_queries_per_resource' => 1,
    ]);
    registerHardeningSearchResources([
        HardeningAllowedResource::class,
        HardeningDeniedResource::class,
    ]);

    HardeningAllowedResource::create(['title' => 'Total Budget Needle First']);
    HardeningDeniedResource::create(['title' => 'Total Budget Needle Second']);

    $results = app(GlobalSearch::class);
    $results->search = 'Total Budget Needle';

    expect($results->getSearchResultsProperty())
        ->toHaveKey(HardeningAllowedResource::getType())
        ->not->toHaveKey(HardeningDeniedResource::getType());
});

test('a successful custom adapter returns bounded results through isolation', function () {
    if (! (new ForkedGlobalSearchExecutor)->isAvailable()) {
        $this->markTestSkipped('Forked global-search execution is unavailable on this platform.');
    }

    registerHardeningSearchResources([HardeningSuccessfulAdapterResource::class]);
    HardeningSuccessfulAdapterResource::create(['title' => 'Custom Adapter Needle']);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Custom Adapter Needle')
        ->assertSee('Custom Adapter Needle');
});

test('a failing resource is isolated from later searchable resources', function () {
    registerHardeningSearchResources([
        HardeningFailingAdapterResource::class,
        HardeningAllowedResource::class,
    ]);

    HardeningFailingAdapterResource::create(['title' => 'Failure Isolation Needle Broken']);
    HardeningAllowedResource::create(['title' => 'Failure Isolation Needle Healthy']);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Failure Isolation Needle')
        ->assertDontSee('Failure Isolation Needle Broken')
        ->assertSee('Failure Isolation Needle Healthy');
});

test('a sleeping adapter cannot delay a healthy later resource', function () {
    if (! (new ForkedGlobalSearchExecutor)->isAvailable()) {
        $this->markTestSkipped('Forked global-search execution is unavailable on this platform.');
    }

    config([
        'aura.global_search.per_resource_timeout_ms' => 100,
        'aura.global_search.total_timeout_ms' => 500,
    ]);
    registerHardeningSearchResources([
        HardeningSleepingAdapterResource::class,
        HardeningAllowedResource::class,
    ]);

    HardeningAllowedResource::create(['title' => 'Deadline Isolation Needle Healthy']);
    Log::spy();
    $startedAt = hrtime(true);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Deadline Isolation Needle')
        ->assertSee('Deadline Isolation Needle Healthy');

    expect((hrtime(true) - $startedAt) / 1_000_000_000)->toBeLessThan(0.6);

    Log::shouldHaveReceived('warning')->with(
        'Aura global search skipped a resource.',
        Mockery::on(fn (array $context): bool => $context['resource'] === HardeningSleepingAdapterResource::class
            && $context['reason'] === 'deadline_exceeded'
            && ! str_contains(json_encode($context, JSON_THROW_ON_ERROR), 'Deadline Isolation Needle')),
    )->atLeast()->once();

    $secondRequestStartedAt = hrtime(true);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Deadline Isolation Needle')
        ->assertSee('Deadline Isolation Needle Healthy');

    expect((hrtime(true) - $secondRequestStartedAt) / 1_000_000_000)->toBeLessThan(0.6);
});

test('a CPU-bound adapter cannot delay a healthy later resource', function () {
    if (! (new ForkedGlobalSearchExecutor)->isAvailable()) {
        $this->markTestSkipped('Forked global-search execution is unavailable on this platform.');
    }

    config([
        'aura.global_search.per_resource_timeout_ms' => 100,
        'aura.global_search.total_timeout_ms' => 500,
    ]);
    registerHardeningSearchResources([
        HardeningCpuAdapterResource::class,
        HardeningAllowedResource::class,
    ]);
    HardeningAllowedResource::create(['title' => 'CPU Isolation Needle Healthy']);
    $startedAt = hrtime(true);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'CPU Isolation Needle')
        ->assertSee('CPU Isolation Needle Healthy');

    expect((hrtime(true) - $startedAt) / 1_000_000_000)->toBeLessThan(0.6);
});

test('a blocking adapter cannot delay a healthy later resource', function () {
    if (! (new ForkedGlobalSearchExecutor)->isAvailable()) {
        $this->markTestSkipped('Forked global-search execution is unavailable on this platform.');
    }

    config([
        'aura.global_search.per_resource_timeout_ms' => 100,
        'aura.global_search.total_timeout_ms' => 500,
    ]);
    registerHardeningSearchResources([
        HardeningBlockingAdapterResource::class,
        HardeningAllowedResource::class,
    ]);
    HardeningAllowedResource::create(['title' => 'Blocking Isolation Needle Healthy']);
    $startedAt = hrtime(true);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Blocking Isolation Needle')
        ->assertSee('Blocking Isolation Needle Healthy');

    expect((hrtime(true) - $startedAt) / 1_000_000_000)->toBeLessThan(0.6);
});

test('the total execution deadline stops later resources', function () {
    if (! (new ForkedGlobalSearchExecutor)->isAvailable()) {
        $this->markTestSkipped('Forked global-search execution is unavailable on this platform.');
    }

    config([
        'aura.global_search.per_resource_timeout_ms' => 120,
        'aura.global_search.total_timeout_ms' => 170,
    ]);
    registerHardeningSearchResources([
        HardeningSleepingAdapterResource::class,
        HardeningSecondSleepingAdapterResource::class,
        HardeningAllowedResource::class,
    ]);
    HardeningAllowedResource::create(['title' => 'Total Deadline Needle Healthy']);
    $component = app(GlobalSearch::class);
    $component->search = 'Total Deadline Needle';
    $startedAt = hrtime(true);
    $results = $component->getSearchResultsProperty();

    expect($results)->not->toHaveKey(HardeningAllowedResource::getType())
        ->and((hrtime(true) - $startedAt) / 1_000_000_000)->toBeLessThan(0.6);
});

test('custom adapters fail closed when process isolation is unavailable', function (string $backend) {
    config(['aura.global_search.execution_backend' => $backend]);
    registerHardeningSearchResources([
        HardeningSleepingAdapterResource::class,
        HardeningAllowedResource::class,
    ]);
    HardeningAllowedResource::create(['title' => 'Fallback Isolation Needle Healthy']);
    $startedAt = hrtime(true);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Fallback Isolation Needle')
        ->assertSee('Fallback Isolation Needle Healthy');

    expect((hrtime(true) - $startedAt) / 1_000_000_000)->toBeLessThan(0.4);
})->with(['disabled backend' => 'none', 'invalid backend' => 'invalid']);

test('custom adapters fail closed under an Octane runtime without state bleed', function () {
    app()->instance('octane', new stdClass);

    try {
        registerHardeningSearchResources([
            HardeningSleepingAdapterResource::class,
            HardeningAllowedResource::class,
        ]);
        HardeningAllowedResource::create(['title' => 'Octane Isolation Needle Healthy']);
        $startedAt = hrtime(true);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Octane Isolation Needle')
            ->assertSee('Octane Isolation Needle Healthy');

        expect((hrtime(true) - $startedAt) / 1_000_000_000)->toBeLessThan(0.4);
    } finally {
        app()->forgetInstance('octane');
    }
});

test('isolated execution rejects nesting and preserves parent signal state', function () {
    $executor = new ForkedGlobalSearchExecutor;

    if (! $executor->isAvailable()) {
        $this->markTestSkipped('Forked global-search execution is unavailable on this platform.');
    }

    $signalHandler = function_exists('pcntl_signal_get_handler')
        ? pcntl_signal_get_handler(SIGALRM)
        : null;

    expect($executor->run(
        fn (): bool => (new ForkedGlobalSearchExecutor)->isAvailable(),
        100,
        1_024,
    ))->toBeFalse()
        ->and($executor->run(fn (): string => 'healthy', 100, 1_024))->toBe('healthy');

    if (function_exists('pcntl_signal_get_handler')) {
        expect(pcntl_signal_get_handler(SIGALRM))->toBe($signalHandler);
    }
});

test('isolated execution enforces its deadline directly', function () {
    $executor = new ForkedGlobalSearchExecutor;

    if (! $executor->isAvailable()) {
        $this->markTestSkipped('Forked global-search execution is unavailable on this platform.');
    }

    $startedAt = hrtime(true);

    expect(fn () => $executor->run(function (): void {
        usleep(1_200_000);
    }, 100, 1_024))->toThrow(GlobalSearchExecutionTimedOut::class);

    expect((hrtime(true) - $startedAt) / 1_000_000_000)->toBeLessThan(0.5);
});

test('database statement deadlines restore nested sqlite session state after exceptions', function () {
    $connection = DB::connection();
    $readBusyTimeout = function () use ($connection): int {
        $row = (array) $connection->selectOne('PRAGMA busy_timeout');

        return (int) (reset($row) ?: 0);
    };
    $originalBusyTimeout = $readBusyTimeout();

    try {
        $connection->statement('PRAGMA busy_timeout = 0');
        (new DatabaseStatementDeadline)->run($connection, 200, function () use ($readBusyTimeout): void {
            expect($readBusyTimeout())->toBe(0);
        });
        expect($readBusyTimeout())->toBe(0);

        $connection->statement('PRAGMA busy_timeout = 1234');
        $deadline = new DatabaseStatementDeadline;

        $deadline->run($connection, 200, function () use ($connection, $deadline, $readBusyTimeout): void {
            expect($readBusyTimeout())->toBe(200);

            expect(fn () => $deadline->run($connection, 50, function () use ($readBusyTimeout): void {
                expect($readBusyTimeout())->toBe(50);

                throw new RuntimeException('Simulated statement failure.');
            }))->toThrow(RuntimeException::class, 'Simulated statement failure.');

            expect($readBusyTimeout())->toBe(200);
        });

        expect($readBusyTimeout())->toBe(1234);
    } finally {
        $connection->statement("PRAGMA busy_timeout = {$originalBusyTimeout}");
    }
});

test('database statement deadlines fail closed when a hard bound is unsupported', function (string $driver) {
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getDriverName')->once()->andReturn($driver);
    $callbackWasCalled = false;

    expect(fn () => (new DatabaseStatementDeadline)->run(
        $connection,
        150,
        function () use (&$callbackWasCalled): void {
            $callbackWasCalled = true;
        },
    ))->toThrow(GlobalSearchExecutionUnavailable::class)
        ->and($callbackWasCalled)->toBeFalse();
})->with(['unknown driver' => 'odbc', 'sub-second SQL Server deadline' => 'sqlsrv']);

test('a failed database deadline restoration disconnects the contaminated connection', function () {
    Log::spy();
    $connection = Mockery::mock(Connection::class);
    $connection->shouldReceive('getDriverName')->twice()->andReturn('sqlite');
    $connection->shouldReceive('selectOne')->once()->with('PRAGMA busy_timeout')->andReturn((object) [
        'busy_timeout' => 1_000,
    ]);
    $connection->shouldReceive('statement')->twice()->andReturn(true, false);
    $connection->shouldReceive('disconnect')->once();

    expect((new DatabaseStatementDeadline)->run(
        $connection,
        150,
        fn (): string => 'completed',
    ))->toBe('completed');

    Log::shouldHaveReceived('warning')->with(
        'Aura global search disconnected a database connection after deadline restoration failed.',
        [
            'driver' => 'sqlite',
            'exception' => GlobalSearchExecutionUnavailable::class,
        ],
    )->once();
});

test('abstract resources and malformed titles are rejected without breaking later resources', function () {
    registerHardeningSearchResources([
        HardeningSearchResource::class,
        HardeningMalformedTitleResource::class,
        HardeningAllowedResource::class,
    ]);

    HardeningMalformedTitleResource::create(['title' => 'Malformed Presentation Needle']);
    HardeningAllowedResource::create(['title' => 'Safe Presentation Needle']);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Presentation Needle')
        ->assertDontSee('Malformed Presentation Needle')
        ->assertSee('Safe Presentation Needle');
});

test('legacy untyped component overrides remain loadable', function () {
    $source = <<<'PHP'
require $argv[1];
class LegacyUntypedGlobalSearch extends \Aura\Base\Livewire\GlobalSearch
{
    public function getSearchResultsProperty() { return collect(); }
    public function mount() {}
    public function render() { return null; }
}
PHP;
    $process = new Process([PHP_BINARY, '-r', $source, base_path('vendor/autoload.php')]);
    $process->run();

    expect($process->isSuccessful())->toBeTrue($process->getErrorOutput());
});

test('server and client share the same defensive maximum query length', function () {
    config(['aura.global_search.maximum_query_length' => 'invalid']);
    registerHardeningSearchResources([HardeningAllowedResource::class]);
    HardeningAllowedResource::create(['title' => 'Maximum Query Needle']);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Maximum Query Needle')
        ->assertSee('Maximum Query Needle')
        ->assertSeeHtml('maxlength="64"');
});

test('unicode matching is deterministic and codepoint case sensitive on sqlite', function () {
    expect(DB::connection()->getDriverName())->toBe('sqlite');
    registerHardeningSearchResources([HardeningAllowedResource::class]);

    HardeningAllowedResource::create(['title' => 'Ärger Needle']);
    HardeningAllowedResource::create(['title' => 'ärger Needle']);
    HardeningAllowedResource::create(['title' => 'Alpha Needle']);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Ärger')
        ->assertSee('Ärger Needle')
        ->assertDontSee('ärger Needle');

    Livewire::test(GlobalSearch::class)
        ->set('search', 'alpha')
        ->assertDontSee('Alpha Needle');
});

test('unicode matching has the same semantics on mysql when a local service is available', function () {
    $host = (string) (env('AURA_TEST_MYSQL_HOST') ?: '127.0.0.1');
    $port = (int) (env('AURA_TEST_MYSQL_PORT') ?: 3306);
    $username = (string) (env('AURA_TEST_MYSQL_USERNAME') ?: 'root');
    $password = (string) (env('AURA_TEST_MYSQL_PASSWORD') ?: '');
    $database = 'aura_global_search_'.getmypid().'_'.strtolower(str()->random(8));

    try {
        $server = new PDO("mysql:host={$host};port={$port};charset=utf8mb4", $username, $password, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $server->exec("CREATE DATABASE `{$database}` CHARACTER SET utf8mb4 COLLATE utf8mb4_0900_ai_ci");
    } catch (Throwable $exception) {
        $this->markTestSkipped('MySQL service is unavailable: '.$exception->getMessage());
    }

    config(['database.connections.global_search_mysql' => [
        'driver' => 'mysql',
        'host' => $host,
        'port' => $port,
        'database' => $database,
        'username' => $username,
        'password' => $password,
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_0900_ai_ci',
        'prefix' => '',
        'strict' => true,
    ]]);

    try {
        Schema::connection('global_search_mysql')->create('global_search_unicode', function (Blueprint $table): void {
            $table->id();
            $table->text('title')->nullable();
            $table->string('type', 64)->index();
            $table->string('status')->nullable();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->unsignedBigInteger('team_id')->nullable();
            $table->timestamps();
            $table->softDeletes();
        });
        $mysqlConnection = DB::connection('global_search_mysql');
        $isMariaDb = str_contains(
            strtolower((string) $mysqlConnection->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION)),
            'mariadb',
        );
        $statementDeadline = new DatabaseStatementDeadline;

        if ($isMariaDb) {
            $originalTimeout = (float) data_get(
                (array) $mysqlConnection->selectOne('SELECT @@SESSION.max_statement_time AS value'),
                'value',
                0,
            );

            try {
                $mysqlConnection->statement('SET SESSION max_statement_time = 0.777');

                expect(fn () => $statementDeadline->run($mysqlConnection, 50, function () use ($mysqlConnection): void {
                    $activeTimeout = (float) data_get(
                        (array) $mysqlConnection->selectOne('SELECT @@SESSION.max_statement_time AS value'),
                        'value',
                        0,
                    );

                    expect($activeTimeout)->toEqualWithDelta(0.05, 0.001);

                    throw new RuntimeException('Simulated MariaDB statement failure.');
                }))->toThrow(RuntimeException::class, 'Simulated MariaDB statement failure.');

                $restoredTimeout = (float) data_get(
                    (array) $mysqlConnection->selectOne('SELECT @@SESSION.max_statement_time AS value'),
                    'value',
                    0,
                );
                expect($restoredTimeout)->toEqualWithDelta(0.777, 0.001);
            } finally {
                $mysqlConnection->statement(
                    'SET SESSION max_statement_time = '.number_format($originalTimeout, 3, '.', ''),
                );
            }
        } else {
            $originalTimeout = (int) data_get(
                (array) $mysqlConnection->selectOne('SELECT @@SESSION.MAX_EXECUTION_TIME AS value'),
                'value',
                0,
            );

            try {
                $mysqlConnection->statement('SET SESSION MAX_EXECUTION_TIME = 777');

                expect(fn () => $statementDeadline->run($mysqlConnection, 50, function () use ($mysqlConnection): void {
                    $activeTimeout = (int) data_get(
                        (array) $mysqlConnection->selectOne('SELECT @@SESSION.MAX_EXECUTION_TIME AS value'),
                        'value',
                        0,
                    );

                    expect($activeTimeout)->toBe(50);

                    throw new RuntimeException('Simulated MySQL statement failure.');
                }))->toThrow(RuntimeException::class, 'Simulated MySQL statement failure.');

                $restoredTimeout = (int) data_get(
                    (array) $mysqlConnection->selectOne('SELECT @@SESSION.MAX_EXECUTION_TIME AS value'),
                    'value',
                    0,
                );
                expect($restoredTimeout)->toBe(777);
            } finally {
                $mysqlConnection->statement("SET SESSION MAX_EXECUTION_TIME = {$originalTimeout}");
            }
        }

        registerHardeningSearchResources([HardeningMysqlUnicodeResource::class]);
        HardeningMysqlUnicodeResource::create([
            'title' => 'Ärger MySQL Needle',
            'type' => HardeningMysqlUnicodeResource::getType(),
        ]);
        HardeningMysqlUnicodeResource::create([
            'title' => 'ärger MySQL Needle',
            'type' => HardeningMysqlUnicodeResource::getType(),
        ]);
        HardeningMysqlUnicodeResource::create([
            'title' => 'Alpha MySQL Needle',
            'type' => HardeningMysqlUnicodeResource::getType(),
        ]);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Ärger')
            ->assertSee('Ärger MySQL Needle')
            ->assertDontSee('ärger MySQL Needle');

        Livewire::test(GlobalSearch::class)
            ->set('search', 'alpha')
            ->assertDontSee('Alpha MySQL Needle');
    } finally {
        DB::purge('global_search_mysql');
        $server->exec("DROP DATABASE IF EXISTS `{$database}`");
    }
});
