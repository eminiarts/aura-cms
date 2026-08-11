<?php

use Aura\Base\Aura;
use Aura\Base\Commands\ResyncReportingProjections;
use Aura\Base\Reporting\CurrentStateProjectionReconciler;
use Aura\Base\Reporting\ReportingProjection;
use Aura\Base\Resource;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Console\Tester\CommandTester;

final class Core29ResyncCommandResource extends Resource
{
    public static string $type = 'Core29ResyncCommandResource';

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

test('resync command backfills fresh live resources and repairs quiet deletion tombstones', function (): void {
    (require dirname(__DIR__, 3).'/database/migrations/create_aura_reporting_projections.php.stub')->up();
    app(Aura::class)->registerResources([Core29ResyncCommandResource::class]);
    app(Aura::class)->captureBaselineState();
    expect(app(Aura::class)->getResources())->toContain(Core29ResyncCommandResource::class);
    expect(app(Aura::class)->findResourceBySlug(Core29ResyncCommandResource::class))->toBeInstanceOf(Core29ResyncCommandResource::class);
    $resource = new Core29ResyncCommandResource;
    $id = DB::table($resource->getTable())->insertGetId([
        'title' => 'Fresh backfill',
        'type' => Core29ResyncCommandResource::$type,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table($resource->getMetaTable())->insert([
        'metable_type' => $resource->getMorphClass(),
        'metable_id' => $id,
        'key' => 'amount',
        'value' => '12.500000',
    ]);

    $resync = new ResyncReportingProjections(app(CurrentStateProjectionReconciler::class));
    $resync->setLaravel(app());
    $command = new CommandTester($resync);
    $exitCode = $command->execute(['resource' => Core29ResyncCommandResource::class, '--chunk' => 1]);

    if ($exitCode !== 0) {
        throw new RuntimeException($command->getDisplay());
    }

    expect($exitCode)->toBe(0)
        ->and(DB::table(ReportingProjection::COORDINATORS_TABLE)->where('resource_type', Core29ResyncCommandResource::class)->where('resource_id', $id)->value('present'))->toBe(1)
        ->and(DB::table(ReportingProjection::VALUES_TABLE)->where('resource_type', Core29ResyncCommandResource::class)->where('resource_id', $id)->value('value_scaled'))->toBe(12_500_000);

    DB::table($resource->getTable())->where('id', $id)->delete();

    expect($command->execute(['resource' => Core29ResyncCommandResource::class, '--chunk' => 1]))->toBe(0)
        ->and(DB::table(ReportingProjection::COORDINATORS_TABLE)->where('resource_type', Core29ResyncCommandResource::class)->where('resource_id', $id)->value('present'))->toBe(0)
        ->and(DB::table(ReportingProjection::VALUES_TABLE)->where('resource_type', Core29ResyncCommandResource::class)->where('resource_id', $id)->doesntExist())->toBeTrue();
});
