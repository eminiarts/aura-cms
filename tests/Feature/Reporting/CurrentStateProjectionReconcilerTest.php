<?php

use Aura\Base\Events\ResourceUpdated;
use Aura\Base\Reporting\CurrentStateProjectionReconciler;
use Aura\Base\Reporting\ReportingProjection;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Support\Facades\DB;

beforeEach(function (): void {
    (require dirname(__DIR__, 3).'/database/migrations/create_aura_reporting_projections.php.stub')->up();
    DB::table(ReportingProjection::VALUES_TABLE)->delete();
    DB::table(ReportingProjection::COORDINATORS_TABLE)->delete();
});

test('reconciliation converges from current state for duplicate out of order delete and recreate events', function (): void {
    $resource = new Core29ProjectionResource;
    $id = DB::table($resource->getTable())->insertGetId([
        'title' => 'Initial',
        'type' => Core29ProjectionResource::$type,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table($resource->getMetaTable())->insert([
        'metable_type' => $resource->getMorphClass(),
        'metable_id' => $id,
        'key' => 'amount',
        'value' => '10.250000',
    ]);

    $reconciler = app(CurrentStateProjectionReconciler::class);
    $reconciler->reconcile(core29ProjectionEvent($resource, $id, '00000000-0000-4000-8000-000000000001'));

    expect(core29ProjectionState($resource, $id))->toBe([
        'present' => true,
        'last_event_id' => '00000000-0000-4000-8000-000000000001',
        'value_scaled' => 10_250_000,
        'value_rows' => 1,
    ]);

    DB::table($resource->getMetaTable())
        ->where('metable_type', $resource->getMorphClass())
        ->where('metable_id', $id)
        ->where('key', 'amount')
        ->update(['value' => '20.500000']);
    $reconciler->reconcile(core29ProjectionEvent($resource, $id, '00000000-0000-4000-8000-000000000002'));
    $reconciler->reconcile(core29ProjectionEvent($resource, $id, '00000000-0000-4000-8000-000000000001'));

    expect(core29ProjectionState($resource, $id))->toMatchArray([
        'present' => true,
        'last_event_id' => '00000000-0000-4000-8000-000000000001',
        'value_scaled' => 20_500_000,
        'value_rows' => 1,
    ]);

    DB::table($resource->getTable())->where('id', $id)->delete();
    $reconciler->reconcile(core29ProjectionEvent($resource, $id, '00000000-0000-4000-8000-000000000003'));
    $reconciler->reconcile(core29ProjectionEvent($resource, $id, '00000000-0000-4000-8000-000000000002'));

    expect(core29ProjectionState($resource, $id))->toMatchArray([
        'present' => false,
        'last_event_id' => '00000000-0000-4000-8000-000000000002',
        'value_scaled' => null,
        'value_rows' => 0,
    ]);

    DB::table($resource->getTable())->insert([
        'id' => $id,
        'title' => 'Recreated',
        'type' => Core29ProjectionResource::$type,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table($resource->getMetaTable())
        ->where('metable_type', $resource->getMorphClass())
        ->where('metable_id', $id)
        ->where('key', 'amount')
        ->update(['value' => '30.000000']);
    $reconciler->reconcile(core29ProjectionEvent($resource, $id, '00000000-0000-4000-8000-000000000004'));
    $reconciler->reconcile(core29ProjectionEvent($resource, $id, '00000000-0000-4000-8000-000000000003'));

    expect(core29ProjectionState($resource, $id))->toMatchArray([
        'present' => true,
        'last_event_id' => '00000000-0000-4000-8000-000000000003',
        'value_scaled' => 30_000_000,
        'value_rows' => 1,
    ]);
});

test('explicit resync repairs a quiet write without an event ordering claim', function (): void {
    $resource = new Core29ProjectionResource;
    $id = DB::table($resource->getTable())->insertGetId([
        'title' => 'Quiet',
        'type' => Core29ProjectionResource::$type,
        'created_at' => now(),
        'updated_at' => now(),
    ]);
    DB::table($resource->getMetaTable())->insert([
        'metable_type' => $resource->getMorphClass(),
        'metable_id' => $id,
        'key' => 'amount',
        'value' => '40.000000',
    ]);

    $resource->setAttribute('id', $id);
    $reconciler = app(CurrentStateProjectionReconciler::class);
    $reconciler->resync($resource);
    DB::table($resource->getMetaTable())
        ->where('metable_type', $resource->getMorphClass())
        ->where('metable_id', $id)
        ->where('key', 'amount')
        ->update(['value' => '41.125000']);
    $reconciler->resync($resource);

    expect(core29ProjectionState($resource, $id))->toMatchArray([
        'present' => true,
        'last_event_id' => null,
        'value_scaled' => 41_125_000,
        'value_rows' => 1,
    ]);
});

function core29ProjectionEvent(Core29ProjectionResource $resource, int $resourceId, string $eventId): ResourceUpdated
{
    return new ResourceUpdated(
        eventId: $eventId,
        operationId: '00000000-0000-4000-8000-000000000099',
        resourceClass: $resource::class,
        resourceType: $resource::$type,
        resourceMorphType: $resource->getMorphClass(),
        resourceId: $resourceId,
        occurredAt: now()->toISOString(),
        connectionName: (string) $resource->getConnectionName(),
        connectionIdentity: User::connectionCacheIdentity($resource->getConnection()),
        table: $resource->getTable(),
        keyName: $resource->getKeyName(),
        inheritanceColumn: $resource::getInheritanceColumn(),
        inheritanceValue: $resource::getInheritanceValue(),
        scopeMode: '',
        teamColumn: null,
        teamId: null,
        ownerColumn: null,
        ownerId: null,
        sharedAcrossTeams: false,
        hardDelete: false,
        physicalChanges: [],
        metaChanges: [],
    );
}

function core29ProjectionState(Core29ProjectionResource $resource, int $resourceId): array
{
    $coordinator = DB::table(ReportingProjection::COORDINATORS_TABLE)
        ->where('resource_type', $resource::class)
        ->where('resource_id', $resourceId)
        ->first();
    $values = DB::table(ReportingProjection::VALUES_TABLE)
        ->where('resource_type', $resource::class)
        ->where('resource_id', $resourceId);

    return [
        'present' => (bool) $coordinator->present,
        'last_event_id' => $coordinator->last_event_id,
        'value_scaled' => ($value = $values->value('value_scaled')) === null ? null : (int) $value,
        'value_rows' => $values->count(),
    ];
}

final class Core29ProjectionResource extends Resource
{
    public static string $type = 'Core29ProjectionResource';

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
