<?php

use Aura\Base\Tests\Fixtures\Reporting\PortableReportingProbe;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    $this->reportingProbe = new PortableReportingProbe(DB::connection());
    $this->reportingProbe->createSchema();
    $this->reportingProbe->seedCorrectnessDataset();
});

afterEach(function (): void {
    $this->reportingProbe->dropSchema();
});

test('physical meta and projection paths produce the same exact aggregate semantics', function (): void {
    $expected = [
        'count' => 8,
        'sum' => '121.860000',
        'average' => '24.372000',
        'min' => '-10.250000',
        'max' => '100.000000',
    ];

    foreach (['physical', 'meta', 'projection'] as $path) {
        expect($this->reportingProbe->aggregate($path))->toBe($expected, $path);
    }
})->group('reporting-research');

test('invalid legacy values are excluded instead of becoming plausible zeroes', function (): void {
    $safe = $this->reportingProbe->aggregate('meta');

    expect($safe['average'])->toBe('24.372000')
        ->and($safe['sum'])->toBe('121.860000');

    $unsafe = DB::selectOne("SELECT SUM(CAST(value AS REAL)) AS aggregate FROM {$this->reportingProbe->tableName('meta')} WHERE `key` = 'amount'");

    expect((float) $unsafe->aggregate)->not->toBe(121.86);
})->group('reporting-research');

test('numeric predicates are exact and tenant constrained on every storage path', function (): void {
    foreach (['physical', 'meta', 'projection'] as $path) {
        expect($this->reportingProbe->numericRange($path, '25.000000'))->toBe([
            'count' => 2,
            'ids' => [4, 5],
        ], $path);
    }

    expect($this->reportingProbe->aggregate('projection', 2))->toBe([
        'count' => 1,
        'sum' => '999.000000',
        'average' => '999.000000',
        'min' => '999.000000',
        'max' => '999.000000',
    ]);
})->group('reporting-research');

test('timezone buckets preserve spring gaps and repeated fall hours', function (): void {
    foreach (['physical', 'meta', 'projection'] as $path) {
        expect($this->reportingProbe->bucketCounts(
            $path,
            'Europe/Zurich',
            'day',
            '2024-03-30 00:00:00',
            '2024-04-02 00:00:00',
        ))->toBe(['2024-03-31' => 2], $path.' spring')
            ->and($this->reportingProbe->bucketCounts(
                $path,
                'Europe/Zurich',
                'day',
                '2024-10-26 00:00:00',
                '2024-10-29 00:00:00',
            ))->toBe(['2024-10-27' => 2], $path.' fall');
    }
})->group('reporting-research');

test('timezone buckets reject invalid zones and unbounded point counts', function (): void {
    expect(fn () => $this->reportingProbe->bucketCounts(
        'physical',
        'Not/A_Timezone',
        'day',
        '2024-01-01',
        '2024-01-02',
    ))->toThrow(InvalidArgumentException::class, 'Invalid reporting timezone')
        ->and(fn () => $this->reportingProbe->bucketCounts(
            'physical',
            'UTC',
            'day',
            '2024-01-01',
            '2026-01-01',
        ))->toThrow(InvalidArgumentException::class, 'limited to 400 points');
})->group('reporting-research');

test('each plain reporting workload executes one statement', function (): void {
    foreach (['physical', 'meta', 'projection'] as $path) {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $this->reportingProbe->aggregate($path);

        expect(DB::getQueryLog())->toHaveCount(1, $path);
        DB::disableQueryLog();
    }
})->group('reporting-research');
