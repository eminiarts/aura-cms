<?php

use Aura\Base\Fields\Date;
use Aura\Base\Fields\Datetime;
use Aura\Base\Fields\Filters\FilterCapability;
use Aura\Base\Resource;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class NativeTemporalFilterResource extends Resource
{
    public static $customTable = true;

    public static ?string $slug = 'native-temporal-filter-resource';

    public $timestamps = false;

    public static string $type = 'NativeTemporalFilterResource';

    public static bool $usesMeta = false;

    protected $fillable = [
        'published_on',
        'occurred_at',
    ];

    protected $table = 'native_temporal_filters';

    public static function getFields(): array
    {
        return [
            [
                'name' => 'Published on',
                'slug' => 'published_on',
                'type' => Date::class,
                'format' => 'd.m.Y',
            ],
            [
                'name' => 'Occurred at',
                'slug' => 'occurred_at',
                'type' => Datetime::class,
                'format' => 'd.m.Y H:i',
            ],
        ];
    }
}

function nativeTemporalQuery(
    NativeTemporalFilterResource $resource,
    string $fieldSlug,
    string $operator,
    mixed $value = null,
    ?FilterCapability $capability = null,
): Builder {
    $field = $resource->fieldBySlug($fieldSlug);
    $fieldInstance = $resource->fieldClassBySlug($fieldSlug);
    $capability ??= $fieldInstance->filterCapability($resource, $field);
    $query = $resource->newQueryWithoutScopes();

    $capability->apply($query, $resource, $field, [
        'name' => $fieldSlug,
        'operator' => $operator,
        'value' => $value,
    ]);

    return $query;
}

/**
 * @return list<int>
 */
function nativeTemporalMatches(
    NativeTemporalFilterResource $resource,
    string $fieldSlug,
    string $operator,
    mixed $value = null,
    ?FilterCapability $capability = null,
): array {
    return nativeTemporalQuery($resource, $fieldSlug, $operator, $value, $capability)
        ->orderBy('id')
        ->pluck('id')
        ->map(fn ($id) => (int) $id)
        ->all();
}

function createNativeTemporalTable(string $connectionName, string $tableName): void
{
    Schema::connection($connectionName)->create($tableName, function (Blueprint $table): void {
        $table->id();
        $table->date('published_on')->nullable();
        $table->timestamp('occurred_at')->nullable();
    });
}

function nativeTemporalResource(string $connectionName, string $tableName): NativeTemporalFilterResource
{
    return (new NativeTemporalFilterResource)
        ->setConnection($connectionName)
        ->setTable($tableName);
}

function insertNativeTemporalRow(
    NativeTemporalFilterResource $resource,
    ?string $publishedOn,
    ?string $occurredAt,
): int {
    return (int) DB::connection($resource->getConnectionName())
        ->table($resource->getTable())
        ->insertGetId([
            'published_on' => $publishedOn,
            'occurred_at' => $occurredAt,
        ]);
}

function setMySqlSessionTimezone(string $connectionName, string $timezone): void
{
    $connection = DB::connection($connectionName);
    $quotedTimezone = $connection->getPdo()->quote($timezone);
    $connection->statement('SET time_zone = '.$quotedTimezone);
}

function mysqlSessionTimezone(string $connectionName): string
{
    return (string) DB::connection($connectionName)
        ->selectOne('SELECT @@session.time_zone AS timezone')
        ->timezone;
}

function localWallTimeAsUtc(string $value, string $timezone): string
{
    return (new DateTimeImmutable($value, new DateTimeZone($timezone)))
        ->setTimezone(new DateTimeZone('UTC'))
        ->format('Y-m-d H:i:s');
}

function exerciseNativeTemporalContract(string $connectionName, string $tableName): void
{
    config()->set('app.timezone', 'UTC');
    $schema = Schema::connection($connectionName);
    $driver = DB::connection($connectionName)->getDriverName();
    $originalMySqlTimezone = $driver === 'mysql' ? mysqlSessionTimezone($connectionName) : null;

    if ($driver === 'mysql') {
        setMySqlSessionTimezone($connectionName, '+00:00');
    }

    createNativeTemporalTable($connectionName, $tableName);

    try {
        $resource = nativeTemporalResource($connectionName, $tableName);
        $firstId = insertNativeTemporalRow($resource, '2025-12-31', '2025-12-31 23:59:00');
        $secondId = insertNativeTemporalRow($resource, '2026-03-20', '2026-03-20 12:30:00');
        $emptyId = insertNativeTemporalRow($resource, null, null);

        expect(nativeTemporalMatches($resource, 'published_on', 'date_is', '2025-12-31'))->toBe([$firstId])
            ->and(nativeTemporalMatches($resource, 'published_on', 'date_is_not', '2025-12-31'))->toBe([$secondId])
            ->and(nativeTemporalMatches($resource, 'published_on', 'date_before', '2026-02-01'))->toBe([$firstId])
            ->and(nativeTemporalMatches($resource, 'published_on', 'date_after', '2026-02-01'))->toBe([$secondId])
            ->and(nativeTemporalMatches($resource, 'published_on', 'date_on_or_before', '2025-12-31'))->toBe([$firstId])
            ->and(nativeTemporalMatches($resource, 'published_on', 'date_on_or_after', '2026-03-20'))->toBe([$secondId])
            ->and(nativeTemporalMatches($resource, 'published_on', 'date_is_empty'))->toBe([$emptyId])
            ->and(nativeTemporalMatches($resource, 'published_on', 'date_is_not_empty'))->toBe([$firstId, $secondId]);

        $dateRange = FilterCapability::dateRange(['date_between' => 'is between'], 'd.m.Y');

        expect(nativeTemporalMatches(
            $resource,
            'published_on',
            'date_between',
            ['from' => '2025-12-31', 'to' => '2026-02-01'],
            $dateRange,
        ))->toBe([$firstId]);

        expect(nativeTemporalMatches($resource, 'occurred_at', 'is', '2025-12-31T23:59'))->toBe([$firstId])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'is_not', '2025-12-31T23:59'))->toBe([$secondId])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'before', '2026-01-01T00:00'))->toBe([$firstId])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'after', '2026-01-01T00:00'))->toBe([$secondId])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'on_or_before', '2025-12-31T23:59'))->toBe([$firstId])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'on_or_after', '2026-03-20T12:30'))->toBe([$secondId])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'is_empty'))->toBe([$emptyId])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'is_not_empty'))->toBe([$firstId, $secondId])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'is', 'not-a-date'))->toBe([])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'is', ['2025-12-31T23:59']))->toBe([]);
    } finally {
        $schema->dropIfExists($tableName);

        if ($originalMySqlTimezone !== null) {
            setMySqlSessionTimezone($connectionName, $originalMySqlTimezone);
        }
    }
}

function exerciseDstContract(string $connectionName, string $tableName): void
{
    config()->set('app.timezone', 'Europe/Zurich');
    $schema = Schema::connection($connectionName);
    $connection = DB::connection($connectionName);
    $driver = $connection->getDriverName();
    $originalMySqlTimezone = $driver === 'mysql' ? mysqlSessionTimezone($connectionName) : null;

    if ($driver === 'mysql') {
        setMySqlSessionTimezone($connectionName, '+00:00');
    }

    createNativeTemporalTable($connectionName, $tableName);

    try {
        $resource = nativeTemporalResource($connectionName, $tableName);
        $beforeGap = '2026-03-29 01:30:00';
        $afterGap = '2026-03-29 03:30:00';
        $fold = '2026-10-25 02:30:00';

        $beforeGapId = insertNativeTemporalRow(
            $resource,
            null,
            $driver === 'mysql' ? localWallTimeAsUtc($beforeGap, 'Europe/Zurich') : $beforeGap,
        );
        $afterGapId = insertNativeTemporalRow(
            $resource,
            null,
            $driver === 'mysql' ? localWallTimeAsUtc($afterGap, 'Europe/Zurich') : $afterGap,
        );
        insertNativeTemporalRow(
            $resource,
            null,
            $driver === 'mysql' ? localWallTimeAsUtc($fold, 'Europe/Zurich') : $fold,
        );

        expect(nativeTemporalMatches($resource, 'occurred_at', 'is', '2026-03-29T01:30'))->toBe([$beforeGapId])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'is', '2026-03-29T03:30'))->toBe([$afterGapId])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'is', '2026-03-29T02:30'))->toBe([])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'is', '2026-10-25T02:30'))->toBe([]);
    } finally {
        $schema->dropIfExists($tableName);

        if ($originalMySqlTimezone !== null) {
            setMySqlSessionTimezone($connectionName, $originalMySqlTimezone);
        }
    }
}

function exerciseWideTimestampContract(string $connectionName, string $tableName): void
{
    config()->set('app.timezone', 'UTC');
    $schema = Schema::connection($connectionName);
    createNativeTemporalTable($connectionName, $tableName);

    try {
        $resource = nativeTemporalResource($connectionName, $tableName);
        $beforeMySqlRange = insertNativeTemporalRow($resource, null, '1969-12-31 23:59:59');
        $afterMySqlRange = insertNativeTemporalRow($resource, null, '2040-01-01 00:00:00');

        expect(nativeTemporalMatches($resource, 'occurred_at', 'is', '1969-12-31T23:59:59'))->toBe([$beforeMySqlRange])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'is', '2040-01-01T00:00:00'))->toBe([$afterMySqlRange]);
    } finally {
        $schema->dropIfExists($tableName);
    }
}

function exerciseMySqlTimestampContract(string $connectionName, string $tableName): void
{
    config()->set('app.timezone', 'UTC');
    $schema = Schema::connection($connectionName);
    $originalTimezone = mysqlSessionTimezone($connectionName);
    setMySqlSessionTimezone($connectionName, '+00:00');
    createNativeTemporalTable($connectionName, $tableName);

    try {
        $resource = nativeTemporalResource($connectionName, $tableName);
        $minimumId = insertNativeTemporalRow($resource, null, '1970-01-01 00:00:01');
        $maximumId = insertNativeTemporalRow($resource, null, '2038-01-19 03:14:07');
        $stableId = insertNativeTemporalRow($resource, null, '2026-06-15 12:00:00');

        expect(nativeTemporalMatches($resource, 'occurred_at', 'is', '1970-01-01T00:00:01'))->toBe([$minimumId])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'is', '2038-01-19T03:14:07'))->toBe([$maximumId]);

        foreach (['1970-01-01T00:00:00', '2038-01-19T03:14:08'] as $outsideRange) {
            $query = nativeTemporalQuery($resource, 'occurred_at', 'is', $outsideRange);

            expect($query->toSql())->toContain('1 = 0')
                ->and($query->pluck('id')->all())->toBe([]);
        }

        setMySqlSessionTimezone($connectionName, '+09:00');

        expect((string) DB::connection($connectionName)
            ->table($tableName)
            ->where('id', $stableId)
            ->value('occurred_at'))->toBe('2026-06-15 21:00:00')
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'is', '2026-06-15T12:00'))->toBe([$stableId])
            ->and(nativeTemporalQuery($resource, 'occurred_at', 'is', '2026-06-15T12:00')->toSql())
            ->toContain('unix_timestamp');
    } finally {
        $schema->dropIfExists($tableName);
        setMySqlSessionTimezone($connectionName, $originalTimezone);
    }
}

function configureNativeTemporalConnection(string $driver): ?string
{
    $prefix = $driver === 'mysql' ? 'AURA_TEST_MYSQL_' : 'AURA_TEST_POSTGRES_';
    $database = getenv($prefix.'DATABASE');

    if (! is_string($database) || $database === '') {
        return null;
    }

    $connectionName = 'native_temporal_'.$driver;
    $usernameDefault = $driver === 'mysql' ? 'root' : get_current_user();
    $configuration = [
        'driver' => $driver === 'mysql' ? 'mysql' : 'pgsql',
        'host' => getenv($prefix.'HOST') ?: '127.0.0.1',
        'port' => getenv($prefix.'PORT') ?: ($driver === 'mysql' ? '3306' : '5432'),
        'database' => $database,
        'username' => getenv($prefix.'USERNAME') ?: $usernameDefault,
        'password' => getenv($prefix.'PASSWORD') ?: '',
        'charset' => 'utf8',
        'prefix' => '',
    ];

    if ($driver === 'mysql') {
        $configuration['collation'] = 'utf8_unicode_ci';
        $configuration['strict'] = true;
    }

    config()->set('database.connections.'.$connectionName, $configuration);
    DB::purge($connectionName);
    DB::connection($connectionName)->getPdo();

    return $connectionName;
}

test('native temporal filters execute on sqlite', function () {
    exerciseNativeTemporalContract('testing', 'native_temporal_filters');
    exerciseDstContract('testing', 'native_temporal_dst');
    exerciseWideTimestampContract('testing', 'native_temporal_wide');
});

test('native temporal filters execute on mysql boundaries and changed session timezone', function () {
    $connectionName = configureNativeTemporalConnection('mysql');

    if ($connectionName === null) {
        $this->markTestSkipped('Set AURA_TEST_MYSQL_DATABASE to run the native MySQL temporal contract.');
    }

    exerciseNativeTemporalContract($connectionName, 'aura_native_temporal_filters_'.getmypid());
    exerciseDstContract($connectionName, 'aura_native_temporal_dst_'.getmypid());
    exerciseMySqlTimestampContract($connectionName, 'aura_native_temporal_bounds_'.getmypid());
});

test('native temporal filters execute on postgres boundaries', function () {
    $connectionName = configureNativeTemporalConnection('pgsql');

    if ($connectionName === null) {
        $this->markTestSkipped('Set AURA_TEST_POSTGRES_DATABASE to run the native PostgreSQL temporal contract.');
    }

    exerciseNativeTemporalContract($connectionName, 'aura_native_temporal_filters_'.getmypid());
    exerciseDstContract($connectionName, 'aura_native_temporal_dst_'.getmypid());
    exerciseWideTimestampContract($connectionName, 'aura_native_temporal_wide_'.getmypid());
});
