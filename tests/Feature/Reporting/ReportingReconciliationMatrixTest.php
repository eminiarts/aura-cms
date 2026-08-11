<?php

use Aura\Base\Tests\Fixtures\Reporting\CurrentStateProjectionReconcilerProbe;
use Aura\Base\Tests\Fixtures\Reporting\ReportingDatabase;
use PHPUnit\Framework\SkippedWithMessageException;

test('projection reconciliation converges from current state despite duplicate and out-of-order events', function (string $driver): void {
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
            : "Set AURA_TEST_{$prefix}_DATABASE to run the {$driver} reporting reconciliation contract.");
    }

    $probe = new CurrentStateProjectionReconcilerProbe($connection);

    try {
        $probe->createSchema();
        $probe->setSource(42, 100_000_000);
        $probe->reconcile(42, 'event-create-v1');

        expect($probe->projectionState(42))->toBe([
            'present' => true,
            'last_event_id' => 'event-create-v1',
            'reconciliation_count' => 1,
            'value_scaled' => 100_000_000,
            'value_rows' => 1,
        ]);

        $probe->setSource(42, 200_000_000);
        $probe->reconcile(42, 'event-update-v2');
        $probe->reconcile(42, 'event-create-v1');
        $probe->reconcile(42, 'event-create-v1');

        expect($probe->projectionState(42))->toBe([
            'present' => true,
            'last_event_id' => 'event-create-v1',
            'reconciliation_count' => 4,
            'value_scaled' => 200_000_000,
            'value_rows' => 1,
        ], "{$driver} late and duplicate older events re-read current state");

        $probe->deleteSource(42);
        $probe->reconcile(42, 'event-delete-v3');
        $probe->reconcile(42, 'event-update-v2');

        expect($probe->projectionState(42))->toBe([
            'present' => false,
            'last_event_id' => 'event-update-v2',
            'reconciliation_count' => 6,
            'value_scaled' => null,
            'value_rows' => 0,
        ], "{$driver} late update cannot resurrect a deleted source");

        $probe->setSource(42, 300_000_000);
        $probe->reconcile(42, 'event-recreate-v4');
        $probe->reconcile(42, 'event-delete-v3');

        expect($probe->projectionState(42))->toBe([
            'present' => true,
            'last_event_id' => 'event-delete-v3',
            'reconciliation_count' => 8,
            'value_scaled' => 300_000_000,
            'value_rows' => 1,
        ], "{$driver} late delete cannot erase a recreated current source");

        $probe->setSource(42, 400_000_000);

        expect($probe->projectionState(42)['value_scaled'])->toBe(300_000_000);

        $probe->repairCurrentState(42);

        expect($probe->projectionState(42))->toBe([
            'present' => true,
            'last_event_id' => null,
            'reconciliation_count' => 9,
            'value_scaled' => 400_000_000,
            'value_rows' => 1,
        ], "{$driver} repair reconciles a missed source event without synthetic ordering");
    } finally {
        $probe->dropSchema();
        ReportingDatabase::disconnect($driver);
    }
})->with(['sqlite', 'mysql', 'mariadb', 'pgsql'])->group('reporting-research', 'database-guards', 'reconciliation');
