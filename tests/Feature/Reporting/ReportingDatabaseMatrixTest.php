<?php

use Aura\Base\Tests\Fixtures\Reporting\PortableReportingProbe;
use Aura\Base\Tests\Fixtures\Reporting\ReportingDatabase;
use PHPUnit\Framework\SkippedWithMessageException;

test('portable reporting semantics execute on every claimed database driver', function (string $driver): void {
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
            : "Set AURA_TEST_{$prefix}_DATABASE to run the {$driver} reporting contract.");
    }

    $probe = new PortableReportingProbe($connection);

    try {
        $probe->createSchema();
        $probe->seedCorrectnessDataset();
        $expected = [
            'count' => 8,
            'sum' => '121.860000',
            'average' => '24.372000',
            'min' => '-10.250000',
            'max' => '100.000000',
        ];

        foreach (['physical', 'meta', 'projection'] as $path) {
            expect($probe->aggregate($path))->toBe($expected, "{$driver} {$path}")
                ->and($probe->aggregate($path, 3))->toBe([
                    'count' => 3,
                    'sum' => null,
                    'average' => null,
                    'min' => null,
                    'max' => null,
                ], "{$driver} {$path} invalid and all-null")
                ->and($probe->numericRange($path, '25.000000'))->toBe([
                    'count' => 2,
                    'ids' => [4, 5],
                ], "{$driver} {$path} range")
                ->and($probe->bucketCounts(
                    $path,
                    'Europe/Zurich',
                    'day',
                    '2024-03-30 00:00:00',
                    '2024-04-02 00:00:00',
                ))->toBe(['2024-03-31' => 2], "{$driver} {$path} DST")
                ->and($probe->bucketCounts(
                    $path,
                    'Europe/Zurich',
                    'day',
                    '2024-10-26 00:00:00',
                    '2024-10-29 00:00:00',
                ))->toBe(['2024-10-27' => 2], "{$driver} {$path} repeated DST hour")
                ->and($probe->bucketCounts(
                    $path,
                    'Europe/Zurich',
                    'week',
                    '2024-03-25 00:00:00',
                    '2024-04-01 00:00:00',
                ))->toBe(['2024-03-25' => 2], "{$driver} {$path} ISO week")
                ->and($probe->bucketCounts(
                    $path,
                    'Europe/Zurich',
                    'month',
                    '2024-02-01 00:00:00',
                    '2024-04-01 00:00:00',
                ))->toBe(['2024-02-01' => 1, '2024-03-01' => 2], "{$driver} {$path} month")
                ->and($probe->bucketCounts(
                    $path,
                    'Europe/Zurich',
                    'quarter',
                    '2024-01-01 00:00:00',
                    '2025-01-01 00:00:00',
                ))->toBe(['2024-01-01' => 3, '2024-10-01' => 2], "{$driver} {$path} quarter")
                ->and($probe->bucketCounts(
                    $path,
                    'Europe/Zurich',
                    'year',
                    '2024-01-01 00:00:00',
                    '2026-01-01 00:00:00',
                ))->toBe(['2024-01-01' => 5, '2025-01-01' => 3], "{$driver} {$path} year")
                ->and($probe->explainAggregate($path))->not->toBeEmpty();
        }

        expect(fn () => $probe->bucketCounts('physical', 'Invalid/Timezone', 'day', '2024-01-01', '2024-01-02'))
            ->toThrow(InvalidArgumentException::class, 'Invalid reporting timezone')
            ->and(fn () => $probe->bucketCounts('physical', 'UTC', 'hour', '2024-01-01', '2024-01-02'))
            ->toThrow(InvalidArgumentException::class, 'Unsupported reporting date bucket')
            ->and(fn () => $probe->bucketCounts('physical', 'UTC', 'day', '2024-01-01', '2026-01-01'))
            ->toThrow(InvalidArgumentException::class, 'limited to 400 points');
    } finally {
        $probe->dropSchema();
        ReportingDatabase::disconnect($driver);
    }
})->with(['sqlite', 'mysql', 'mariadb', 'pgsql'])->group('reporting-research', 'database-guards');
