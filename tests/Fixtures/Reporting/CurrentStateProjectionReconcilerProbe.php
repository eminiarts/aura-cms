<?php

namespace Aura\Base\Tests\Fixtures\Reporting;

use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Str;
use RuntimeException;

final class CurrentStateProjectionReconcilerProbe
{
    private readonly string $coordinatorsTable;

    private readonly string $sourcesTable;

    private readonly string $valuesTable;

    public function __construct(private readonly Connection $connection)
    {
        $suffix = Str::lower(Str::random(10));
        $this->coordinatorsTable = 'core28_coordinators_'.$suffix;
        $this->sourcesTable = 'core28_sources_'.$suffix;
        $this->valuesTable = 'core28_values_'.$suffix;
    }

    public function createSchema(): void
    {
        $schema = $this->connection->getSchemaBuilder();
        $schema->create($this->sourcesTable, function (Blueprint $table): void {
            $table->unsignedBigInteger('id')->primary();
            $table->bigInteger('value_scaled')->nullable();
        });
        $schema->create($this->coordinatorsTable, function (Blueprint $table): void {
            $table->unsignedBigInteger('resource_id')->primary();
            $table->boolean('present')->default(false);
            $table->string('last_event_id')->nullable();
            $table->unsignedBigInteger('reconciliation_count')->default(0);
        });
        $schema->create($this->valuesTable, function (Blueprint $table): void {
            $table->unsignedBigInteger('resource_id');
            $table->string('field_key');
            $table->bigInteger('value_scaled')->nullable();
            $table->unique(['resource_id', 'field_key'], 'core28_reconciliation_identity');
        });
    }

    public function deleteSource(int $resourceId): void
    {
        $this->connection->table($this->sourcesTable)->where('id', $resourceId)->delete();
    }

    public function dropSchema(): void
    {
        $schema = $this->connection->getSchemaBuilder();
        $schema->dropIfExists($this->valuesTable);
        $schema->dropIfExists($this->coordinatorsTable);
        $schema->dropIfExists($this->sourcesTable);
    }

    /** @return array{present: bool, last_event_id: string|null, reconciliation_count: int, value_scaled: int|null, value_rows: int} */
    public function projectionState(int $resourceId): array
    {
        $coordinator = $this->connection->table($this->coordinatorsTable)
            ->where('resource_id', $resourceId)
            ->first();

        if ($coordinator === null) {
            throw new RuntimeException('The reporting reconciliation coordinator does not exist.');
        }

        $valueQuery = $this->connection->table($this->valuesTable)->where('resource_id', $resourceId);

        return [
            'present' => (bool) $coordinator->present,
            'last_event_id' => $coordinator->last_event_id === null ? null : (string) $coordinator->last_event_id,
            'reconciliation_count' => (int) $coordinator->reconciliation_count,
            'value_scaled' => ($value = $valueQuery->value('value_scaled')) === null ? null : (int) $value,
            'value_rows' => $valueQuery->count(),
        ];
    }

    public function reconcile(int $resourceId, ?string $eventId): void
    {
        $this->connection->transaction(function () use ($resourceId, $eventId): void {
            $this->connection->table($this->coordinatorsTable)->insertOrIgnore([
                'resource_id' => $resourceId,
                'present' => false,
                'last_event_id' => null,
                'reconciliation_count' => 0,
            ]);

            $coordinator = $this->connection->table($this->coordinatorsTable)
                ->where('resource_id', $resourceId)
                ->lockForUpdate()
                ->first();

            if ($coordinator === null) {
                throw new RuntimeException('The reporting reconciliation lock row disappeared.');
            }

            $source = $this->connection->table($this->sourcesTable)
                ->where('id', $resourceId)
                ->first();

            if ($source === null) {
                $this->connection->table($this->valuesTable)->where('resource_id', $resourceId)->delete();
            } else {
                $this->connection->table($this->valuesTable)->updateOrInsert(
                    ['resource_id' => $resourceId, 'field_key' => 'amount'],
                    ['value_scaled' => $source->value_scaled],
                );
            }

            $this->connection->table($this->coordinatorsTable)
                ->where('resource_id', $resourceId)
                ->update([
                    'present' => $source !== null,
                    'last_event_id' => $eventId,
                    'reconciliation_count' => ((int) $coordinator->reconciliation_count) + 1,
                ]);
        }, 3);
    }

    public function repairCurrentState(int $resourceId): void
    {
        $this->reconcile($resourceId, null);
    }

    public function setSource(int $resourceId, ?int $valueScaled): void
    {
        $this->connection->table($this->sourcesTable)->updateOrInsert(
            ['id' => $resourceId],
            ['value_scaled' => $valueScaled],
        );
    }
}
