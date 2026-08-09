<?php

use Aura\Base\Fields\Date;
use Aura\Base\Fields\Datetime;
use Aura\Base\Fields\Filters\FilterCapability;
use Aura\Base\Resource;
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
    $field = $resource->fieldBySlug($fieldSlug);
    $fieldInstance = $resource->fieldClassBySlug($fieldSlug);
    $capability ??= $fieldInstance->filterCapability($resource, $field);
    $query = $resource->newQueryWithoutScopes();

    $capability->apply($query, $resource, $field, [
        'name' => $fieldSlug,
        'operator' => $operator,
        'value' => $value,
    ]);

    return $query->orderBy('id')->pluck('id')->map(fn ($id) => (int) $id)->all();
}

function exerciseNativeTemporalContract(string $connectionName, string $tableName): void
{
    $schema = Schema::connection($connectionName);

    $schema->create($tableName, function (Blueprint $table): void {
        $table->id();
        $table->date('published_on')->nullable();
        $table->timestamp('occurred_at')->nullable();
    });

    try {
        $resource = (new NativeTemporalFilterResource)
            ->setConnection($connectionName)
            ->setTable($tableName);

        $first = $resource->newQueryWithoutScopes()->create([
            'published_on' => '31.12.2025',
            'occurred_at' => '31.12.2025 23:59',
        ]);
        $second = $resource->newQueryWithoutScopes()->create([
            'published_on' => '20.03.2026',
            'occurred_at' => '20.03.2026 12:30',
        ]);
        $empty = $resource->newQueryWithoutScopes()->create([
            'published_on' => null,
            'occurred_at' => null,
        ]);

        $stored = DB::connection($connectionName)
            ->table($tableName)
            ->orderBy('id')
            ->get(['published_on', 'occurred_at']);

        expect((string) $stored[0]->published_on)->toBe('2025-12-31')
            ->and((string) $stored[0]->occurred_at)->toBe('2025-12-31 23:59:00')
            ->and($stored[2]->published_on)->toBeNull()
            ->and($stored[2]->occurred_at)->toBeNull();

        expect(nativeTemporalMatches($resource, 'published_on', 'date_is', '2025-12-31'))->toBe([$first->id])
            ->and(nativeTemporalMatches($resource, 'published_on', 'date_is_not', '2025-12-31'))->toBe([$second->id])
            ->and(nativeTemporalMatches($resource, 'published_on', 'date_before', '2026-02-01'))->toBe([$first->id])
            ->and(nativeTemporalMatches($resource, 'published_on', 'date_after', '2026-02-01'))->toBe([$second->id])
            ->and(nativeTemporalMatches($resource, 'published_on', 'date_on_or_before', '2025-12-31'))->toBe([$first->id])
            ->and(nativeTemporalMatches($resource, 'published_on', 'date_on_or_after', '2026-03-20'))->toBe([$second->id])
            ->and(nativeTemporalMatches($resource, 'published_on', 'date_is_empty'))->toBe([$empty->id])
            ->and(nativeTemporalMatches($resource, 'published_on', 'date_is_not_empty'))->toBe([$first->id, $second->id]);

        $dateRange = FilterCapability::dateRange(['date_between' => 'is between'], 'd.m.Y');

        expect(nativeTemporalMatches(
            $resource,
            'published_on',
            'date_between',
            ['from' => '2025-12-31', 'to' => '2026-02-01'],
            $dateRange,
        ))->toBe([$first->id]);

        expect(nativeTemporalMatches($resource, 'occurred_at', 'is', '2025-12-31T23:59'))->toBe([$first->id])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'is_not', '2025-12-31T23:59'))->toBe([$second->id])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'before', '2026-01-01T00:00'))->toBe([$first->id])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'after', '2026-01-01T00:00'))->toBe([$second->id])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'on_or_before', '2025-12-31T23:59'))->toBe([$first->id])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'on_or_after', '2026-03-20T12:30'))->toBe([$second->id])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'is_empty'))->toBe([$empty->id])
            ->and(nativeTemporalMatches($resource, 'occurred_at', 'is_not_empty'))->toBe([$first->id, $second->id]);

        expect(fn () => $resource->newQueryWithoutScopes()->create([
            'published_on' => 'not-a-date',
            'occurred_at' => '31.12.2025 23:59',
        ]))->toThrow(InvalidArgumentException::class, 'valid date');
    } finally {
        $schema->dropIfExists($tableName);
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

test('native temporal fields persist canonically and filter chronologically on sqlite', function () {
    exerciseNativeTemporalContract('testing', 'native_temporal_filters');
});

test('native temporal fields execute on mysql', function () {
    $connectionName = configureNativeTemporalConnection('mysql');

    if ($connectionName === null) {
        $this->markTestSkipped('Set AURA_TEST_MYSQL_DATABASE to run the native MySQL temporal contract.');
    }

    exerciseNativeTemporalContract($connectionName, 'aura_native_temporal_filters_'.getmypid());
});

test('native temporal fields execute on postgres', function () {
    $connectionName = configureNativeTemporalConnection('pgsql');

    if ($connectionName === null) {
        $this->markTestSkipped('Set AURA_TEST_POSTGRES_DATABASE to run the native PostgreSQL temporal contract.');
    }

    exerciseNativeTemporalContract($connectionName, 'aura_native_temporal_filters_'.getmypid());
});
