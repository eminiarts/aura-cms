<?php

namespace Aura\Base\Tests\Fixtures\Reporting;

use Illuminate\Database\Connection;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;
use InvalidArgumentException;
use RuntimeException;

final class PortableReportingProbe
{
    public const DECIMAL_PRECISION = 18;

    public const DECIMAL_SCALE = 6;

    public const PERFORMANCE_SEED = 28082026;

    private readonly string $factsTable;

    private readonly string $metaTable;

    private readonly string $projectionTable;

    public function __construct(private readonly Connection $connection)
    {
        $suffix = Str::lower(Str::random(10));
        $this->factsTable = 'core28_facts_'.$suffix;
        $this->metaTable = 'core28_meta_'.$suffix;
        $this->projectionTable = 'core28_projection_'.$suffix;

        $this->registerSqliteFunctions();
    }

    /**
     * @return array{count: int, sum: string|null, average: string|null, min: string|null, max: string|null}
     */
    public function aggregate(string $path, int $teamId = 1): array
    {
        [$query, $scaledExpression] = $this->queryForPath($path);

        $row = $query
            ->where('f.team_id', $teamId)
            ->selectRaw('COUNT(*) as row_count')
            ->selectRaw("SUM({$scaledExpression}) as scaled_sum")
            ->selectRaw("COUNT({$scaledExpression}) as valid_count")
            ->selectRaw("MIN({$scaledExpression}) as scaled_min")
            ->selectRaw("MAX({$scaledExpression}) as scaled_max")
            ->first();

        if ($row === null) {
            throw new RuntimeException('The reporting aggregate returned no result row.');
        }

        return [
            'count' => (int) $row->row_count,
            'sum' => $this->decimalFromScaled($row->scaled_sum),
            'average' => $this->averageFromScaled($row->scaled_sum, (int) $row->valid_count),
            'min' => $this->decimalFromScaled($row->scaled_min),
            'max' => $this->decimalFromScaled($row->scaled_max),
        ];
    }

    /**
     * @return array<string, array{cold_ms: float, median_ms: float, p95_ms: float, query_count: int, checksum: string}>
     */
    public function benchmarkPath(string $path, int $iterations = 10): array
    {
        if ($iterations < 2) {
            throw new InvalidArgumentException('Reporting benchmarks require at least two measured iterations.');
        }

        $workloads = [
            'aggregate' => fn (): array => $this->aggregate($path),
            'numeric_range' => fn (): array => $this->numericRange($path, '25.000000'),
            'grouped_sum' => fn (): array => $this->groupedSum($path),
            'monthly_buckets' => fn (): array => $this->monthlyBuckets($path, 'Europe/Zurich'),
        ];
        $results = [];

        foreach ($workloads as $name => $workload) {
            $coldStarted = hrtime(true);
            $coldResult = $workload();
            $coldMilliseconds = (hrtime(true) - $coldStarted) / 1_000_000;
            $timings = [];

            $this->connection->flushQueryLog();
            $this->connection->enableQueryLog();

            for ($iteration = 0; $iteration < $iterations; $iteration++) {
                $started = hrtime(true);
                $warmResult = $workload();
                $timings[] = (hrtime(true) - $started) / 1_000_000;

                if ($warmResult !== $coldResult) {
                    throw new RuntimeException("The [{$name}] benchmark result changed between iterations.");
                }
            }

            $queryCount = count($this->connection->getQueryLog());
            $this->connection->disableQueryLog();
            sort($timings);

            $results[$name] = [
                'cold_ms' => round($coldMilliseconds, 3),
                'median_ms' => round($this->percentile($timings, 0.5), 3),
                'p95_ms' => round($this->percentile($timings, 0.95), 3),
                'query_count' => intdiv($queryCount, $iterations),
                'checksum' => hash('sha256', json_encode($coldResult, JSON_THROW_ON_ERROR)),
            ];
        }

        return $results;
    }

    /**
     * @return array<string, int>
     */
    public function bucketCounts(
        string $path,
        string $timezone,
        string $bucket,
        string $start,
        string $end,
        int $teamId = 1,
    ): array {
        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            throw new InvalidArgumentException("Invalid reporting timezone [{$timezone}].");
        }

        [$query] = $this->queryForPath($path);
        [$expression, $bindings] = $this->bucketExpression(
            'f.occurred_at',
            Carbon::parse($start, $timezone),
            Carbon::parse($end, $timezone),
            $timezone,
            $bucket,
        );

        return $query
            ->where('f.team_id', $teamId)
            ->whereNotNull('f.occurred_at')
            ->selectRaw("{$expression} as bucket_key", $bindings)
            ->selectRaw('COUNT(*) as aggregate')
            ->groupBy('bucket_key')
            ->orderBy('bucket_key')
            ->pluck('aggregate', 'bucket_key')
            ->filter(fn (mixed $value, mixed $key): bool => $key !== null && $key !== '')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();
    }

    public function createSchema(): void
    {
        $schema = $this->connection->getSchemaBuilder();
        $driver = $this->driver();

        $schema->create($this->factsTable, function (Blueprint $table) use ($driver): void {
            $table->bigIncrements('id');
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('owner_id');
            $table->string('group_label', 32);

            if ($driver === 'sqlite') {
                $table->text('amount')->nullable();
            } else {
                $table->decimal('amount', self::DECIMAL_PRECISION, self::DECIMAL_SCALE)->nullable();
            }

            $table->dateTime('occurred_at');
            $table->index(['team_id', 'occurred_at', 'id']);
            $table->index(['team_id', 'group_label', 'id']);
        });

        $schema->create($this->metaTable, function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('metable_type');
            $table->unsignedBigInteger('metable_id');
            $table->string('key');
            $table->longText('value')->nullable();
            $table->unique(['metable_type', 'metable_id', 'key'], 'core28_meta_identity');
            $table->index(['metable_type', 'key', 'metable_id']);
        });

        $schema->create($this->projectionTable, function (Blueprint $table): void {
            $table->bigIncrements('id');
            $table->string('resource_type');
            $table->unsignedBigInteger('resource_id');
            $table->string('field_key');
            $table->bigInteger('value_scaled')->nullable();
            $table->unique(['resource_type', 'resource_id', 'field_key'], 'core28_projection_identity');
            $table->index(['resource_type', 'field_key', 'value_scaled', 'resource_id'], 'core28_projection_numeric');
        });
    }

    public function driver(): string
    {
        return $this->connection->getDriverName();
    }

    public function dropSchema(): void
    {
        $schema = $this->connection->getSchemaBuilder();
        $schema->dropIfExists($this->projectionTable);
        $schema->dropIfExists($this->metaTable);
        $schema->dropIfExists($this->factsTable);
    }

    /**
     * @return array<string, mixed>
     */
    public function explainAggregate(string $path): array
    {
        [$query, $scaledExpression] = $this->queryForPath($path);
        $query->where('f.team_id', 1)->selectRaw("SUM({$scaledExpression}) as scaled_sum");
        $sql = $query->toSql();
        $bindings = $query->getBindings();

        $rows = match ($this->driver()) {
            'pgsql' => $this->connection->select('EXPLAIN (FORMAT JSON) '.$sql, $bindings),
            'mysql', 'mariadb' => $this->connection->select('EXPLAIN FORMAT=JSON '.$sql, $bindings),
            default => $this->connection->select('EXPLAIN QUERY PLAN '.$sql, $bindings),
        };

        return json_decode(json_encode($rows, JSON_THROW_ON_ERROR), true, 512, JSON_THROW_ON_ERROR);
    }

    /**
     * @return array<string, int>
     */
    public function groupedSum(string $path, int $teamId = 1): array
    {
        [$query, $scaledExpression] = $this->queryForPath($path);

        return $query
            ->where('f.team_id', $teamId)
            ->select('f.group_label')
            ->selectRaw("SUM({$scaledExpression}) as scaled_sum")
            ->groupBy('f.group_label')
            ->orderBy('f.group_label')
            ->pluck('scaled_sum', 'group_label')
            ->map(fn (mixed $value): int => (int) $value)
            ->all();
    }

    /**
     * @return array<string, int>
     */
    public function monthlyBuckets(string $path, string $timezone, int $teamId = 1): array
    {
        return $this->bucketCounts(
            $path,
            $timezone,
            'month',
            '2024-01-01 00:00:00',
            '2026-01-01 00:00:00',
            $teamId,
        );
    }

    /**
     * @return array{count: int, ids: list<int>}
     */
    public function numericRange(string $path, string $minimum, int $teamId = 1): array
    {
        [$query, $scaledExpression] = $this->queryForPath($path);
        $minimumScaled = $this->scaledInteger($minimum);

        if ($minimumScaled === null) {
            throw new InvalidArgumentException('The reporting range minimum must be a valid decimal.');
        }

        $ids = $query
            ->where('f.team_id', $teamId)
            ->whereRaw("{$scaledExpression} > ?", [$minimumScaled])
            ->orderBy('f.id')
            ->pluck('f.id')
            ->map(fn (mixed $id): int => (int) $id)
            ->all();

        return ['count' => count($ids), 'ids' => $ids];
    }

    public function seedCorrectnessDataset(): void
    {
        $rows = [
            ['team' => 1, 'owner' => 1, 'group' => 'alpha', 'amount' => '-10.250000', 'meta' => '-10.250000', 'at' => '2024-03-31 00:30:00'],
            ['team' => 1, 'owner' => 1, 'group' => 'alpha', 'amount' => '0.100000', 'meta' => '0.100000', 'at' => '2024-03-31 01:30:00'],
            ['team' => 1, 'owner' => 2, 'group' => 'beta', 'amount' => '2.010000', 'meta' => '2.010000', 'at' => '2024-10-27 00:30:00'],
            ['team' => 1, 'owner' => 2, 'group' => 'beta', 'amount' => '30.000000', 'meta' => '30.000000', 'at' => '2024-10-27 01:30:00'],
            ['team' => 1, 'owner' => 3, 'group' => 'gamma', 'amount' => '100.000000', 'meta' => '100.000000', 'at' => '2024-02-29 12:00:00'],
            ['team' => 1, 'owner' => 3, 'group' => 'gamma', 'amount' => null, 'meta' => null, 'at' => '2025-01-01 00:00:00'],
            ['team' => 1, 'owner' => 3, 'group' => 'gamma', 'amount' => null, 'meta' => 'legacy-invalid', 'at' => '2025-06-01 00:00:00'],
            ['team' => 1, 'owner' => 3, 'group' => 'gamma', 'amount' => null, 'meta' => '1e3', 'at' => '2025-07-01 00:00:00'],
            ['team' => 2, 'owner' => 4, 'group' => 'secret', 'amount' => '999.000000', 'meta' => '999.000000', 'at' => '2024-06-01 00:00:00'],
        ];

        $this->insertLogicalRows($rows);
    }

    public function seedPerformanceDataset(int $rowCount, int $noiseMetaFields = 11): void
    {
        if ($rowCount < 1 || $noiseMetaFields < 0) {
            throw new InvalidArgumentException('Reporting benchmark sizes must be positive.');
        }

        mt_srand(self::PERFORMANCE_SEED);
        $startedAt = Carbon::parse('2024-01-01 00:00:00', 'UTC');

        for ($offset = 0; $offset < $rowCount; $offset += 500) {
            $rows = [];
            $limit = min($rowCount, $offset + 500);

            for ($index = $offset; $index < $limit; $index++) {
                $valid = $index % 10 < 8;
                $amount = $valid
                    ? $this->decimalFromScaled((($index % 200_001) - 100_000) * 1_000_003)
                    : null;

                $rows[] = [
                    'team' => ($index % 2) + 1,
                    'owner' => ($index % 10) + 1,
                    'group' => 'group-'.str_pad((string) ($index % 20), 2, '0', STR_PAD_LEFT),
                    'amount' => $amount,
                    'meta' => $index % 10 === 9 ? 'legacy-invalid-'.$index : $amount,
                    'at' => $startedAt->copy()->addHours($index % 17_520)->format('Y-m-d H:i:s'),
                    'noise' => $noiseMetaFields,
                ];
            }

            $this->insertLogicalRows($rows);
        }
    }

    public function tableName(string $kind): string
    {
        return match ($kind) {
            'facts' => $this->factsTable,
            'meta' => $this->metaTable,
            'projection' => $this->projectionTable,
            default => throw new InvalidArgumentException("Unknown reporting table kind [{$kind}]."),
        };
    }

    private function averageFromScaled(mixed $sum, int $count): ?string
    {
        if ($sum === null || $count === 0) {
            return null;
        }

        if (! is_int($sum) && ! is_string($sum)) {
            throw new RuntimeException('The scaled reporting sum is not an exact integer.');
        }

        $integerSum = filter_var($sum, FILTER_VALIDATE_INT);

        if ($integerSum === false) {
            throw new RuntimeException('The scaled reporting sum exceeds the exact V1 range.');
        }

        $quotient = intdiv((int) $integerSum, $count);
        $remainder = abs((int) $integerSum % $count);

        if (($remainder * 2) >= $count) {
            $quotient += (int) $integerSum < 0 ? -1 : 1;
        }

        return $this->decimalFromScaled($quotient);
    }

    /**
     * @return array{0: string, 1: list<string>}
     */
    private function bucketExpression(
        string $column,
        Carbon $start,
        Carbon $end,
        string $timezone,
        string $bucket,
    ): array {
        if (! in_array($bucket, ['day', 'week', 'month', 'quarter', 'year'], true)) {
            throw new InvalidArgumentException("Unsupported reporting date bucket [{$bucket}].");
        }

        if (! in_array($timezone, timezone_identifiers_list(), true)) {
            throw new InvalidArgumentException("Invalid reporting timezone [{$timezone}].");
        }

        $cursor = $this->floorBucket($start->copy()->timezone($timezone), $bucket);
        $last = $end->copy()->timezone($timezone);
        $clauses = [];
        $bindings = [];

        while ($cursor->lt($last)) {
            if (count($clauses) >= 400) {
                throw new InvalidArgumentException('Reporting date buckets are limited to 400 points.');
            }

            $next = $this->stepBucket($cursor->copy(), $bucket);
            $clauses[] = "WHEN {$column} >= ? AND {$column} < ? THEN ?";
            $bindings[] = $cursor->copy()->utc()->format('Y-m-d H:i:s');
            $bindings[] = $next->copy()->utc()->format('Y-m-d H:i:s');
            $bindings[] = $cursor->format('Y-m-d');
            $cursor = $next;
        }

        return ['CASE '.implode(' ', $clauses).' ELSE NULL END', $bindings];
    }

    private function decimalFromScaled(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }

        $digits = (string) $value;

        if (preg_match('/\A-?\d+\z/', $digits) !== 1) {
            throw new RuntimeException('The scaled reporting value is not an exact integer.');
        }

        $negative = str_starts_with($digits, '-');
        $digits = ltrim($digits, '-');
        $digits = str_pad($digits, self::DECIMAL_SCALE + 1, '0', STR_PAD_LEFT);
        $integer = substr($digits, 0, -self::DECIMAL_SCALE);
        $fraction = substr($digits, -self::DECIMAL_SCALE);
        $formatted = $integer.'.'.$fraction;

        return $negative && trim($digits, '0') !== '' ? '-'.$formatted : $formatted;
    }

    private function floorBucket(Carbon $date, string $bucket): Carbon
    {
        return match ($bucket) {
            'day' => $date->startOfDay(),
            'week' => $date->startOfWeek(Carbon::MONDAY),
            'month' => $date->startOfMonth(),
            'quarter' => $date->startOfQuarter(),
            'year' => $date->startOfYear(),
        };
    }

    /**
     * @param  list<array{team: int, owner: int, group: string, amount: string|null, meta: string|null, at: string, noise?: int}>  $rows
     */
    private function insertLogicalRows(array $rows): void
    {
        $facts = [];

        foreach ($rows as $row) {
            $facts[] = [
                'team_id' => $row['team'],
                'owner_id' => $row['owner'],
                'group_label' => $row['group'],
                'amount' => $row['amount'],
                'occurred_at' => $row['at'],
            ];
        }

        $this->connection->table($this->factsTable)->insert($facts);
        $firstId = (int) $this->connection->table($this->factsTable)->max('id') - count($rows) + 1;
        $meta = [];
        $projections = [];

        foreach ($rows as $offset => $row) {
            $resourceId = $firstId + $offset;

            if ($row['meta'] !== null) {
                $meta[] = [
                    'metable_type' => 'Core28ReportingProbe',
                    'metable_id' => $resourceId,
                    'key' => 'amount',
                    'value' => $row['meta'],
                ];
            }

            for ($noise = 0; $noise < ($row['noise'] ?? 0); $noise++) {
                $meta[] = [
                    'metable_type' => 'Core28ReportingProbe',
                    'metable_id' => $resourceId,
                    'key' => 'noise_'.str_pad((string) $noise, 2, '0', STR_PAD_LEFT),
                    'value' => (string) (($resourceId + $noise) % 10_000),
                ];
            }

            $projections[] = [
                'resource_type' => 'Core28ReportingProbe',
                'resource_id' => $resourceId,
                'field_key' => 'amount',
                'value_scaled' => $this->scaledInteger($row['amount']),
            ];
        }

        foreach (array_chunk($meta, 5_000) as $chunk) {
            $this->connection->table($this->metaTable)->insert($chunk);
        }

        $this->connection->table($this->projectionTable)->insert($projections);
    }

    /** @param list<float> $values */
    private function percentile(array $values, float $percentile): float
    {
        $position = ($percentile * (count($values) - 1));
        $lower = (int) floor($position);
        $upper = (int) ceil($position);

        if ($lower === $upper) {
            return $values[$lower];
        }

        return $values[$lower] + (($values[$upper] - $values[$lower]) * ($position - $lower));
    }

    /**
     * @return array{0: Builder, 1: string}
     */
    private function queryForPath(string $path): array
    {
        $query = $this->connection->table($this->factsTable.' as f');

        return match ($path) {
            'physical' => [$query, $this->scaledExpression('f.amount')],
            'meta' => [
                $query->leftJoin($this->metaTable.' as m', function ($join): void {
                    $join->on('m.metable_id', '=', 'f.id')
                        ->where('m.metable_type', 'Core28ReportingProbe')
                        ->where('m.key', 'amount');
                }),
                $this->scaledExpression('m.value'),
            ],
            'projection' => [
                $query->leftJoin($this->projectionTable.' as p', function ($join): void {
                    $join->on('p.resource_id', '=', 'f.id')
                        ->where('p.resource_type', 'Core28ReportingProbe')
                        ->where('p.field_key', 'amount');
                }),
                'p.value_scaled',
            ],
            default => throw new InvalidArgumentException("Unknown reporting storage path [{$path}]."),
        };
    }

    private function registerSqliteFunctions(): void
    {
        if ($this->driver() !== 'sqlite') {
            return;
        }

        $this->connection->getPdo()->sqliteCreateFunction(
            'aura_reporting_scaled',
            fn (mixed $value): ?int => $this->scaledInteger($value),
            1,
            true,
        );
    }

    private function scaledExpression(string $column): string
    {
        $trimmed = match ($this->driver()) {
            'pgsql' => "BTRIM(CAST({$column} AS TEXT))",
            'mysql', 'mariadb' => "TRIM(CAST({$column} AS CHAR))",
            default => $column,
        };

        return match ($this->driver()) {
            'pgsql' => "CASE WHEN {$trimmed} ~ '^[+-]?[0-9]{1,12}([.][0-9]{1,6})?$' THEN CAST(CAST({$trimmed} AS NUMERIC(18,6)) * 1000000 AS BIGINT) ELSE NULL END",
            'mysql', 'mariadb' => "CASE WHEN {$trimmed} REGEXP '^[+-]?[0-9]{1,12}([.][0-9]{1,6})?$' THEN CAST(ROUND(CAST({$trimmed} AS DECIMAL(18,6)) * 1000000, 0) AS SIGNED) ELSE NULL END",
            'sqlite' => "aura_reporting_scaled({$column})",
            default => throw new InvalidArgumentException("Unsupported reporting database driver [{$this->driver()}]."),
        };
    }

    private function scaledInteger(mixed $value): ?int
    {
        if ($value === null) {
            return null;
        }

        $value = trim((string) $value);

        if (preg_match('/\A([+-]?)(\d{1,12})(?:\.(\d{1,6}))?\z/', $value, $matches) !== 1) {
            return null;
        }

        $integer = ltrim($matches[2], '0');
        $integer = $integer === '' ? '0' : $integer;
        $fraction = str_pad($matches[3] ?? '', self::DECIMAL_SCALE, '0');
        $scaled = ((int) $integer * 1_000_000) + (int) $fraction;

        return $matches[1] === '-' && $scaled !== 0 ? -$scaled : $scaled;
    }

    private function stepBucket(Carbon $date, string $bucket): Carbon
    {
        return match ($bucket) {
            'day' => $date->addDay(),
            'week' => $date->addWeek(),
            'month' => $date->addMonth(),
            'quarter' => $date->addQuarter(),
            'year' => $date->addYear(),
        };
    }
}
