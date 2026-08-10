<?php

use Aura\Base\BaseResource;
use Aura\Base\Livewire\Table\TableMutationDispatcher;
use Aura\Base\Livewire\Table\TableMutationModelDescriptor;
use Aura\Base\Resources\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpException;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Core05PostgresMutationResource extends BaseResource
{
    public array $actions = [
        'markReviewed' => [
            'label' => 'Mark reviewed',
            'ability' => 'update',
        ],
    ];

    public array $bulkActions = [
        'captureCurrentOrder' => [
            'label' => 'Capture current order',
            'ability' => 'update',
            'method' => 'collection',
        ],
        'downloadCurrentOrder' => [
            'ability' => 'update',
            'download' => [
                'content_type' => 'text/plain',
                'filename' => 'postgres-order.txt',
            ],
            'label' => 'Download current order',
            'method' => 'collection',
        ],
    ];

    public static array $capturedIds = [];

    public static ?string $slug = 'core05-postgres-mutation-resource';

    protected $guarded = [];

    public function captureCurrentOrder(array $ids): void
    {
        array_push(static::$capturedIds, ...$ids);
    }

    public function downloadCurrentOrder(array $ids): string
    {
        return implode(',', $ids);
    }

    public static function getFields(): array
    {
        return [];
    }

    public function markReviewed(): void
    {
        $this->content = 'reviewed';
        $this->save();
    }
}

class Core05PostgresMutationPolicy
{
    public function update(User $user, Core05PostgresMutationResource $resource): bool
    {
        return $user->exists && $resource->exists;
    }
}

function core05PostgresTable(): string
{
    static $table;

    return $table ??= 'core05_pg_mutation_'.getmypid();
}

function core05PostgresAvailable(): bool
{
    try {
        DB::connection('core05_pgsql')->getPdo();

        return true;
    } catch (Throwable) {
        return false;
    }
}

function core05PostgresMountedResource(string $connection = 'core05_pgsql'): Core05PostgresMutationResource
{
    return (new Core05PostgresMutationResource)
        ->setConnection($connection)
        ->setTable(core05PostgresTable());
}

function core05PostgresReadSignal(mixed $stream): void
{
    stream_set_timeout($stream, 5);
    $signal = fread($stream, 1);
    $metadata = stream_get_meta_data($stream);

    if ($signal !== '1' || ($metadata['timed_out'] ?? false)) {
        throw new RuntimeException('Timed out waiting for the PostgreSQL ordering probe signal.');
    }
}

function core05PostgresWaitForChild(int $child, int &$status): bool
{
    $deadline = microtime(true) + 5;

    do {
        $result = pcntl_waitpid($child, $status, WNOHANG);

        if ($result === $child) {
            return true;
        }

        if ($result === -1) {
            return false;
        }

        usleep(50_000);
    } while (microtime(true) < $deadline);

    pcntl_kill($child, SIGTERM);
    $terminateDeadline = microtime(true) + 1;

    do {
        if (pcntl_waitpid($child, $status, WNOHANG) === $child) {
            return false;
        }

        usleep(50_000);
    } while (microtime(true) < $terminateDeadline);

    pcntl_kill($child, SIGKILL);
    $killDeadline = microtime(true) + 1;

    do {
        if (pcntl_waitpid($child, $status, WNOHANG) === $child) {
            return false;
        }

        usleep(50_000);
    } while (microtime(true) < $killDeadline);

    return false;
}

beforeEach(function () {
    config()->set('database.connections.core05_pgsql', [
        'driver' => 'pgsql',
        'host' => env('CORE05_PG_HOST', '/tmp'),
        'port' => env('CORE05_PG_PORT', 5432),
        'database' => env('CORE05_PG_DATABASE', 'postgres'),
        'username' => env('CORE05_PG_USERNAME', get_current_user()),
        'password' => env('CORE05_PG_PASSWORD'),
        'charset' => 'utf8',
        'prefix' => '',
        'search_path' => 'public',
        'sslmode' => 'prefer',
    ]);
    config()->set('database.connections.core05_pgsql_child', config('database.connections.core05_pgsql'));
    config()->set('database.connections.core05_pgsql_writer', config('database.connections.core05_pgsql'));
    DB::purge('core05_pgsql');
    DB::purge('core05_pgsql_child');
    DB::purge('core05_pgsql_writer');

    if (! core05PostgresAvailable()) {
        $this->markTestSkipped('A real PostgreSQL connection is required.');
    }

    Schema::connection('core05_pgsql')->dropIfExists(core05PostgresTable());
    Schema::connection('core05_pgsql')->create(core05PostgresTable(), function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->text('content')->nullable();
        $table->string('status');
        $table->timestamps();
    });
    Gate::policy(Core05PostgresMutationResource::class, Core05PostgresMutationPolicy::class);
    $this->actingAs(createSuperAdmin());
});

afterEach(function () {
    if (core05PostgresAvailable()) {
        Schema::connection('core05_pgsql')->dropIfExists(core05PostgresTable());
    }

    DB::disconnect('core05_pgsql');
    DB::disconnect('core05_pgsql_child');
    DB::disconnect('core05_pgsql_writer');
});

test('postgres locks base rows for distinct group and subquery effective scopes', function (string $shape) {
    $mounted = core05PostgresMountedResource();
    $resource = $mounted->newQuery()->create([
        'title' => 'PostgreSQL shape target',
        'content' => 'unchanged',
        'status' => 'eligible',
    ]);
    $scope = $mounted->newQuery()->where($mounted->qualifyColumn('status'), 'eligible');

    $scope = match ($shape) {
        'distinct' => $scope->distinct(),
        'group' => $scope
            ->groupBy($mounted->qualifyColumn('id'))
            ->havingRaw('count(*) >= ?', [1]),
        'subquery' => $scope->whereExists(function (QueryBuilder $query) use ($mounted): void {
            $query
                ->selectRaw('1')
                ->from($mounted->getTable().' as scoped')
                ->whereColumn('scoped.id', $mounted->qualifyColumn('id'))
                ->where('scoped.status', 'eligible');
        }),
    };

    app(TableMutationDispatcher::class)->dispatchAction(
        $scope,
        new TableMutationModelDescriptor($mounted),
        $resource->getKey(),
        'markReviewed',
        $mounted->getActions(),
    );

    expect($resource->fresh()->content)->toBe('reviewed');
})->with([
    'DISTINCT' => 'distinct',
    'GROUP BY and HAVING' => 'group',
    'correlated subquery' => 'subquery',
])->group('postgres');

test('postgres aggregate scopes fail closed before policy or handler execution', function () {
    $mounted = core05PostgresMountedResource();
    $resource = $mounted->newQuery()->create([
        'title' => 'PostgreSQL aggregate target',
        'content' => 'unchanged',
        'status' => 'eligible',
    ]);
    $scope = $mounted->newQuery();
    $scope->getQuery()->aggregate = ['function' => 'count', 'columns' => ['*']];

    expect(fn () => app(TableMutationDispatcher::class)->dispatchAction(
        $scope,
        new TableMutationModelDescriptor($mounted),
        $resource->getKey(),
        'markReviewed',
        $mounted->getActions(),
    ))->toThrow(HttpException::class, 'Aggregate table mutation scopes');

    expect($resource->fresh()->content)->toBe('unchanged');
})->group('postgres');

test('postgres bulk downloads retain bound custom ordering while de-duplicating joins', function () {
    $mounted = core05PostgresMountedResource();
    collect(['Alpha', 'Charlie', 'Bravo'])->each(fn (string $title) => $mounted->newQuery()->create([
        'title' => $title,
        'status' => 'eligible',
    ]));
    $duplicates = DB::connection('core05_pgsql')->query()
        ->selectRaw('1 as duplicate_marker')
        ->unionAll(DB::connection('core05_pgsql')->query()->selectRaw('2 as duplicate_marker'));
    $scope = $mounted->newQuery()
        ->crossJoinSub($duplicates, 'download_duplicates')
        ->orderByDesc('duplicate_marker')
        ->orderByRaw('CASE WHEN '.$mounted->qualifyColumn('title').' = ? THEN 0 ELSE 1 END', ['Bravo'])
        ->orderByDesc($mounted->qualifyColumn('title'))
        ->orderBy($mounted->qualifyColumn('id'));

    $context = app(TableMutationDispatcher::class)->prepareBulkDownload(
        $scope,
        new TableMutationModelDescriptor($mounted),
        'downloadCurrentOrder',
        $mounted->getBulkActions(),
        [],
        true,
    );
    $selection = $context['selection'];
    $ids = collect(DB::connection('core05_pgsql')->select($selection['sql'], $selection['bindings']))
        ->pluck('__aura_download_key');
    $titles = $mounted->newQuery()->whereKey($ids)->pluck('title', 'id');

    expect($ids)->toHaveCount(3)
        ->and($ids->unique())->toHaveCount(3)
        ->and($ids->map(fn (int|string $id): string => $titles[$id])->all())
        ->toBe(['Bravo', 'Charlie', 'Alpha']);
})->group('postgres');

test('postgres revalidates effective membership after waiting on a concurrent scope change', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the two-session PostgreSQL probe.');
    }

    $mounted = core05PostgresMountedResource();
    $resource = $mounted->newQuery()->create([
        'title' => 'PostgreSQL concurrent target',
        'content' => 'unchanged',
        'status' => 'eligible',
    ]);
    DB::disconnect('core05_pgsql');
    DB::purge('core05_pgsql');
    $signals = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

    if ($signals === false) {
        $this->fail('Unable to create the PostgreSQL probe signal channel.');
    }

    $child = pcntl_fork();

    if ($child === -1) {
        $this->fail('Unable to fork the PostgreSQL mutation probe.');
    }

    if ($child === 0) {
        fclose($signals[0]);
        fread($signals[1], 1);
        $childMounted = core05PostgresMountedResource('core05_pgsql_child');

        try {
            app(TableMutationDispatcher::class)->dispatchAction(
                $childMounted->newQuery()->where($childMounted->qualifyColumn('status'), 'eligible'),
                new TableMutationModelDescriptor($childMounted),
                $resource->getKey(),
                'markReviewed',
                $childMounted->getActions(),
            );

            exit(2);
        } catch (HttpExceptionInterface $exception) {
            fwrite($signals[1], 'http:'.$exception->getStatusCode());
            exit($exception->getStatusCode() === 404 ? 0 : 3);
        } catch (Throwable $exception) {
            fwrite($signals[1], $exception::class.':'.$exception->getMessage());
            exit(4);
        }
    }

    fclose($signals[1]);
    $writer = DB::connection('core05_pgsql_writer');
    $writer->beginTransaction();
    $writer->table(core05PostgresTable())
        ->where('id', $resource->getKey())
        ->update(['status' => 'excluded']);
    fwrite($signals[0], '1');
    usleep(300_000);
    $writer->commit();
    pcntl_waitpid($child, $status);
    $childResult = stream_get_contents($signals[0]);
    fclose($signals[0]);

    expect(pcntl_wifexited($status))->toBeTrue()
        ->and(pcntl_wexitstatus($status))->toBe(0, $childResult)
        ->and($resource->fresh()->status)->toBe('excluded')
        ->and($resource->fresh()->content)->toBe('unchanged');
})->group('postgres');

test('postgres globally orders multiple locked chunks from current values after a concurrent update', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the two-session PostgreSQL ordering probe.');
    }

    config()->set('aura.security.table_mutations.chunk_size', 2);
    $mounted = core05PostgresMountedResource();
    $resources = collect(['10', '20', '30', '50'])->map(fn (string $content) => $mounted->newQuery()->create([
        'title' => 'PostgreSQL ordered '.$content,
        'content' => $content,
        'status' => 'eligible',
    ]));
    DB::disconnect('core05_pgsql');
    DB::purge('core05_pgsql');
    $signals = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

    if ($signals === false) {
        $this->fail('Unable to create the PostgreSQL ordering signal channel.');
    }

    $child = pcntl_fork();

    if ($child === -1) {
        $this->fail('Unable to fork the PostgreSQL ordering probe.');
    }

    if ($child === 0) {
        fclose($signals[0]);
        core05PostgresReadSignal($signals[1]);
        $childMounted = core05PostgresMountedResource('core05_pgsql_child');
        Core05PostgresMutationResource::$capturedIds = [];
        $snapshotSignalled = false;
        DB::connection('core05_pgsql_child')->listen(function (QueryExecuted $query) use (
            &$snapshotSignalled,
            $signals,
        ): void {
            if ($snapshotSignalled || ! str_contains($query->sql, '__aura_mutation_key')) {
                return;
            }

            $snapshotSignalled = true;
            fwrite($signals[1], "snapshot\n");
            fflush($signals[1]);
            core05PostgresReadSignal($signals[1]);
        });

        try {
            app(TableMutationDispatcher::class)->dispatchBulk(
                $childMounted->newQuery()->orderBy($childMounted->qualifyColumn('content')),
                new TableMutationModelDescriptor($childMounted),
                'captureCurrentOrder',
                $childMounted->getBulkActions(),
                $resources->pluck('id')->all(),
                false,
                'collection',
            );

            fwrite($signals[1], json_encode(Core05PostgresMutationResource::$capturedIds, JSON_THROW_ON_ERROR));
            exit(0);
        } catch (Throwable $exception) {
            fwrite($signals[1], $exception::class.':'.$exception->getMessage());
            exit(2);
        }
    }

    fclose($signals[1]);
    $writer = DB::connection('core05_pgsql_writer');
    $barrier = '';
    $childExited = false;
    $childResult = '';
    $status = 0;

    try {
        $writer->beginTransaction();
        $writer->table(core05PostgresTable())
            ->where('id', $resources[0]->getKey())
            ->update(['content' => '40']);
        fwrite($signals[0], '1');
        stream_set_timeout($signals[0], 5);
        $barrier = trim((string) fgets($signals[0]));
        $writer->commit();
        fwrite($signals[0], '1');
        $childExited = core05PostgresWaitForChild($child, $status);
        $childResult = stream_get_contents($signals[0]);
    } finally {
        if ($writer->transactionLevel() > 0) {
            $writer->rollBack();
        }

        if (! $childExited) {
            @fwrite($signals[0], '1');
            core05PostgresWaitForChild($child, $status);
        }

        fclose($signals[0]);
    }

    $capturedIds = json_decode($childResult, true);

    expect($childExited)->toBeTrue()
        ->and(pcntl_wifexited($status))->toBeTrue()
        ->and(pcntl_wexitstatus($status))->toBe(0, $childResult)
        ->and($barrier)->toBe('snapshot')
        ->and($capturedIds)->toBe([
            $resources[1]->getKey(),
            $resources[2]->getKey(),
            $resources[0]->getKey(),
            $resources[3]->getKey(),
        ]);
})->group('postgres');
