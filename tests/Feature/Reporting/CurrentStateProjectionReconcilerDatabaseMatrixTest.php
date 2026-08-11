<?php

use Aura\Base\Events\ResourceUpdated;
use Aura\Base\Reporting\CurrentStateProjectionReconciler;
use Aura\Base\Reporting\ReportingProjection;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Aura\Base\Tests\Fixtures\Reporting\ReportingDatabase;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use PHPUnit\Framework\SkippedWithMessageException;

final class Core29NativeProjectionResource extends Resource
{
    public static $customTable = true;

    public static ?string $ownerColumn = null;

    public static string $reportingConnection = 'testing';

    public static string $scopeMode = self::SCOPE_GLOBAL;

    public static ?string $teamColumn = null;

    public static string $type = 'Core29NativeProjectionResource';

    protected $fillable = ['title'];

    protected $table = 'core29_native_projection_resources';

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setConnection(self::$reportingConnection);
    }

    public static function getFields(): array
    {
        return [[
            'name' => 'Amount',
            'slug' => 'amount',
            'type' => 'Aura\\Base\\Fields\\Number',
            'number_type' => 'decimal',
            'precision' => 12,
            'scale' => 6,
        ]];
    }
}

/** @return array{0: Connection, 1: string} */
function core29ProjectionMatrixConnection(string $driver): array
{
    $connection = ReportingDatabase::connect($driver);

    if ($connection === null) {
        $prefix = match ($driver) {
            'mysql' => 'MYSQL',
            'mariadb' => 'MARIADB',
            'pgsql' => 'POSTGRES',
            default => null,
        };

        throw new SkippedWithMessageException($prefix === null
            ? 'SQLite is supplied by the package test harness.'
            : "Set AURA_TEST_{$prefix}_DATABASE to run the {$driver} projection reconciler contract.");
    }

    return [$connection, (string) $connection->getName()];
}

function core29NativeProjectionEvent(Core29NativeProjectionResource $resource, int $id, string $eventId): ResourceUpdated
{
    return new ResourceUpdated(
        eventId: $eventId,
        operationId: '00000000-0000-4000-8000-000000000099',
        resourceClass: $resource::class,
        resourceType: $resource::$type,
        resourceMorphType: $resource->getMorphClass(),
        resourceId: $id,
        occurredAt: now()->toISOString(),
        connectionName: (string) $resource->getConnectionName(),
        connectionIdentity: User::connectionCacheIdentity($resource->getConnection()),
        table: $resource->getTable(),
        keyName: $resource->getKeyName(),
        inheritanceColumn: $resource::getInheritanceColumn(),
        inheritanceValue: $resource::getInheritanceValue(),
        scopeMode: Resource::SCOPE_GLOBAL,
        teamColumn: null,
        teamId: null,
        ownerColumn: null,
        ownerId: null,
        sharedAcrossTeams: false,
        hardDelete: false,
        physicalChanges: [],
        metaChanges: [],
    );
}

/** @return array{present: bool, value: int|null, rows: int} */
function core29NativeProjectionState(Connection $connection, int $id): array
{
    $coordinator = $connection->table(ReportingProjection::COORDINATORS_TABLE)
        ->where('resource_type', Core29NativeProjectionResource::class)
        ->where('resource_id', $id)
        ->first();
    $values = $connection->table(ReportingProjection::VALUES_TABLE)
        ->where('resource_type', Core29NativeProjectionResource::class)
        ->where('resource_id', $id);

    return [
        'present' => (bool) $coordinator->present,
        'value' => ($value = $values->value('value_scaled')) === null ? null : (int) $value,
        'rows' => $values->count(),
    ];
}

test('the production projection reconciler converges on every claimed database', function (string $driver): void {
    [$connection, $connectionName] = core29ProjectionMatrixConnection($driver);
    Core29NativeProjectionResource::$reportingConnection = $connectionName;
    $schema = $connection->getSchemaBuilder();

    foreach ([ReportingProjection::VALUES_TABLE, ReportingProjection::COORDINATORS_TABLE, 'meta', 'core29_native_projection_resources'] as $table) {
        $schema->dropIfExists($table);
    }

    $schema->create('core29_native_projection_resources', function (Blueprint $table): void {
        $table->id();
        $table->string('title');
        $table->timestamps();
    });
    $schema->create('meta', function (Blueprint $table): void {
        $table->id();
        $table->string('metable_type');
        $table->unsignedBigInteger('metable_id');
        $table->string('key');
        $table->longText('value')->nullable();
        $table->timestamps();
        $table->unique(['metable_type', 'metable_id', 'key'], 'core29_native_meta_identity');
    });
    $schema->create(ReportingProjection::COORDINATORS_TABLE, function (Blueprint $table): void {
        $table->id();
        $table->string('resource_type');
        $table->unsignedBigInteger('resource_id');
        $table->boolean('present');
        $table->uuid('last_event_id')->nullable();
        $table->timestamp('reconciled_at')->nullable();
        $table->timestamps();
        $table->unique(['resource_type', 'resource_id'], 'core29_native_coordinator_identity');
    });
    $schema->create(ReportingProjection::VALUES_TABLE, function (Blueprint $table): void {
        $table->id();
        $table->string('resource_type');
        $table->unsignedBigInteger('resource_id');
        $table->string('field_key');
        $table->bigInteger('value_scaled')->nullable();
        $table->unsignedTinyInteger('contract_version');
        $table->timestamps();
        $table->unique(['resource_type', 'resource_id', 'field_key'], 'core29_native_value_identity');
    });

    try {
        $timestamp = now();
        $connection->table('core29_native_projection_resources')->insert(['id' => 42, 'title' => 'Original', 'created_at' => $timestamp, 'updated_at' => $timestamp]);
        $connection->table('meta')->insert(['metable_type' => Core29NativeProjectionResource::class, 'metable_id' => 42, 'key' => 'amount', 'value' => '10.250000', 'created_at' => $timestamp, 'updated_at' => $timestamp]);
        $resource = new Core29NativeProjectionResource;
        $reconciler = new CurrentStateProjectionReconciler;
        $first = core29NativeProjectionEvent($resource, 42, '00000000-0000-4000-8000-000000000001');
        $late = core29NativeProjectionEvent($resource, 42, '00000000-0000-4000-8000-000000000002');
        $reconciler->reconcile($first);
        $connection->table('meta')->where('metable_id', 42)->where('key', 'amount')->update(['value' => '20.500000']);
        $reconciler->reconcile($late);
        $reconciler->reconcile($first);

        expect(core29NativeProjectionState($connection, 42))->toBe(['present' => true, 'value' => 20_500_000, 'rows' => 1]);

        $connection->table('core29_native_projection_resources')->where('id', 42)->delete();
        $reconciler->reconcile($late);
        $reconciler->reconcile($first);

        expect(core29NativeProjectionState($connection, 42))->toBe(['present' => false, 'value' => null, 'rows' => 0]);

        $connection->table('core29_native_projection_resources')->insert(['id' => 42, 'title' => 'Recreated', 'created_at' => $timestamp, 'updated_at' => $timestamp]);
        $connection->table('meta')->where('metable_id', 42)->where('key', 'amount')->update(['value' => '30.000000']);
        $reconciler->reconcile($first);

        expect(core29NativeProjectionState($connection, 42))->toBe(['present' => true, 'value' => 30_000_000, 'rows' => 1]);
    } finally {
        foreach ([ReportingProjection::VALUES_TABLE, ReportingProjection::COORDINATORS_TABLE, 'meta', 'core29_native_projection_resources'] as $table) {
            $schema->dropIfExists($table);
        }

        ReportingDatabase::disconnect($driver);
    }
})->with(['sqlite', 'mysql', 'mariadb', 'pgsql'])->group('database-guards');
