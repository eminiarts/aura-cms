<?php

use Aura\Base\Contracts\GlobalSearchAdapter;
use Aura\Base\Exceptions\GlobalSearchExecutionFailed;
use Aura\Base\Exceptions\GlobalSearchExecutionTimedOut;
use Aura\Base\Exceptions\GlobalSearchExecutionUnavailable;
use Aura\Base\Facades\Aura;
use Aura\Base\GlobalSearch\DatabaseGlobalSearchAdapter;
use Aura\Base\GlobalSearch\DatabaseStatementDeadline;
use Aura\Base\GlobalSearch\FreshProcessGlobalSearchExecutor;
use Aura\Base\GlobalSearch\FreshProcessGlobalSearchSupervisor;
use Aura\Base\GlobalSearch\GlobalSearchBudget;
use Aura\Base\GlobalSearch\GlobalSearchQueryGuard;
use Aura\Base\GlobalSearch\GlobalSearchWorkerContext;
use Aura\Base\Livewire\GlobalSearch;
use Aura\Base\Models\Meta;
use Aura\Base\Resource;
use Aura\Base\Resources\Team;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessBeforeQueryMutationResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessBlockingDiscoveryResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessCapturedManagerConnectionChurnResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessConnectionChurnResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessDefaultConnectionResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessDeniedConstructionResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessDescriptorProbeResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessEventForgetConnectionChurnResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessHostRestrictionResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessOutputAttackResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessPolicy;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessQueryFloodAdapterResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessQueryFloodPolicyResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessQueryFloodVisibilityResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessRawPdoAdapterResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessSlowDiscoveryResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessSlowTitleResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessSpawningResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessStallingResource;
use Aura\Base\Tests\Fixtures\GlobalSearchProcessUnionMutationResource;
use Illuminate\Database\Connection;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Events\ConnectionEstablished;
use Illuminate\Database\Query\Builder as BaseQueryBuilder;
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

class HardeningBeforeQueryMutationResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-before-query-mutation';

    public static string $type = 'HardeningBeforeQueryMutation';

    public function applyGlobalSearchVisibility($query, $user)
    {
        $table = $this->getTable();

        return $query->whereExists(function (BaseQueryBuilder $visibilityQuery) use ($table, $user): void {
            $alias = 'hardening_visible_posts';
            $visibilityQuery
                ->from($table.' as '.$alias)
                ->selectRaw('1')
                ->whereColumn($alias.'.id', $table.'.id')
                ->where($alias.'.team_id', data_get($user, 'current_team_id'));
            $visibilityQuery->beforeQuery(function (BaseQueryBuilder $query): void {
                $query->wheres = [];
                $query->bindings['where'] = [];
            });
        });
    }

    protected static function booted(): void
    {
        static::addGlobalScope('hardening-before-query-mutation', function (Builder $builder): void {
            $builder->getQuery()->beforeQuery(function (BaseQueryBuilder $query): void {
                $query->wheres = [];
                $query->bindings['where'] = [];
                $query->where('id', '>', 0)->orWhere('id', '>', 0);
                $query->limit = null;
            });
        });
    }
}

class HardeningBoundBeforeQueryResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-bound-before-query';

    public static string $type = 'HardeningBoundBeforeQuery';

    protected static function booted(): void
    {
        static::addGlobalScope('hardening-bound-before-query', function (Builder $builder): void {
            $builder->getQuery()->beforeQuery(function (BaseQueryBuilder $query): void {
                $query->where('title', 'not like', 'Bound Callback Hidden%');
            });
        });
    }
}

class HardeningRawBeforeQueryResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-raw-before-query';

    public static string $type = 'HardeningRawBeforeQuery';

    protected static function booted(): void
    {
        static::addGlobalScope('hardening-raw-before-query', function (Builder $builder): void {
            $builder->getQuery()->beforeQuery(function (BaseQueryBuilder $query): void {
                $query->whereRaw('1 = 1');
            });
        });
    }
}

class HardeningUnionBeforeQueryResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-union-before-query';

    public static string $type = 'HardeningUnionBeforeQuery';

    public function applyGlobalSearchVisibility($query, $user)
    {
        return $query->where('team_id', data_get($user, 'current_team_id'));
    }

    protected static function booted(): void
    {
        static::addGlobalScope('hardening-union-before-query', function (Builder $builder): void {
            $builder->getQuery()->beforeQuery(function (BaseQueryBuilder $query): void {
                $query->unionAll(
                    $query->connection
                        ->table('posts')
                        ->where('title', 'Cross Tenant Union Needle'),
                );
            });
        });
    }
}

class HardeningSecondPassUnionResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-second-pass-union';

    public static string $type = 'HardeningSecondPassUnion';

    public static int $visibilityPass = 0;

    public function applyGlobalSearchVisibility($query, $user)
    {
        static::$visibilityPass++;
        $query->where('team_id', data_get($user, 'current_team_id'));

        if (static::$visibilityPass === 2) {
            $query->unionAll(
                $query->getQuery()->connection
                    ->table('posts')
                    ->where('title', 'Cross Tenant Second Pass Needle'),
            );
        }

        return $query;
    }
}

class HardeningSecondPassPredicateErasingResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-second-pass-predicate-erasing';

    public static string $type = 'HardeningSecondPassPredicateErasing';

    public static int $visibilityPass = 0;

    public function applyGlobalSearchVisibility($query, $user)
    {
        static::$visibilityPass++;

        if (static::$visibilityPass === 1) {
            return $query->where('team_id', data_get($user, 'current_team_id'));
        }

        $query->getQuery()->wheres = [];
        $query->getQuery()->bindings['where'] = [];

        return $query;
    }
}

class HardeningEmptySearchableFieldsResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-empty-searchable-fields';

    public static string $type = 'HardeningEmptySearchableFields';

    protected static array $searchable = ['missing'];
}

class HardeningDeniedHooksResource extends HardeningSearchResource
{
    public static array $events = [];

    public static ?string $slug = 'hardening-denied-hooks';

    public static string $type = 'HardeningDeniedHooks';

    public static function getGlobalSearch()
    {
        DB::table((new static)->getTable())->count();
        static::$events[] = 'get-global-search';

        return true;
    }

    public function getGlobalSearchableFields()
    {
        static::$events[] = 'searchable-fields-hook';

        throw new RuntimeException('A denied searchable-fields hook must not run.');
    }

    public function globalSearchAllowsMissingTeamContext($user)
    {
        static::$events[] = 'missing-team-hook';

        throw new RuntimeException('A denied missing-team hook must not run.');
    }
}

class HardeningDeniedConstructionResource extends HardeningSearchResource
{
    public static array $events = [];

    public static ?string $slug = 'hardening-denied-construction';

    public static string $type = 'HardeningDeniedConstruction';

    public static function getFields()
    {
        DB::select('select 1');
        static::$events[] = 'get-fields-query';

        return parent::getFields();
    }
}

class HardeningDeniedConstructionPolicy
{
    public function view(User $user, Resource $resource): bool
    {
        return false;
    }

    public function viewAny(User $user, Resource $resource): bool
    {
        HardeningDeniedConstructionResource::$events[] = 'view-any';

        return false;
    }
}

class HardeningDeniedHooksPolicy
{
    public function view(User $user, Resource $resource): bool
    {
        return false;
    }

    public function viewAny(User $user, Resource $resource): bool
    {
        HardeningDeniedHooksResource::$events[] = 'view-any';

        return false;
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

class HardeningSlowTitleResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-slow-title';

    public static string $type = 'HardeningSlowTitle';

    public function title()
    {
        usleep(750_000);

        return parent::title();
    }
}

class HardeningSlowDiscoveryResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-slow-discovery';

    public static string $type = 'HardeningSlowDiscovery';

    public static function getGlobalSearch()
    {
        usleep(750_000);

        return true;
    }
}

class HardeningHostileIconResource extends HardeningSearchResource
{
    public static ?string $slug = 'hardening-hostile-icon';

    public static string $type = 'HardeningHostileIcon';

    public function getIcon()
    {
        return '<svg class="fixed inset-0" fill="url(https://attacker.example/paint)" onload="alert(1)" viewBox="0 0 10 10"><script>alert(2)</script><path d="M0 0h10v10z" stroke="u&#114;l(javascript:alert(3))" onclick="alert(3)"/></svg>'
            .str_repeat('A', 20_000);
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

/**
 * @param  array<int, class-string<resource>>  $resources
 * @return array{database: string, marker: string, user: User}
 */
function configureFreshProcessSearchHarness(string $mode, array $resources): array
{
    $suffix = getmypid().'-'.bin2hex(random_bytes(8));
    $databasePath = sys_get_temp_dir()."/aura-global-search-{$suffix}.sqlite";
    $markerPath = sys_get_temp_dir()."/aura-global-search-marker-{$suffix}";
    $pdo = new PDO("sqlite:{$databasePath}", null, null, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
    $pdo->exec(<<<'SQL'
CREATE TABLE users (
    id INTEGER PRIMARY KEY,
    name TEXT NOT NULL,
    email TEXT NOT NULL,
    password TEXT NOT NULL,
    current_team_id INTEGER,
    global_admin INTEGER NOT NULL DEFAULT 0,
    created_at TEXT,
    updated_at TEXT
);
CREATE TABLE meta (
    id INTEGER PRIMARY KEY,
    metable_type TEXT NOT NULL,
    metable_id INTEGER NOT NULL,
    key TEXT,
    value TEXT
);
CREATE TABLE global_search_process_records (
    id INTEGER PRIMARY KEY,
    title TEXT NOT NULL,
    team_id INTEGER,
    user_id INTEGER,
    created_at TEXT,
    updated_at TEXT
);
INSERT INTO users (id, name, email, password, current_team_id, global_admin)
VALUES (1, 'Process Search User', 'process-search@example.test', 'unused', 11, 1);
INSERT INTO global_search_process_records (id, title, team_id, user_id)
VALUES
    (1, 'Fresh Process Needle Current Team', 11, 1),
    (2, 'Fresh Process Needle Other Team', 22, 1);
SQL);
    $pdo = null;

    config([
        'aura.features.global_search' => true,
        'aura.features.legacy_fields_append' => false,
        'aura.global_search.execution_backend' => 'process',
        'aura.global_search.per_resource_timeout_ms' => 650,
        'aura.global_search.total_timeout_ms' => 4_000,
        'aura.global_search.max_queries_per_resource' => 8,
        'aura.global_search.max_total_queries' => 50,
        'aura.global_search.worker_connections' => ['process_search'],
        'aura.teams' => true,
        'database.connections.process_search' => [
            'driver' => 'sqlite',
            'database' => $databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ]);
    DB::purge('process_search');
    app()->instance(FreshProcessGlobalSearchExecutor::class, new FreshProcessGlobalSearchExecutor(
        realpath(dirname(__DIR__).'/Fixtures/GlobalSearchWorkerArtisan.php') ?: null,
        [
            'APP_ENV' => 'testing',
            'APP_KEY' => (string) config('app.key'),
            'AURA_GLOBAL_SEARCH_DESCENDANT_MARKER' => $markerPath,
            'AURA_GLOBAL_SEARCH_HOOK_MARKER' => $markerPath,
            'AURA_GLOBAL_SEARCH_PROCESS_FIXTURE' => '1',
            'AURA_GLOBAL_SEARCH_FIXTURE_MODE' => $mode,
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $databasePath,
        ],
        dirname(__DIR__, 2),
        autoloadPath: realpath(dirname(__DIR__, 2).'/vendor/autoload.php') ?: null,
        bootstrapPath: realpath(dirname(__DIR__).'/Fixtures/GlobalSearchWorkerBootstrap.php') ?: null,
    ));

    Aura::fake();
    Aura::registerResources($resources);

    foreach ($resources as $resource) {
        Aura::registerRoutes($resource::getSlug(), $resource);
        Gate::policy($resource, GlobalSearchProcessPolicy::class);
    }

    Aura::setModel(new $resources[0]);
    $user = User::on('process_search')->withoutGlobalScopes()->findOrFail(1);

    return [
        'database' => $databasePath,
        'marker' => $markerPath,
        'user' => $user,
    ];
}

/** @param array{database: string, marker: string, user: User} $harness */
function cleanupFreshProcessSearchHarness(array $harness): void
{
    DB::purge('process_search');
    @unlink($harness['database']);
    @unlink($harness['marker']);
}

/**
 * @return array{
 *     version: int,
 *     guard: string,
 *     user_id: int|string,
 *     team_id: int|string|null,
 *     connection: string,
 *     connection_fingerprint: string,
 *     signature: string
 * }
 */
function signedFreshProcessContext(User $user): array
{
    $context = (new GlobalSearchWorkerContext)->create($user, 'web');

    if (! is_array($context)) {
        throw new RuntimeException('The test worker context could not be signed.');
    }

    return $context;
}

/** @return array<int, array{pid: int, parent_pid: int, state: string}> */
function processEntriesContaining(string $needle): array
{
    $process = new Process(['/bin/ps', '-axo', 'pid=,ppid=,state=,command=']);
    $process->run();

    if (! $process->isSuccessful()) {
        throw new RuntimeException('Unable to inspect the process table.');
    }

    $entries = [];

    foreach (preg_split('/\R/', $process->getOutput()) ?: [] as $line) {
        if (! str_contains($line, $needle)) {
            continue;
        }

        if (preg_match('/^\s*(\d+)\s+(\d+)\s+(\S+)\s+/', $line, $matches) === 1) {
            $entries[] = [
                'pid' => (int) $matches[1],
                'parent_pid' => (int) $matches[2],
                'state' => $matches[3],
            ];
        }
    }

    return $entries;
}

/**
 * @return array{databases: array<int, string>, marker: string, user: User, previous_default: string}
 */
function configureCollidingFreshProcessSearchHarness(): array
{
    $suffix = getmypid().'-'.bin2hex(random_bytes(8));
    $tenantAPath = sys_get_temp_dir()."/aura-global-search-tenant-a-{$suffix}.sqlite";
    $tenantBPath = sys_get_temp_dir()."/aura-global-search-tenant-b-{$suffix}.sqlite";
    $markerPath = sys_get_temp_dir()."/aura-global-search-marker-{$suffix}";

    foreach ([
        [$tenantAPath, 'Tenant A User', 'tenant-a@example.test', 'Collision Needle Correct Tenant'],
        [$tenantBPath, 'Tenant B User', 'tenant-b@example.test', 'Collision Needle Wrong Tenant'],
    ] as [$databasePath, $name, $email, $title]) {
        $pdo = new PDO("sqlite:{$databasePath}", null, null, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);
        $pdo->exec('CREATE TABLE users (id INTEGER PRIMARY KEY, name TEXT NOT NULL, email TEXT NOT NULL, password TEXT NOT NULL, current_team_id INTEGER, global_admin INTEGER NOT NULL DEFAULT 0, created_at TEXT, updated_at TEXT)');
        $pdo->exec('CREATE TABLE meta (id INTEGER PRIMARY KEY, metable_type TEXT NOT NULL, metable_id INTEGER NOT NULL, key TEXT, value TEXT)');
        $pdo->exec('CREATE TABLE global_search_process_records (id INTEGER PRIMARY KEY, title TEXT NOT NULL, team_id INTEGER, user_id INTEGER, created_at TEXT, updated_at TEXT)');
        $insertUser = $pdo->prepare('INSERT INTO users (id, name, email, password, current_team_id, global_admin) VALUES (1, ?, ?, ?, 11, 1)');
        $insertUser->execute([$name, $email, 'unused']);
        $insertRecord = $pdo->prepare('INSERT INTO global_search_process_records (id, title, team_id, user_id) VALUES (1, ?, 11, 1)');
        $insertRecord->execute([$title]);
    }

    $previousDefault = DB::getDefaultConnection();
    $connections = [
        'process_search_tenant_a' => [
            'driver' => 'sqlite',
            'database' => $tenantAPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
        'process_search_tenant_b' => [
            'driver' => 'sqlite',
            'database' => $tenantBPath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ],
    ];
    config([
        'aura.features.global_search' => true,
        'aura.features.legacy_fields_append' => false,
        'aura.global_search.execution_backend' => 'process',
        'aura.global_search.per_resource_timeout_ms' => 650,
        'aura.global_search.total_timeout_ms' => 3_000,
        'aura.global_search.max_queries_per_resource' => 8,
        'aura.global_search.max_total_queries' => 50,
        'aura.global_search.worker_connections' => array_keys($connections),
        'aura.teams' => true,
        'database.default' => 'process_search_tenant_a',
        'database.connections.process_search_tenant_a' => $connections['process_search_tenant_a'],
        'database.connections.process_search_tenant_b' => $connections['process_search_tenant_b'],
    ]);
    DB::setDefaultConnection('process_search_tenant_a');
    DB::purge('process_search_tenant_a');
    DB::purge('process_search_tenant_b');
    app()->instance(FreshProcessGlobalSearchExecutor::class, new FreshProcessGlobalSearchExecutor(
        realpath(dirname(__DIR__).'/Fixtures/GlobalSearchWorkerArtisan.php') ?: null,
        [
            'APP_ENV' => 'testing',
            'APP_KEY' => (string) config('app.key'),
            'AURA_GLOBAL_SEARCH_HOOK_MARKER' => $markerPath,
            'AURA_GLOBAL_SEARCH_PROCESS_FIXTURE' => '1',
            'AURA_GLOBAL_SEARCH_FIXTURE_MODE' => 'tenant-collision',
            'DB_DATABASE_TENANT_A' => $tenantAPath,
            'DB_DATABASE_TENANT_B' => $tenantBPath,
        ],
        dirname(__DIR__, 2),
        autoloadPath: realpath(dirname(__DIR__, 2).'/vendor/autoload.php') ?: null,
        bootstrapPath: realpath(dirname(__DIR__).'/Fixtures/GlobalSearchWorkerBootstrap.php') ?: null,
    ));

    Aura::fake();
    Aura::registerResources([GlobalSearchProcessDefaultConnectionResource::class]);
    Aura::registerRoutes(
        GlobalSearchProcessDefaultConnectionResource::getSlug(),
        GlobalSearchProcessDefaultConnectionResource::class,
    );
    Gate::policy(GlobalSearchProcessDefaultConnectionResource::class, GlobalSearchProcessPolicy::class);
    Aura::setModel(new GlobalSearchProcessDefaultConnectionResource);

    return [
        'databases' => [$tenantAPath, $tenantBPath],
        'marker' => $markerPath,
        'user' => User::on('process_search_tenant_a')->withoutGlobalScopes()->findOrFail(1),
        'previous_default' => $previousDefault,
    ];
}

/** @param array{databases: array<int, string>, marker: string, user: User, previous_default: string} $harness */
function cleanupCollidingFreshProcessSearchHarness(array $harness): void
{
    DB::purge('process_search_tenant_a');
    DB::purge('process_search_tenant_b');
    DB::setDefaultConnection($harness['previous_default']);
    config(['database.default' => $harness['previous_default']]);

    foreach ($harness['databases'] as $databasePath) {
        @unlink($databasePath);
    }

    @unlink($harness['marker']);
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

test('resources without valid searchable fields do not consume the searchable resource cap', function () {
    config(['aura.global_search.max_resources' => 1]);
    registerHardeningSearchResources([
        HardeningEmptySearchableFieldsResource::class,
        HardeningAllowedResource::class,
    ]);
    Gate::policy(HardeningEmptySearchableFieldsResource::class, HardeningSearchPolicy::class);
    Gate::policy(HardeningAllowedResource::class, HardeningSearchPolicy::class);

    HardeningAllowedResource::create(['title' => 'Eligible Resource Cap Needle']);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Eligible Resource Cap Needle')
        ->assertSee('Eligible Resource Cap Needle');
});

test('viewAny authorization runs before every resource-controlled discovery hook', function () {
    registerHardeningSearchResources([HardeningDeniedHooksResource::class]);
    Gate::policy(HardeningDeniedHooksResource::class, HardeningDeniedHooksPolicy::class);

    $operator = User::factory()->create(config('aura.teams') ? ['current_team_id' => null] : []);
    $operator->forceFill(['global_admin' => true])->saveQuietly();
    $this->actingAs($operator->refresh());
    HardeningDeniedHooksResource::$events = [];

    $component = app(GlobalSearch::class);
    $component->search = 'Denied Hook Needle';

    expect($component->getSearchResultsProperty())->toBeEmpty()
        ->and(HardeningDeniedHooksResource::$events)->toBe(['view-any']);
});

test('viewAny authorization runs before resource construction and container resolution', function () {
    registerHardeningSearchResources([
        HardeningDeniedConstructionResource::class,
        HardeningAllowedResource::class,
    ]);
    Gate::policy(HardeningDeniedConstructionResource::class, HardeningDeniedConstructionPolicy::class);
    Gate::policy(HardeningAllowedResource::class, HardeningSearchPolicy::class);
    HardeningAllowedResource::create(['title' => 'Constructor Authorization Needle Allowed']);
    HardeningDeniedConstructionResource::$events = [];
    app()->bind(HardeningDeniedConstructionResource::class, function (): Resource {
        HardeningDeniedConstructionResource::$events[] = 'container-resolution';

        return new HardeningDeniedConstructionResource;
    });

    $component = app(GlobalSearch::class);
    $component->search = 'Constructor Authorization Needle';

    expect($component->getSearchResultsProperty()->flatten()->pluck('title')->all())
        ->toBe(['Constructor Authorization Needle Allowed'])
        ->and(HardeningDeniedConstructionResource::$events)->toBe(['view-any']);
});

test('the inline default adapter seals visibility scopes and its candidate limit after callbacks', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('This regression test exercises tenant visibility.');
    }

    config([
        'aura.global_search.execution_backend' => 'inline-testing',
        'aura.global_search.candidate_limit' => 2,
        'aura.global_search.per_resource_limit' => 5,
    ]);
    registerHardeningSearchResources([HardeningBeforeQueryMutationResource::class]);
    Gate::policy(HardeningBeforeQueryMutationResource::class, HardeningSearchPolicy::class);

    $otherTeam = Team::factory()->createQuietly();
    HardeningBeforeQueryMutationResource::withoutGlobalScopes()->create([
        'title' => 'Cross Tenant Callback Needle',
        'team_id' => $otherTeam->getKey(),
    ]);

    foreach (range(1, 6) as $index) {
        HardeningBeforeQueryMutationResource::withoutGlobalScopes()->create([
            'title' => "Visible Callback Needle {$index}",
            'team_id' => $this->searchUser->current_team_id,
        ]);
    }

    $component = app(GlobalSearch::class);
    $component->search = 'Callback Needle';
    $titles = $component->getSearchResultsProperty()->flatten()->pluck('title')->all();

    expect($titles)->toHaveCount(2)
        ->not->toContain('Cross Tenant Callback Needle')
        ->toEqualCanonicalizing([
            'Visible Callback Needle 1',
            'Visible Callback Needle 2',
        ]);
});

test('the inline default adapter rejects union callbacks instead of leaking another tenant', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('This regression test exercises tenant visibility.');
    }

    config(['aura.global_search.execution_backend' => 'inline-testing']);
    registerHardeningSearchResources([HardeningUnionBeforeQueryResource::class]);
    Gate::policy(HardeningUnionBeforeQueryResource::class, HardeningSearchPolicy::class);

    HardeningUnionBeforeQueryResource::withoutGlobalScopes()->create([
        'title' => 'Current Tenant Union Needle',
        'team_id' => $this->searchUser->current_team_id,
    ]);
    HardeningUnionBeforeQueryResource::withoutGlobalScopes()->create([
        'title' => 'Cross Tenant Union Needle',
        'team_id' => Team::factory()->createQuietly()->getKey(),
    ]);

    $component = app(GlobalSearch::class);
    $component->search = 'Union Needle';

    expect($component->getSearchResultsProperty())->toBeEmpty();
});

test('the inline default adapter validates the final visibility pass', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('This regression test exercises tenant visibility.');
    }

    config(['aura.global_search.execution_backend' => 'inline-testing']);
    registerHardeningSearchResources([HardeningSecondPassUnionResource::class]);
    Gate::policy(HardeningSecondPassUnionResource::class, HardeningSearchPolicy::class);

    HardeningSecondPassUnionResource::withoutGlobalScopes()->create([
        'title' => 'Current Tenant Second Pass Needle',
        'team_id' => $this->searchUser->current_team_id,
    ]);
    HardeningSecondPassUnionResource::withoutGlobalScopes()->create([
        'title' => 'Cross Tenant Second Pass Needle',
        'team_id' => Team::factory()->createQuietly()->getKey(),
    ]);
    HardeningSecondPassUnionResource::$visibilityPass = 0;

    $component = app(GlobalSearch::class);
    $component->search = 'Second Pass Needle';

    expect($component->getSearchResultsProperty())->toBeEmpty()
        ->and(HardeningSecondPassUnionResource::$visibilityPass)->toBe(2);
});

test('the inline default adapter preserves first pass visibility predicates and bindings', function () {
    if (! config('aura.teams')) {
        $this->markTestSkipped('This regression test exercises tenant visibility.');
    }

    config(['aura.global_search.execution_backend' => 'inline-testing']);
    registerHardeningSearchResources([HardeningSecondPassPredicateErasingResource::class]);
    Gate::policy(HardeningSecondPassPredicateErasingResource::class, HardeningSearchPolicy::class);

    HardeningSecondPassPredicateErasingResource::withoutGlobalScopes()->create([
        'title' => 'Current Tenant Predicate Needle',
        'team_id' => $this->searchUser->current_team_id,
    ]);
    HardeningSecondPassPredicateErasingResource::withoutGlobalScopes()->create([
        'title' => 'Cross Tenant Predicate Needle',
        'team_id' => Team::factory()->createQuietly()->getKey(),
    ]);
    HardeningSecondPassPredicateErasingResource::$visibilityPass = 0;

    $component = app(GlobalSearch::class);
    $component->search = 'Predicate Needle';

    expect($component->getSearchResultsProperty())->toBeEmpty()
        ->and(HardeningSecondPassPredicateErasingResource::$visibilityPass)->toBe(2);
});

test('the inline default adapter rejects raw callbacks', function () {
    config(['aura.global_search.execution_backend' => 'inline-testing']);
    registerHardeningSearchResources([HardeningRawBeforeQueryResource::class]);
    Gate::policy(HardeningRawBeforeQueryResource::class, HardeningSearchPolicy::class);

    HardeningRawBeforeQueryResource::create(['title' => 'Raw Callback Needle']);

    $component = app(GlobalSearch::class);
    $component->search = 'Raw Callback';

    expect($component->getSearchResultsProperty())->toBeEmpty();
});

test('the inline default adapter retains bound callbacks', function () {
    config(['aura.global_search.execution_backend' => 'inline-testing']);
    registerHardeningSearchResources([HardeningBoundBeforeQueryResource::class]);
    Gate::policy(HardeningBoundBeforeQueryResource::class, HardeningSearchPolicy::class);

    HardeningBoundBeforeQueryResource::create(['title' => 'Bound Callback Visible Needle']);
    HardeningBoundBeforeQueryResource::create(['title' => 'Bound Callback Hidden Needle']);

    $component = app(GlobalSearch::class);
    $component->search = 'Callback';
    $titles = $component->getSearchResultsProperty()->flatten()->pluck('title')->all();

    expect($titles)->toBe(['Bound Callback Visible Needle'])
        ->not->toContain('Bound Callback Hidden Needle');
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

test('hostile adapters cannot delay a healthy later resource', function (string $mode) {
    $harness = configureFreshProcessSearchHarness($mode, [
        GlobalSearchProcessStallingResource::class,
        GlobalSearchProcessResource::class,
    ]);
    try {
        $this->actingAs($harness['user']);
        $startedAt = hrtime(true);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Fresh Process Needle')
            ->assertSee('Fresh Process Needle Current Team');

        expect((string) file_get_contents($harness['marker']))->toStartWith($mode.'-entered-')
            ->and((hrtime(true) - $startedAt) / 1_000_000_000)->toBeLessThan(2.0);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
})->with(['sleeping', 'cpu', 'blocking']);

test('the hard resource deadline preempts slow title presentation', function () {
    $harness = configureFreshProcessSearchHarness('slow-title', [
        GlobalSearchProcessSlowTitleResource::class,
        GlobalSearchProcessResource::class,
    ]);
    try {
        $this->actingAs($harness['user']);
        $startedAt = hrtime(true);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Fresh Process Needle')
            ->assertSee('Fresh Process Needle Current Team');

        expect(file_get_contents($harness['marker']))->toBe('slow-title-entered')
            ->and((hrtime(true) - $startedAt) / 1_000_000_000)->toBeLessThan(2.0);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('the hard total deadline preempts slow resource discovery', function () {
    $harness = configureFreshProcessSearchHarness('slow-discovery', [
        GlobalSearchProcessSlowDiscoveryResource::class,
        GlobalSearchProcessResource::class,
    ]);
    config(['aura.global_search.total_timeout_ms' => 650]);

    try {
        $this->actingAs($harness['user']);
        $component = app(GlobalSearch::class);
        $component->search = 'Fresh Process Needle';
        $startedAt = hrtime(true);

        expect($component->getSearchResultsProperty())->toBeEmpty()
            ->and((string) file_get_contents($harness['marker']))->toStartWith('slow-discovery-entered-')
            ->and((hrtime(true) - $startedAt) / 1_000_000_000)->toBeLessThan(1.2);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh sqlite workers preserve current team isolation and parent connection state', function () {
    $harness = configureFreshProcessSearchHarness('normal', [GlobalSearchProcessResource::class]);

    try {
        $this->actingAs($harness['user']);
        $parentConnection = DB::connection('process_search');
        expect((int) $parentConnection->table('global_search_process_records')->count())->toBe(2);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Fresh Process Needle')
            ->assertSee('Fresh Process Needle Current Team')
            ->assertDontSee('Fresh Process Needle Other Team');

        expect((int) $parentConnection->table('global_search_process_records')->count())->toBe(2);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh workers seal visibility scopes and the candidate limit after callbacks', function () {
    $harness = configureFreshProcessSearchHarness('before-query-mutation', [
        GlobalSearchProcessBeforeQueryMutationResource::class,
    ]);

    try {
        $this->actingAs($harness['user']);
        $component = app(GlobalSearch::class);
        $component->search = 'Fresh Process Needle';
        $titles = $component->getSearchResultsProperty()->flatten()->pluck('title')->all();

        expect($titles)->toBe(['Fresh Process Needle Current Team'])
            ->not->toContain('Fresh Process Needle Other Team');
        expect((string) file_get_contents($harness['marker']))
            ->toContain('before-query-mutated')
            ->toContain(' where ')
            ->toContain(' limit 2');
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh workers authorize before denied resource construction', function () {
    $harness = configureFreshProcessSearchHarness('auth-before-construction', [
        GlobalSearchProcessDeniedConstructionResource::class,
        GlobalSearchProcessResource::class,
    ]);
    @unlink($harness['marker']);

    try {
        $this->actingAs($harness['user']);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Fresh Process Needle')
            ->assertSee('Fresh Process Needle Current Team');

        expect((string) file_get_contents($harness['marker']))->toBe('view-any');
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh workers reject union callbacks instead of leaking another tenant', function () {
    $harness = configureFreshProcessSearchHarness('before-query-union', [
        GlobalSearchProcessUnionMutationResource::class,
    ]);

    try {
        $this->actingAs($harness['user']);
        $result = app(FreshProcessGlobalSearchExecutor::class)->run([
            'operation' => 'search',
            'context' => signedFreshProcessContext($harness['user']),
            'query_limit' => 50,
            'resource' => GlobalSearchProcessUnionMutationResource::class,
            'resource_order' => 0,
            'search_term' => 'Fresh Process Needle',
            'global_limit' => 15,
            'execution_timeout_ms' => 1_500,
        ], 1_500, 1_048_576);

        expect($result['results'] ?? null)->toBe([])
            ->and((string) file_get_contents($harness['marker']))->toContain('union-mutated');
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh workers deny descendant spawning and leave no escaped process', function () {
    $harness = configureFreshProcessSearchHarness('spawning', [
        GlobalSearchProcessSpawningResource::class,
        GlobalSearchProcessResource::class,
    ]);

    try {
        $this->actingAs($harness['user']);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Fresh Process Needle')
            ->assertSee('Fresh Process Needle Current Team');

        usleep(350_000);

        expect(file_exists($harness['marker']))->toBeFalse()
            ->and((int) DB::connection('process_search')
                ->table('global_search_process_records')
                ->count())->toBe(2);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh workers close inherited parent file descriptors before booting PHP', function () {
    $harness = configureFreshProcessSearchHarness('descriptor-probe', [
        GlobalSearchProcessDescriptorProbeResource::class,
        GlobalSearchProcessResource::class,
    ]);
    $parentDescriptor = fopen(dirname(__DIR__, 2).'/composer.json', 'r');

    try {
        expect($parentDescriptor)->toBeResource();
        $this->actingAs($harness['user']);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Fresh Process Needle')
            ->assertSee('Fresh Process Needle Current Team');

        expect(file_exists($harness['marker']))->toBeFalse()
            ->and(ftell($parentDescriptor))->toBe(0);
    } finally {
        if (is_resource($parentDescriptor)) {
            fclose($parentDescriptor);
        }

        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh workers centrally enforce query budgets in hooks and adapters', function (
    string $mode,
    string $hostileResource,
) {
    $harness = configureFreshProcessSearchHarness($mode, [
        $hostileResource,
        GlobalSearchProcessResource::class,
    ]);
    config([
        'aura.global_search.max_queries_per_resource' => 4,
        'aura.global_search.per_resource_timeout_ms' => 1_500,
        'aura.global_search.total_timeout_ms' => 6_000,
    ]);

    try {
        $this->actingAs($harness['user']);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Fresh Process Needle')
            ->assertSee('Fresh Process Needle Current Team');

        expect((string) file_get_contents($harness['marker']))->toBe('qq');
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
})->with([
    'custom adapter' => ['query-adapter', GlobalSearchProcessQueryFloodAdapterResource::class],
    'connection churn' => ['query-churn', GlobalSearchProcessConnectionChurnResource::class],
    'visibility hook' => ['query-visibility', GlobalSearchProcessQueryFloodVisibilityResource::class],
    'policy' => ['query-policy', GlobalSearchProcessQueryFloodPolicyResource::class],
]);

test('a prohibited native PDO adapter remains contained by the hard resource deadline', function () {
    $harness = configureFreshProcessSearchHarness('raw-pdo', [
        GlobalSearchProcessRawPdoAdapterResource::class,
        GlobalSearchProcessResource::class,
    ]);
    config(['aura.global_search.per_resource_timeout_ms' => 800]);

    try {
        $this->actingAs($harness['user']);
        $startedAt = hrtime(true);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Fresh Process Needle')
            ->assertSee('Fresh Process Needle Current Team');

        expect((string) file_get_contents($harness['marker']))->toBe(str_repeat('p', 10))
            ->and((hrtime(true) - $startedAt) / 1_000_000_000)->toBeLessThan(2.3);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('malformed route allowlists fail closed and log metadata only', function (array $patterns) {
    Log::spy();
    config(['aura.global_search.allowed_route_names' => $patterns]);
    registerHardeningSearchResources([HardeningAllowedResource::class]);
    HardeningAllowedResource::create(['title' => 'Malformed Allowlist Needle']);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Malformed Allowlist Needle')
        ->assertDontSee('Malformed Allowlist Needle');

    Log::shouldHaveReceived('warning')->with(
        'Aura global search configuration failed closed.',
        ['reason' => 'invalid_allowed_route_names'],
    )->once();
})->with([
    'non-string entry' => [['aura.*', ['invalid']]],
    'associative list' => [['primary' => 'aura.*']],
]);

test('global search sanitizes and byte caps resource icons', function () {
    registerHardeningSearchResources([HardeningHostileIconResource::class]);
    HardeningHostileIconResource::create(['title' => 'Hostile Icon Needle']);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Hostile Icon Needle')
        ->assertSee('Hostile Icon Needle')
        ->assertSeeHtml('<path d="M0 0h10v10z"></path>')
        ->assertDontSeeHtml('<script')
        ->assertDontSeeHtml('onload=')
        ->assertDontSeeHtml('onclick=')
        ->assertDontSeeHtml('class="fixed inset-0"')
        ->assertDontSeeHtml('url(')
        ->assertDontSeeHtml('attacker.example')
        ->assertDontSeeHtml('javascript:')
        ->assertDontSee(str_repeat('A', 8_193), false);
});

test('global search fails closed when process isolation is unavailable', function (string $backend) {
    config(['aura.global_search.execution_backend' => $backend]);
    registerHardeningSearchResources([
        HardeningSleepingAdapterResource::class,
        HardeningAllowedResource::class,
    ]);
    HardeningAllowedResource::create(['title' => 'Fallback Isolation Needle Healthy']);
    $startedAt = hrtime(true);

    Livewire::test(GlobalSearch::class)
        ->set('search', 'Fallback Isolation Needle')
        ->assertDontSee('Fallback Isolation Needle Healthy');

    expect((hrtime(true) - $startedAt) / 1_000_000_000)->toBeLessThan(0.4);
})->with(['disabled backend' => 'none', 'invalid backend' => 'invalid']);

test('fresh process search remains isolated under an Octane runtime', function () {
    $harness = configureFreshProcessSearchHarness('normal', [GlobalSearchProcessResource::class]);
    app()->instance('octane', new stdClass);

    try {
        $this->actingAs($harness['user']);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Fresh Process Needle')
            ->assertSee('Fresh Process Needle Current Team')
            ->assertDontSee('Fresh Process Needle Other Team');
    } finally {
        app()->forgetInstance('octane');
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh worker authentication consumes the central query budget', function () {
    $harness = configureFreshProcessSearchHarness('normal', [GlobalSearchProcessResource::class]);
    $executor = app(FreshProcessGlobalSearchExecutor::class);

    try {
        $context = signedFreshProcessContext($harness['user']);

        $result = $executor->run([
            'operation' => 'discover',
            'context' => $context,
            'query_limit' => 50,
        ], 3_000, 1_048_576);

        expect($result['query_count'] ?? null)->toBe(2);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh worker reports authentication and resource queries together', function () {
    $harness = configureFreshProcessSearchHarness('normal', [GlobalSearchProcessResource::class]);
    $executor = app(FreshProcessGlobalSearchExecutor::class);

    try {
        $result = $executor->run([
            'operation' => 'search',
            'context' => signedFreshProcessContext($harness['user']),
            'query_limit' => 50,
            'resource' => GlobalSearchProcessResource::class,
            'resource_order' => 0,
            'search_term' => 'Fresh Process Needle',
            'global_limit' => 15,
            'execution_timeout_ms' => 650,
        ], 650, 1_048_576);

        expect($result['query_count'] ?? null)->toBe(3);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('the central query guard meters every purged connection incarnation', function () {
    $connectionName = 'global_search_guard_churn';
    config(["database.connections.{$connectionName}" => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]]);
    DB::purge($connectionName);
    $queryGuard = new GlobalSearchQueryGuard(3);
    $queryGuard->install();
    $guardedConnectionsProperty = new ReflectionProperty(
        GlobalSearchQueryGuard::class,
        'guardedConnections',
    );
    $initialGuardedConnectionCount = count($guardedConnectionsProperty->getValue($queryGuard));
    $completedQueries = 0;
    $failureClass = null;

    try {
        foreach (range(1, 100) as $iteration) {
            try {
                $connection = DB::connection($connectionName);
                $connection->select('select 1');
                $completedQueries++;
            } catch (GlobalSearchExecutionFailed $exception) {
                $failureClass = $exception::class;
                unset($exception);

                break;
            } finally {
                DB::purge($connectionName);
                unset($connection);
                gc_collect_cycles();
            }
        }

        gc_collect_cycles();
        $guardedConnections = $guardedConnectionsProperty->getValue($queryGuard);

        expect($failureClass)->toBe(GlobalSearchExecutionFailed::class)
            ->and($completedQueries)->toBe(3)
            ->and($queryGuard->queryCount())->toBe(3)
            ->and($guardedConnections)->toBeInstanceOf(WeakMap::class)
            ->toHaveCount($initialGuardedConnectionCount);
    } finally {
        DB::purge($connectionName);
    }
});

test('the central query guard survives removal of Laravel connection listeners', function () {
    $connectionName = 'global_search_guard_event_forget';
    config(["database.connections.{$connectionName}" => [
        'driver' => 'sqlite',
        'database' => ':memory:',
        'prefix' => '',
        'foreign_key_constraints' => true,
    ]]);
    DB::purge($connectionName);
    $queryGuard = new GlobalSearchQueryGuard(2);
    $queryGuard->install();
    Event::forget(ConnectionEstablished::class);
    DB::purge($connectionName);
    $completedQueries = 0;

    try {
        foreach (range(1, 5) as $iteration) {
            try {
                DB::connection($connectionName)->select('select 1');
                $completedQueries++;
            } catch (GlobalSearchExecutionFailed) {
                break;
            }
        }

        expect($completedQueries)->toBe(2)
            ->and($queryGuard->queryCount())->toBe(2);
    } finally {
        DB::purge($connectionName);
    }
});

test('fresh workers meter purge and reconnect after connection listeners are removed', function () {
    $harness = configureFreshProcessSearchHarness('query-churn-event-forget', [
        GlobalSearchProcessEventForgetConnectionChurnResource::class,
        GlobalSearchProcessResource::class,
    ]);

    try {
        $this->actingAs($harness['user']);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Fresh Process Needle')
            ->assertSee('Fresh Process Needle Current Team');

        expect(strlen((string) @file_get_contents($harness['marker'])))->toBeLessThanOrEqual(7);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh workers meter a manager captured during provider boot after listeners are removed', function () {
    $harness = configureFreshProcessSearchHarness('query-churn-captured-manager', [
        GlobalSearchProcessCapturedManagerConnectionChurnResource::class,
        GlobalSearchProcessResource::class,
    ]);

    try {
        $this->actingAs($harness['user']);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Fresh Process Needle')
            ->assertSee('Fresh Process Needle Current Team');

        expect(strlen((string) @file_get_contents($harness['marker'])))->toBeLessThanOrEqual(7);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh workers meter late connection extensions after listeners are removed', function (string $mode) {
    $harness = configureFreshProcessSearchHarness($mode, [
        GlobalSearchProcessCapturedManagerConnectionChurnResource::class,
        GlobalSearchProcessResource::class,
    ]);

    try {
        $this->actingAs($harness['user']);

        Livewire::test(GlobalSearch::class)
            ->set('search', 'Fresh Process Needle')
            ->assertSee('Fresh Process Needle Current Team');

        expect(strlen((string) @file_get_contents($harness['marker'])))->toBeLessThanOrEqual(7);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
})->with([
    'current guarded manager' => 'query-churn-late-extension-current-manager',
    'provider-captured original manager' => 'query-churn-late-extension-captured-manager',
]);

test('fresh workers reject forged stdout envelopes and abnormal termination', function (string $mode) {
    $harness = configureFreshProcessSearchHarness($mode, [GlobalSearchProcessOutputAttackResource::class]);
    $executor = app(FreshProcessGlobalSearchExecutor::class);

    try {
        expect(fn () => $executor->run([
            'operation' => 'search',
            'context' => signedFreshProcessContext($harness['user']),
            'query_limit' => 20,
            'resource' => GlobalSearchProcessOutputAttackResource::class,
            'resource_order' => 0,
            'search_term' => 'Fresh Process Needle',
            'global_limit' => 15,
            'execution_timeout_ms' => 1_500,
        ], 1_500, 1_048_576))->toThrow(GlobalSearchExecutionFailed::class);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
})->with([
    'forged success followed by exit' => 'forged-exit',
    'forged success followed by die' => 'forged-die',
    'forged success followed by the normal completion code' => 'forged-completed-code',
    'forged success followed by fatal error' => 'forged-fatal',
    'multiple forged envelopes' => 'forged-multiple',
    'partial forged envelope' => 'forged-partial',
]);

test('fresh workers reject a provider bootstrap shutdown callback forging completion', function () {
    $harness = configureFreshProcessSearchHarness(
        'provider-forged-completed-code',
        [GlobalSearchProcessOutputAttackResource::class],
    );
    $executor = app(FreshProcessGlobalSearchExecutor::class);

    try {
        expect(fn () => $executor->run([
            'operation' => 'search',
            'context' => signedFreshProcessContext($harness['user']),
            'query_limit' => 20,
            'resource' => GlobalSearchProcessOutputAttackResource::class,
            'resource_order' => 0,
            'search_term' => 'Fresh Process Needle',
            'global_limit' => 15,
            'execution_timeout_ms' => 1_500,
        ], 1_500, 1_048_576))->toThrow(GlobalSearchExecutionFailed::class);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh workers reject provider access to public completion authority', function () {
    expect(is_callable([
        FreshProcessGlobalSearchSupervisor::class,
        'markApplicationWorkerCompleted',
    ]))->toBeFalse();

    $harness = configureFreshProcessSearchHarness(
        'provider-public-completion-forge',
        [GlobalSearchProcessOutputAttackResource::class],
    );
    $executor = app(FreshProcessGlobalSearchExecutor::class);

    try {
        expect(fn () => $executor->run([
            'operation' => 'search',
            'context' => signedFreshProcessContext($harness['user']),
            'query_limit' => 20,
            'resource' => GlobalSearchProcessOutputAttackResource::class,
            'resource_order' => 0,
            'search_term' => 'Fresh Process Needle',
            'global_limit' => 15,
            'execution_timeout_ms' => 1_500,
        ], 1_500, 1_048_576))->toThrow(GlobalSearchExecutionFailed::class);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh workers keep completion capability outside application bootstrap scope', function () {
    $harness = configureFreshProcessSearchHarness(
        'bootstrap-capability-scope',
        [GlobalSearchProcessResource::class],
    );
    $executor = app(FreshProcessGlobalSearchExecutor::class);

    try {
        $result = $executor->run([
            'operation' => 'search',
            'context' => signedFreshProcessContext($harness['user']),
            'query_limit' => 20,
            'resource' => GlobalSearchProcessResource::class,
            'resource_order' => 0,
            'search_term' => 'Fresh Process Needle',
            'global_limit' => 15,
            'execution_timeout_ms' => 1_500,
        ], 1_500, 1_048_576);

        expect($result['results'][0]['title'] ?? null)->toBe('Fresh Process Needle Current Team')
            ->and((string) file_get_contents($harness['marker']))->toBe('hidden');
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh workers ignore stderr diagnostics while accepting one normal response', function () {
    $harness = configureFreshProcessSearchHarness('stderr-noise', [
        GlobalSearchProcessOutputAttackResource::class,
    ]);
    $executor = app(FreshProcessGlobalSearchExecutor::class);

    try {
        $result = $executor->run([
            'operation' => 'search',
            'context' => signedFreshProcessContext($harness['user']),
            'query_limit' => 20,
            'resource' => GlobalSearchProcessOutputAttackResource::class,
            'resource_order' => 0,
            'search_term' => 'Fresh Process Needle',
            'global_limit' => 15,
            'execution_timeout_ms' => 1_500,
        ], 1_500, 1_048_576);

        expect(collect($result['results'] ?? [])->pluck('title')->all())
            ->toBe(['Fresh Process Needle Current Team']);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh workers bind equal user and team identifiers to the signed tenant database', function () {
    $harness = configureCollidingFreshProcessSearchHarness();

    try {
        $this->actingAs($harness['user']);
        $component = app(GlobalSearch::class);
        $component->search = 'Collision Needle';
        $titles = $component->getSearchResultsProperty()
            ->flatten()
            ->pluck('title');

        expect($titles)->toContain('Collision Needle Correct Tenant')
            ->not->toContain('Collision Needle Wrong Tenant');
    } finally {
        cleanupCollidingFreshProcessSearchHarness($harness);
    }
});

test('fresh workers reject a tampered signed authentication context', function () {
    $harness = configureFreshProcessSearchHarness('normal', [GlobalSearchProcessResource::class]);
    $executor = app(FreshProcessGlobalSearchExecutor::class);

    try {
        $context = signedFreshProcessContext($harness['user']);
        $context['team_id'] = 22;

        expect(fn () => $executor->run([
            'operation' => 'discover',
            'context' => $context,
            'query_limit' => 50,
        ], 3_000, 1_048_576))->toThrow(GlobalSearchExecutionFailed::class);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('signed worker contexts reject tenant database bootstrap drift', function () {
    $harness = configureFreshProcessSearchHarness('normal', [GlobalSearchProcessResource::class]);
    $configuration = config('database.connections.process_search');

    try {
        $context = signedFreshProcessContext($harness['user']);
        config(['database.connections.process_search.database' => $harness['database'].'-different']);

        expect((new GlobalSearchWorkerContext)->verify($context))->toBeNull();
    } finally {
        config(['database.connections.process_search' => $configuration]);
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh execution fails closed before launch without an enumerable descriptor directory', function () {
    $harness = configureFreshProcessSearchHarness('slow-discovery', [
        GlobalSearchProcessSlowDiscoveryResource::class,
        GlobalSearchProcessResource::class,
    ]);
    $descriptorDirectory = sys_get_temp_dir().'/aura-empty-fd-directory-'.bin2hex(random_bytes(8));
    mkdir($descriptorDirectory, 0700);
    $executor = new FreshProcessGlobalSearchExecutor(
        artisanPath: realpath(dirname(__DIR__).'/Fixtures/GlobalSearchWorkerArtisan.php') ?: null,
        environment: [
            'APP_ENV' => 'testing',
            'AURA_GLOBAL_SEARCH_HOOK_MARKER' => $harness['marker'],
            'AURA_GLOBAL_SEARCH_PROCESS_FIXTURE' => '1',
            'AURA_GLOBAL_SEARCH_FIXTURE_MODE' => 'slow-discovery',
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $harness['database'],
        ],
        workingDirectory: dirname(__DIR__, 2),
        descriptorDirectories: [$descriptorDirectory],
        autoloadPath: realpath(dirname(__DIR__, 2).'/vendor/autoload.php') ?: null,
        bootstrapPath: realpath(dirname(__DIR__).'/Fixtures/GlobalSearchWorkerBootstrap.php') ?: null,
    );

    try {
        expect($executor->isAvailable())->toBeFalse()
            ->and(fn () => $executor->run([], 500, 1_048_576))
            ->toThrow(GlobalSearchExecutionUnavailable::class)
            ->and(file_exists($harness['marker']))->toBeFalse();
    } finally {
        @rmdir($descriptorDirectory);
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh execution fails before launch without required parent supervision primitives', function () {
    $harness = configureFreshProcessSearchHarness('slow-discovery', [
        GlobalSearchProcessSlowDiscoveryResource::class,
        GlobalSearchProcessResource::class,
    ]);
    $projectPath = dirname(__DIR__, 2);
    $artisanPath = dirname(__DIR__).'/Fixtures/GlobalSearchWorkerArtisan.php';
    $outer = new Process([
        PHP_BINARY,
        '-d',
        'disable_functions=pcntl_async_signals,pcntl_signal,pcntl_signal_get_handler,posix_kill',
        $artisanPath,
        'aura:test-supervise-global-search',
        '--no-interaction',
    ], $projectPath, [
        'APP_ENV' => 'testing',
        'APP_KEY' => (string) config('app.key'),
        'AURA_GLOBAL_SEARCH_HOOK_MARKER' => $harness['marker'],
        'AURA_GLOBAL_SEARCH_PROCESS_FIXTURE' => '1',
        'AURA_GLOBAL_SEARCH_FIXTURE_MODE' => 'slow-discovery',
        'AURA_GLOBAL_SEARCH_WORKER_ARTISAN' => $artisanPath,
        'AURA_GLOBAL_SEARCH_WORKING_DIRECTORY' => $projectPath,
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => $harness['database'],
    ], timeout: 3);

    try {
        $outer->run();

        expect($outer->isSuccessful())->toBeFalse($outer->getOutput().$outer->getErrorOutput())
            ->and(file_exists($harness['marker']))->toBeFalse();
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('fresh execution fails before launch without signal masking', function () {
    $harness = configureFreshProcessSearchHarness('slow-discovery', [
        GlobalSearchProcessSlowDiscoveryResource::class,
        GlobalSearchProcessResource::class,
    ]);
    $projectPath = dirname(__DIR__, 2);
    $artisanPath = dirname(__DIR__).'/Fixtures/GlobalSearchWorkerArtisan.php';
    $outer = new Process([
        PHP_BINARY,
        '-d',
        'disable_functions=pcntl_sigprocmask',
        $artisanPath,
        'aura:test-supervise-global-search',
        '--no-interaction',
    ], $projectPath, [
        'APP_ENV' => 'testing',
        'APP_KEY' => (string) config('app.key'),
        'AURA_GLOBAL_SEARCH_HOOK_MARKER' => $harness['marker'],
        'AURA_GLOBAL_SEARCH_PROCESS_FIXTURE' => '1',
        'AURA_GLOBAL_SEARCH_FIXTURE_MODE' => 'slow-discovery',
        'AURA_GLOBAL_SEARCH_WORKER_ARTISAN' => $artisanPath,
        'AURA_GLOBAL_SEARCH_WORKING_DIRECTORY' => $projectPath,
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => $harness['database'],
    ], timeout: 3);

    try {
        $outer->run();

        expect($outer->isSuccessful())->toBeFalse($outer->getOutput().$outer->getErrorOutput())
            ->and(file_exists($harness['marker']))->toBeFalse();
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('isolated execution uses a fresh contained process and preserves parent signal state', function () {
    $harness = configureFreshProcessSearchHarness('normal', [GlobalSearchProcessResource::class]);
    $executor = app(FreshProcessGlobalSearchExecutor::class);

    $signalHandler = function_exists('pcntl_signal_get_handler')
        ? pcntl_signal_get_handler(SIGALRM)
        : null;
    $asynchronousSignals = function_exists('pcntl_async_signals')
        ? pcntl_async_signals()
        : null;

    try {
        $result = $executor->run([
            'operation' => 'discover',
            'context' => signedFreshProcessContext($harness['user']),
            'query_limit' => 50,
        ], 3_000, 1_048_576);

        expect($result['worker_pid'] ?? null)->toBeInt()
            ->not->toBe(getmypid())
            ->and($result['contained'] ?? null)->toBeTrue();
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }

    if (function_exists('pcntl_signal_get_handler')) {
        expect(pcntl_signal_get_handler(SIGALRM))->toBe($signalHandler);
    }

    if (function_exists('pcntl_async_signals')) {
        expect(pcntl_async_signals())->toBe($asynchronousSignals);
    }
});

test('fresh workers preserve inherited host function restrictions', function () {
    $harness = configureFreshProcessSearchHarness('host-restriction', [
        GlobalSearchProcessHostRestrictionResource::class,
    ]);
    $projectPath = dirname(__DIR__, 2);
    $artisanPath = dirname(__DIR__).'/Fixtures/GlobalSearchWorkerArtisan.php';
    $outer = new Process([
        PHP_BINARY,
        '-d',
        'disable_functions=putenv',
        $artisanPath,
        'aura:test-supervise-global-search',
        '--no-interaction',
    ], $projectPath, [
        'APP_ENV' => 'testing',
        'APP_KEY' => (string) config('app.key'),
        'AURA_GLOBAL_SEARCH_HOOK_MARKER' => $harness['marker'],
        'AURA_GLOBAL_SEARCH_PROCESS_FIXTURE' => '1',
        'AURA_GLOBAL_SEARCH_FIXTURE_MODE' => 'host-restriction',
        'AURA_GLOBAL_SEARCH_WORKER_ARTISAN' => $artisanPath,
        'AURA_GLOBAL_SEARCH_WORKING_DIRECTORY' => $projectPath,
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => $harness['database'],
    ], timeout: 5);

    try {
        $outer->run();

        expect($outer->isSuccessful())->toBeTrue($outer->getOutput().$outer->getErrorOutput())
            ->and((string) file_get_contents($harness['marker']))->toBe('disabled');
    } finally {
        if ($outer->isRunning()) {
            $outer->stop(0);
        }

        cleanupFreshProcessSearchHarness($harness);
    }
});

test('the worker bootstrap fails actionably before artisan when restrictions are missing', function () {
    $projectPath = dirname(__DIR__, 2);
    $supervisorPath = realpath($projectPath.'/src/GlobalSearch/FreshProcessGlobalSearchSupervisor.php');
    $artisanPath = realpath(dirname(__DIR__).'/Fixtures/GlobalSearchWorkerArtisan.php');

    expect($supervisorPath)->toBeString()
        ->and($artisanPath)->toBeString();

    $process = new Process([
        PHP_BINARY,
        '-r',
        'require $argv[1]; exit(\Aura\Base\GlobalSearch\FreshProcessGlobalSearchSupervisor::runWorker(array_slice($argv, 2)));',
        '--',
        $supervisorPath,
        $artisanPath,
        'strlen',
    ], $projectPath, timeout: 2);
    $process->run();

    expect($process->getExitCode())->toBe(FreshProcessGlobalSearchSupervisor::CONFIGURATION_EXIT_CODE)
        ->and($process->getErrorOutput())->toContain(
            'Unable to apply the inherited global search worker PHP restrictions.',
        );
});

test('isolated execution enforces its deadline directly', function () {
    $harness = configureFreshProcessSearchHarness('slow-discovery', [
        GlobalSearchProcessSlowDiscoveryResource::class,
        GlobalSearchProcessResource::class,
    ]);
    $executor = app(FreshProcessGlobalSearchExecutor::class);

    try {
        $startedAt = hrtime(true);
        $exception = null;

        try {
            $executor->run([
                'operation' => 'discover',
                'context' => signedFreshProcessContext($harness['user']),
                'query_limit' => 50,
            ], 650, 1_048_576);
        } catch (GlobalSearchExecutionTimedOut $caughtException) {
            $exception = $caughtException;
        }

        expect($exception)->toBeInstanceOf(GlobalSearchExecutionTimedOut::class)
            ->and($exception?->getPrevious())->toBeNull()
            ->and((string) file_get_contents($harness['marker']))->toStartWith('slow-discovery-entered-')
            ->and((hrtime(true) - $startedAt) / 1_000_000_000)->toBeLessThan(1.2);
    } finally {
        cleanupFreshProcessSearchHarness($harness);
    }
});

test('parent SIGTERM cleanup kills and reaps the active fresh worker', function () {
    if (! function_exists('posix_kill') || ! defined('SIGTERM')) {
        $this->markTestSkipped('POSIX signals are unavailable on this platform.');
    }

    $harness = configureFreshProcessSearchHarness('slow-discovery', [
        GlobalSearchProcessSlowDiscoveryResource::class,
        GlobalSearchProcessResource::class,
    ]);
    $projectPath = dirname(__DIR__, 2);
    $artisanPath = dirname(__DIR__).'/Fixtures/GlobalSearchWorkerArtisan.php';
    $workerEnvironment = [
        'APP_ENV' => 'testing',
        'AURA_GLOBAL_SEARCH_HOOK_MARKER' => $harness['marker'],
        'AURA_GLOBAL_SEARCH_PROCESS_FIXTURE' => '1',
        'AURA_GLOBAL_SEARCH_FIXTURE_MODE' => 'slow-discovery',
        'AURA_GLOBAL_SEARCH_WORKER_ARTISAN' => $artisanPath,
        'AURA_GLOBAL_SEARCH_WORKING_DIRECTORY' => $projectPath,
        'DB_CONNECTION' => 'sqlite',
        'DB_DATABASE' => $harness['database'],
    ];
    $outer = new Process(
        [PHP_BINARY, $artisanPath, 'aura:test-supervise-global-search', '--no-interaction'],
        $projectPath,
        $workerEnvironment,
    );

    try {
        $outer->start();
        $markerDeadline = hrtime(true) + 3_000_000_000;

        while (! is_file($harness['marker']) && hrtime(true) < $markerDeadline) {
            usleep(10_000);
        }

        expect(is_file($harness['marker']))->toBeTrue($outer->getErrorOutput());
        $workerProcessId = (int) str()->after(
            (string) file_get_contents($harness['marker']),
            'slow-discovery-entered-',
        );
        $outerProcessId = $outer->getPid();

        expect($workerProcessId)->toBeGreaterThan(1)
            ->and($outerProcessId)->toBeInt();

        posix_kill($outerProcessId, SIGTERM);

        try {
            $outer->wait();
        } catch (Throwable) {
            // A signal exit is the expected outcome for the supervised parent.
        }

        $reapDeadline = hrtime(true) + 1_000_000_000;

        while (@posix_kill($workerProcessId, 0) && hrtime(true) < $reapDeadline) {
            usleep(10_000);
        }

        expect(@posix_kill($workerProcessId, 0))->toBeFalse();
    } finally {
        if ($outer->isRunning()) {
            $outer->stop(0);
        }

        cleanupFreshProcessSearchHarness($harness);
    }
});

test('SIGTERM during worker startup cannot orphan the application process', function () {
    if (! function_exists('posix_kill')
        || ! defined('SIGKILL')
        || ! defined('SIGCONT')
        || ! defined('SIGSTOP')
        || ! defined('SIGTERM')) {
        $this->markTestSkipped('POSIX signals are unavailable on this platform.');
    }

    $projectPath = dirname(__DIR__, 2);
    $supervisorPath = realpath($projectPath.'/src/GlobalSearch/FreshProcessGlobalSearchSupervisor.php');
    $workerPath = realpath(dirname(__DIR__).'/Fixtures/GlobalSearchEarlySignalWorker.php');
    $processToken = 'aura-global-search-early-signal-'.getmypid().'-'.bin2hex(random_bytes(8));

    expect($supervisorPath)->toBeString()
        ->and($workerPath)->toBeString();

    $outer = new Process([
        PHP_BINARY,
        '-r',
        'require $argv[1]; exit(\Aura\Base\GlobalSearch\FreshProcessGlobalSearchSupervisor::run(array_slice($argv, 2)));',
        '--',
        $supervisorPath,
        (string) getmypid(),
        (string) (hrtime(true) + 2_000_000_000),
        $workerPath,
        $processToken,
        'pcntl_exec,pcntl_fork,proc_open',
    ], $projectPath, timeout: 3);

    $observedProcessIds = [];

    try {
        $outer->start();
        $startupDeadline = hrtime(true) + 1_000_000_000;
        $workerProcessId = null;

        while (! is_int($workerProcessId) && hrtime(true) < $startupDeadline) {
            $entries = processEntriesContaining($processToken);

            foreach ($entries as $entry) {
                $observedProcessIds[$entry['pid']] = $entry['pid'];
            }

            foreach ($entries as $entry) {
                $parent = collect($entries)->firstWhere('pid', $entry['parent_pid']);

                if (str_starts_with($entry['state'], 'T')
                    && is_array($parent)
                    && str_starts_with($parent['state'], 'T')) {
                    $workerProcessId = $entry['pid'];
                    break;
                }
            }

            usleep(1_000);
        }

        expect($workerProcessId)->toBeInt($outer->getErrorOutput());
        posix_kill($workerProcessId, SIGCONT);
        $outer->wait();

        foreach (processEntriesContaining($processToken) as $entry) {
            $observedProcessIds[$entry['pid']] = $entry['pid'];
        }

        $cleanupDeadline = hrtime(true) + 500_000_000;
        $remainingProcessIds = array_values(array_filter(
            $observedProcessIds,
            fn (int $processId): bool => @posix_kill($processId, 0),
        ));

        while ($remainingProcessIds !== [] && hrtime(true) < $cleanupDeadline) {
            usleep(10_000);
            $remainingProcessIds = array_values(array_filter(
                $observedProcessIds,
                fn (int $processId): bool => @posix_kill($processId, 0),
            ));
        }

        expect($remainingProcessIds)->toBe([]);
    } finally {
        if ($outer->isRunning()) {
            $outer->stop(0);
        }

        foreach (processEntriesContaining($processToken) as $entry) {
            @posix_kill($entry['pid'], SIGCONT);
            @posix_kill($entry['pid'], SIGKILL);
        }
    }
})->repeat(20);

test('SIGKILLing the watcher cannot orphan its published application worker', function () {
    if (! function_exists('posix_kill') || ! defined('SIGKILL')) {
        $this->markTestSkipped('POSIX signals are unavailable on this platform.');
    }

    $projectPath = dirname(__DIR__, 2);
    $supervisorPath = realpath($projectPath.'/src/GlobalSearch/FreshProcessGlobalSearchSupervisor.php');
    $workerPath = realpath(dirname(__DIR__).'/Fixtures/GlobalSearchBlockingWorker.php');
    $markerPath = sys_get_temp_dir().'/aura-global-search-worker-pid-'.bin2hex(random_bytes(8));
    $processToken = 'aura_global_search_watcher_kill_'.bin2hex(random_bytes(8));

    expect($supervisorPath)->toBeString()
        ->and($workerPath)->toBeString();

    $outer = new Process([
        PHP_BINARY,
        '-r',
        'require $argv[1]; exit(\Aura\Base\GlobalSearch\FreshProcessGlobalSearchSupervisor::run(array_slice($argv, 2)));',
        '--',
        $supervisorPath,
        (string) getmypid(),
        (string) (hrtime(true) + 750_000_000),
        PHP_BINARY,
        $workerPath,
        'pcntl_exec,pcntl_fork,posix_kill,proc_open,'.$processToken,
    ], $projectPath, [
        'AURA_GLOBAL_SEARCH_WORKER_PID_MARKER' => $markerPath,
    ], timeout: 3);
    $workerProcessId = null;

    try {
        $outer->start();
        $markerDeadline = hrtime(true) + 1_000_000_000;

        while (! is_file($markerPath) && hrtime(true) < $markerDeadline) {
            usleep(5_000);
        }

        expect(is_file($markerPath))->toBeTrue($outer->getErrorOutput());
        $payload = json_decode((string) file_get_contents($markerPath), true, flags: JSON_THROW_ON_ERROR);
        $workerProcessId = $payload['worker_pid'] ?? null;
        $workerParentProcessId = $payload['parent_pid'] ?? null;
        $outerProcessId = $outer->getPid();
        $watcherProcessId = collect(processEntriesContaining($processToken))
            ->first(fn (array $entry): bool => $entry['parent_pid'] === $outerProcessId
                && $entry['pid'] !== $workerProcessId)['pid'] ?? null;

        expect($workerProcessId)->toBeInt()->toBeGreaterThan(1)
            ->and($outerProcessId)->toBeInt()
            ->and($workerParentProcessId)->toBe($outerProcessId)
            ->and($watcherProcessId)->toBeInt()->toBeGreaterThan(1);

        posix_kill($watcherProcessId, SIGKILL);

        try {
            $outer->wait();
        } catch (Throwable) {
            // The supervisor reports the deliberately killed watcher.
        }

        $cleanupDeadline = hrtime(true) + 1_000_000_000;

        while (@posix_kill($workerProcessId, 0) && hrtime(true) < $cleanupDeadline) {
            usleep(10_000);
        }

        expect(@posix_kill($workerProcessId, 0))->toBeFalse();
    } finally {
        if (is_int($workerProcessId) && $workerProcessId > 1 && @posix_kill($workerProcessId, 0)) {
            @posix_kill($workerProcessId, SIGKILL);
        }

        foreach (processEntriesContaining($processToken) as $entry) {
            @posix_kill($entry['pid'], SIGKILL);
        }

        if ($outer->isRunning()) {
            $outer->stop(0);
        }

        @unlink($markerPath);
    }
})->repeat(10);

test('supervision fails closed when its PID publication channel is unavailable', function () {
    $projectPath = dirname(__DIR__, 2);
    $supervisorPath = realpath($projectPath.'/src/GlobalSearch/FreshProcessGlobalSearchSupervisor.php');
    $workerPath = realpath(dirname(__DIR__).'/Fixtures/GlobalSearchBlockingWorker.php');
    $markerPath = sys_get_temp_dir().'/aura-global-search-publication-failure-'.bin2hex(random_bytes(8));

    expect($supervisorPath)->toBeString()
        ->and($workerPath)->toBeString();

    $outer = new Process([
        PHP_BINARY,
        '-d',
        'disable_functions=stream_socket_pair',
        '-r',
        'require $argv[1]; exit(\Aura\Base\GlobalSearch\FreshProcessGlobalSearchSupervisor::run(array_slice($argv, 2)));',
        '--',
        $supervisorPath,
        (string) getmypid(),
        (string) (hrtime(true) + 300_000_000),
        PHP_BINARY,
        $workerPath,
        'pcntl_exec,pcntl_fork,posix_kill,proc_open',
    ], $projectPath, [
        'AURA_GLOBAL_SEARCH_WORKER_PID_MARKER' => $markerPath,
    ], timeout: 2);

    try {
        $outer->run();

        expect($outer->getExitCode())->toBe(126)
            ->and(file_exists($markerPath))->toBeFalse();
    } finally {
        if ($outer->isRunning()) {
            $outer->stop(0);
        }

        @unlink($markerPath);
    }
});

test('a blocking fresh worker cannot outlive a SIGKILLed request parent', function () {
    if (! function_exists('posix_kill') || ! defined('SIGKILL')) {
        $this->markTestSkipped('POSIX signals are unavailable on this platform.');
    }

    $harness = configureFreshProcessSearchHarness('blocking-discovery', [
        GlobalSearchProcessBlockingDiscoveryResource::class,
        GlobalSearchProcessResource::class,
    ]);
    $projectPath = dirname(__DIR__, 2);
    $artisanPath = dirname(__DIR__).'/Fixtures/GlobalSearchWorkerArtisan.php';
    $outer = new Process(
        [PHP_BINARY, $artisanPath, 'aura:test-supervise-global-search', '--no-interaction'],
        $projectPath,
        [
            'APP_ENV' => 'testing',
            'APP_KEY' => (string) config('app.key'),
            'AURA_GLOBAL_SEARCH_HOOK_MARKER' => $harness['marker'],
            'AURA_GLOBAL_SEARCH_PROCESS_FIXTURE' => '1',
            'AURA_GLOBAL_SEARCH_FIXTURE_MODE' => 'blocking-discovery',
            'AURA_GLOBAL_SEARCH_WORKER_ARTISAN' => $artisanPath,
            'AURA_GLOBAL_SEARCH_WORKING_DIRECTORY' => $projectPath,
            'DB_CONNECTION' => 'sqlite',
            'DB_DATABASE' => $harness['database'],
        ],
    );
    $workerProcessId = null;

    try {
        $outer->start();
        $markerDeadline = hrtime(true) + 3_000_000_000;

        while (! is_file($harness['marker']) && hrtime(true) < $markerDeadline) {
            usleep(10_000);
        }

        expect(is_file($harness['marker']))->toBeTrue($outer->getErrorOutput());
        $workerProcessId = (int) str()->after(
            (string) file_get_contents($harness['marker']),
            'blocking-discovery-entered-',
        );
        $outerProcessId = $outer->getPid();

        expect($workerProcessId)->toBeGreaterThan(1)
            ->and($outerProcessId)->toBeInt();

        posix_kill($outerProcessId, SIGKILL);
        $reapDeadline = hrtime(true) + 1_000_000_000;

        while (@posix_kill($workerProcessId, 0) && hrtime(true) < $reapDeadline) {
            usleep(10_000);
        }

        expect(@posix_kill($workerProcessId, 0))->toBeFalse();
    } finally {
        if (is_int($workerProcessId) && $workerProcessId > 1 && @posix_kill($workerProcessId, 0)) {
            @posix_kill($workerProcessId, SIGKILL);
        }

        if ($outer->isRunning()) {
            $outer->stop(0);
        }

        cleanupFreshProcessSearchHarness($harness);
    }
});

test('database statement deadlines reject sqlite busy timeout as a statement deadline', function () {
    $callbackWasCalled = false;

    expect(fn () => (new DatabaseStatementDeadline)->run(
        DB::connection(),
        150,
        function () use (&$callbackWasCalled): void {
            $callbackWasCalled = true;
        },
    ))->toThrow(GlobalSearchExecutionUnavailable::class)
        ->and($callbackWasCalled)->toBeFalse();
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
    $connection->shouldReceive('getDriverName')->twice()->andReturn('pgsql');
    $connection->shouldReceive('selectOne')
        ->once()
        ->with("SELECT current_setting('statement_timeout') AS value")
        ->andReturn((object) ['value' => '1000ms']);
    $connection->shouldReceive('selectOne')
        ->once()
        ->with("SELECT set_config('statement_timeout', ?, false)", ['150ms'])
        ->andReturn((object) ['value' => '150ms']);
    $connection->shouldReceive('selectOne')
        ->once()
        ->with("SELECT set_config('statement_timeout', ?, false)", ['1000ms'])
        ->andReturnNull();
    $connection->shouldReceive('disconnect')->once();

    expect((new DatabaseStatementDeadline)->run(
        $connection,
        150,
        fn (): string => 'completed',
    ))->toBe('completed');

    Log::shouldHaveReceived('warning')->with(
        'Aura global search disconnected a database connection after deadline restoration failed.',
        [
            'driver' => 'pgsql',
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
