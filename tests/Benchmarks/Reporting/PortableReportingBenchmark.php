<?php

use Aura\Base\Tests\Fixtures\Reporting\PortableReportingProbe;
use Aura\Base\Tests\Fixtures\Reporting\ReportingDatabase;
use Aura\Base\Tests\TestCase;
use PHPUnit\Framework\SkippedWithMessageException;

uses(TestCase::class);

test('records the portable reporting storage-path baseline', function (): void {
    if (getenv('AURA_REPORTING_BENCHMARK') !== '1') {
        throw new SkippedWithMessageException(
            'Set AURA_REPORTING_BENCHMARK=1 to run the opt-in reporting benchmark.',
        );
    }

    $driver = getenv('AURA_REPORTING_DRIVER') ?: 'sqlite';
    $rowCount = filter_var(getenv('AURA_REPORTING_ROWS') ?: 10_000, FILTER_VALIDATE_INT);
    $iterations = filter_var(getenv('AURA_REPORTING_ITERATIONS') ?: 10, FILTER_VALIDATE_INT);
    $outputPath = getenv('AURA_REPORTING_BENCHMARK_OUTPUT') ?: null;

    expect($driver)->toBeIn(['sqlite', 'mysql', 'mariadb', 'pgsql'])
        ->and($rowCount)->toBeInt()->toBeGreaterThanOrEqual(10_000)
        ->and($iterations)->toBeInt()->toBeGreaterThanOrEqual(2);

    $connection = ReportingDatabase::connect($driver);

    if ($connection === null) {
        throw new RuntimeException("The [{$driver}] reporting benchmark connection is not configured.");
    }

    $probe = new PortableReportingProbe($connection);

    try {
        $probe->createSchema();
        $probe->seedPerformanceDataset($rowCount);
        $probe->prepareBenchmarkStatistics();

        $paths = [];

        foreach (['physical', 'meta', 'projection'] as $path) {
            $paths[$path] = [
                'workloads' => $probe->benchmarkPath($path, $iterations),
                'aggregate_explain' => $probe->explainAggregate($path),
            ];
        }

        $result = [
            'schema_version' => 1,
            'recorded_at' => now()->utc()->toIso8601String(),
            'environment' => [
                'driver' => $driver,
                'server_version' => (string) $connection->getPdo()->getAttribute(PDO::ATTR_SERVER_VERSION),
                'php_version' => PHP_VERSION,
            ],
            'dataset' => [
                'rows' => $rowCount,
                'noise_meta_fields_per_row' => 11,
                'seed' => PortableReportingProbe::PERFORMANCE_SEED,
            ],
            'iterations' => $iterations,
            'paths' => $paths,
        ];

        expect($result['paths'])->toHaveKeys(['physical', 'meta', 'projection']);

        if (is_string($outputPath) && $outputPath !== '') {
            $encoded = json_encode($result, JSON_PRETTY_PRINT | JSON_THROW_ON_ERROR | JSON_UNESCAPED_SLASHES).PHP_EOL;

            if (file_put_contents($outputPath, $encoded, LOCK_EX) === false) {
                throw new RuntimeException("Could not write reporting benchmark output to [{$outputPath}].");
            }
        }
    } finally {
        $probe->dropSchema();
        ReportingDatabase::disconnect($driver);
    }
})->group('reporting-research', 'benchmark');
