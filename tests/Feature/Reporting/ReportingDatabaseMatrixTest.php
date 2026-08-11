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
                ->and($probe->explainAggregate($path))->not->toBeEmpty();
        }
    } finally {
        $probe->dropSchema();
        ReportingDatabase::disconnect($driver);
    }
})->with(['sqlite', 'mysql', 'mariadb', 'pgsql'])->group('reporting-research', 'database-guards');
