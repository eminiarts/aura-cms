<?php

use Aura\Base\Reporting\ReportingProjection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

test('reporting migration recovers partial installation and retains projection data on rollback', function (): void {
    $migration = require dirname(__DIR__, 3).'/database/migrations/create_aura_reporting_projections.php.stub';
    $migration->up();
    DB::table(ReportingProjection::COORDINATORS_TABLE)->insert([
        'resource_type' => 'App\\Aura\\Resources\\Opportunity',
        'resource_id' => 42,
        'present' => true,
        'last_event_id' => null,
        'reconciled_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    Schema::drop(ReportingProjection::VALUES_TABLE);
    $migration->up();

    expect(Schema::hasTable(ReportingProjection::VALUES_TABLE))->toBeTrue()
        ->and(DB::table(ReportingProjection::COORDINATORS_TABLE)->where('resource_id', 42)->exists())->toBeTrue();

    DB::table(ReportingProjection::VALUES_TABLE)->insert([
        'resource_type' => 'App\\Aura\\Resources\\Opportunity',
        'resource_id' => 42,
        'field_key' => 'amount',
        'value_scaled' => 10_250_000,
        'contract_version' => ReportingProjection::CONTRACT_VERSION,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    $migration->down();

    expect(Schema::hasTable(ReportingProjection::COORDINATORS_TABLE))->toBeTrue()
        ->and(Schema::hasTable(ReportingProjection::VALUES_TABLE))->toBeTrue()
        ->and(DB::table(ReportingProjection::VALUES_TABLE)->where('resource_id', 42)->value('value_scaled'))->toBe(10_250_000);
});
