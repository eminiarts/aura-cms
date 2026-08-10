<?php

use Aura\Base\BaseResource;
use Aura\Base\Livewire\Table\TableMutationDispatcher;
use Aura\Base\Livewire\Table\TableMutationModelDescriptor;
use Aura\Base\Resources\User;
use Illuminate\Database\Events\QueryExecuted;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;

class Core05MySqlMutationResource extends BaseResource
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
    ];

    public static array $capturedIds = [];

    protected $guarded = [];

    public function captureCurrentOrder(array $ids): void
    {
        array_push(static::$capturedIds, ...$ids);
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

class Core05MySqlMutationPolicy
{
    public function update(User $user, Core05MySqlMutationResource $resource): bool
    {
        return $user->exists && $resource->exists;
    }
}

function core05MySqlTable(): string
{
    static $table;

    return $table ??= 'core05_mysql_mutation_'.getmypid();
}

function core05MySqlAvailable(): bool
{
    if (! is_string(env('CORE05_MYSQL_DATABASE'))) {
        return false;
    }

    try {
        DB::connection('core05_mysql')->getPdo();

        return true;
    } catch (Throwable) {
        return false;
    }
}

function core05MySqlMountedResource(string $connection = 'core05_mysql'): Core05MySqlMutationResource
{
    return (new Core05MySqlMutationResource)
        ->setConnection($connection)
        ->setTable(core05MySqlTable());
}

beforeEach(function () {
    config()->set('database.connections.core05_mysql', [
        'driver' => 'mysql',
        'host' => env('CORE05_MYSQL_HOST', '127.0.0.1'),
        'port' => env('CORE05_MYSQL_PORT', 3306),
        'database' => env('CORE05_MYSQL_DATABASE'),
        'username' => env('CORE05_MYSQL_USERNAME', 'root'),
        'password' => env('CORE05_MYSQL_PASSWORD'),
        'unix_socket' => env('CORE05_MYSQL_SOCKET', ''),
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'prefix' => '',
        'strict' => true,
    ]);
    config()->set('database.connections.core05_mysql_child', config('database.connections.core05_mysql'));
    config()->set('database.connections.core05_mysql_writer', config('database.connections.core05_mysql'));
    DB::purge('core05_mysql');
    DB::purge('core05_mysql_child');
    DB::purge('core05_mysql_writer');

    if (! core05MySqlAvailable()) {
        $this->markTestSkipped('Set CORE05_MYSQL_DATABASE to run the real MySQL probes.');
    }

    Schema::connection('core05_mysql')->dropIfExists(core05MySqlTable());
    Schema::connection('core05_mysql')->create(core05MySqlTable(), function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->text('content')->nullable();
        $table->string('status');
        $table->timestamps();
    });
    Gate::policy(Core05MySqlMutationResource::class, Core05MySqlMutationPolicy::class);
    $this->actingAs(createSuperAdmin());
});

afterEach(function () {
    if (core05MySqlAvailable()) {
        Schema::connection('core05_mysql')->dropIfExists(core05MySqlTable());
    }

    DB::disconnect('core05_mysql');
    DB::disconnect('core05_mysql_child');
    DB::disconnect('core05_mysql_writer');
});

test('mysql revalidates distinct and grouped scopes with a current locking read', function (string $shape) {
    $mounted = core05MySqlMountedResource();
    $resource = $mounted->newQuery()->create([
        'title' => 'MySQL shape target',
        'content' => 'unchanged',
        'status' => 'eligible',
    ]);
    $scope = $mounted->newQuery()->where($mounted->qualifyColumn('status'), 'eligible');
    $scope = $shape === 'distinct'
        ? $scope->distinct()
        : $scope->groupBy($mounted->qualifyColumn('id'))->havingRaw('count(*) >= ?', [1]);

    app(TableMutationDispatcher::class)->dispatchAction(
        $scope,
        new TableMutationModelDescriptor($mounted),
        $resource->getKey(),
        'markReviewed',
        $mounted->getActions(),
    );

    expect($resource->fresh()->content)->toBe('reviewed');
})->with(['DISTINCT' => 'distinct', 'GROUP BY and HAVING' => 'group'])->group('mysql');

test('mysql revalidates membership after waiting on a concurrent scope change', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the two-session MySQL probe.');
    }

    $mounted = core05MySqlMountedResource();
    $resource = $mounted->newQuery()->create([
        'title' => 'MySQL concurrent target',
        'content' => 'unchanged',
        'status' => 'eligible',
    ]);
    DB::disconnect('core05_mysql');
    DB::purge('core05_mysql');
    $signals = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

    if ($signals === false) {
        $this->fail('Unable to create the MySQL probe signal channel.');
    }

    $child = pcntl_fork();

    if ($child === -1) {
        $this->fail('Unable to fork the MySQL mutation probe.');
    }

    if ($child === 0) {
        fclose($signals[0]);
        fread($signals[1], 1);
        $childMounted = core05MySqlMountedResource('core05_mysql_child');

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
    $writer = DB::connection('core05_mysql_writer');
    $writer->beginTransaction();
    $writer->table(core05MySqlTable())
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
})->group('mysql');

test('mysql globally orders multiple locked chunks from current values after a concurrent update', function () {
    if (! function_exists('pcntl_fork')) {
        $this->markTestSkipped('pcntl is required for the two-session MySQL ordering probe.');
    }

    config()->set('aura.security.table_mutations.chunk_size', 2);
    $mounted = core05MySqlMountedResource();
    $resources = collect(['10', '20', '30', '50'])->map(fn (string $content) => $mounted->newQuery()->create([
        'title' => 'MySQL ordered '.$content,
        'content' => $content,
        'status' => 'eligible',
    ]));
    DB::disconnect('core05_mysql');
    DB::purge('core05_mysql');
    $signals = stream_socket_pair(STREAM_PF_UNIX, STREAM_SOCK_STREAM, STREAM_IPPROTO_IP);

    if ($signals === false) {
        $this->fail('Unable to create the MySQL ordering signal channel.');
    }

    $child = pcntl_fork();

    if ($child === -1) {
        $this->fail('Unable to fork the MySQL ordering probe.');
    }

    if ($child === 0) {
        fclose($signals[0]);
        fread($signals[1], 1);
        $childMounted = core05MySqlMountedResource('core05_mysql_child');
        Core05MySqlMutationResource::$capturedIds = [];
        $snapshotSignalled = false;
        DB::connection('core05_mysql_child')->listen(function (QueryExecuted $query) use (
            &$snapshotSignalled,
            $signals,
        ): void {
            if ($snapshotSignalled || ! str_contains($query->sql, '__aura_mutation_key')) {
                return;
            }

            $snapshotSignalled = true;
            fwrite($signals[1], "snapshot\n");
            fflush($signals[1]);
            fread($signals[1], 1);
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

            fwrite($signals[1], json_encode(Core05MySqlMutationResource::$capturedIds, JSON_THROW_ON_ERROR));
            exit(0);
        } catch (Throwable $exception) {
            fwrite($signals[1], $exception::class.':'.$exception->getMessage());
            exit(2);
        }
    }

    fclose($signals[1]);
    $writer = DB::connection('core05_mysql_writer');
    $writer->beginTransaction();
    $writer->table(core05MySqlTable())
        ->where('id', $resources[0]->getKey())
        ->update(['content' => '40']);
    fwrite($signals[0], '1');
    stream_set_timeout($signals[0], 5);
    $barrier = trim((string) fgets($signals[0]));
    $writer->commit();
    fwrite($signals[0], '1');
    pcntl_waitpid($child, $status);
    $childResult = stream_get_contents($signals[0]);
    fclose($signals[0]);
    $capturedIds = json_decode($childResult, true);

    expect(pcntl_wifexited($status))->toBeTrue()
        ->and(pcntl_wexitstatus($status))->toBe(0, $childResult)
        ->and($barrier)->toBe('snapshot')
        ->and($capturedIds)->toBe([
            $resources[1]->getKey(),
            $resources[2]->getKey(),
            $resources[0]->getKey(),
            $resources[3]->getKey(),
        ]);
})->group('mysql');
