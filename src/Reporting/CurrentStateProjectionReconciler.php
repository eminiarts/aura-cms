<?php

namespace Aura\Base\Reporting;

use Aura\Base\Events\ResourceEvent;
use Aura\Base\Fields\Number;
use Aura\Base\Resource;
use Aura\Base\Resources\User;
use Illuminate\Database\Connection;
use RuntimeException;

final class CurrentStateProjectionReconciler
{
    public function reconcile(ResourceEvent $event): void
    {
        $this->reconcileCurrentState($event, $event->eventId);
    }

    public function resync(Resource $resource): void
    {
        $this->reconcileCurrentState(ReportingResyncEvent::fromResource($resource), null);
    }

    private function declaredProjectedKeys(Resource $resource): array
    {
        return collect($resource->inputFields())
            ->filter(function (array $field) use ($resource): bool {
                $slug = $field['slug'] ?? null;

                return is_string($slug)
                    && $resource->isMetaField($slug)
                    && $this->isProjectedNumber($resource->fieldClassBySlug($slug), $field);
            })
            ->pluck('slug')
            ->values()
            ->all();
    }

    private function insertCoordinator(Connection $connection, ResourceEvent $event): void
    {
        $connection->table(ReportingProjection::COORDINATORS_TABLE)->insertOrIgnore([
            'resource_type' => $event->resourceClass,
            'resource_id' => $event->resourceId,
            'present' => false,
            'last_event_id' => null,
            'reconciled_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function isProjectedNumber(mixed $fieldClass, array $field): bool
    {
        if (! $fieldClass instanceof Number) {
            return false;
        }

        $precision = $field['precision'] ?? Number::DEFAULT_PRECISION;
        $scale = $field['scale'] ?? Number::DEFAULT_SCALE;

        return filter_var($precision, FILTER_VALIDATE_INT) !== false
            && filter_var($scale, FILTER_VALIDATE_INT) !== false
            && (int) $precision >= 1
            && (int) $precision <= 18
            && (int) $scale >= 0
            && (int) $scale <= 6;
    }

    private function projectedValues(Connection $connection, Resource $resource, int|string $resourceId, array $source): array
    {
        $meta = $resource->usesMeta()
            ? $connection->table($resource->getMetaTable())
                ->where('metable_type', $resource->getMorphClass())
                ->where('metable_id', $resourceId)
                ->pluck('value', 'key')
                ->all()
            : [];
        $values = [];

        foreach ($resource->inputFields() as $field) {
            $slug = $field['slug'] ?? null;

            if (! is_string($slug) || ! $resource->isMetaField($slug)) {
                continue;
            }

            $fieldClass = $resource->fieldClassBySlug($slug);

            if (! $this->isProjectedNumber($fieldClass, $field)) {
                continue;
            }

            $values[$slug] = $this->scaledValue($fieldClass, $field, $meta[$slug] ?? null);
        }

        return $values;
    }

    private function readSource(Connection $connection, ResourceEvent $event, Resource $resource): ?object
    {
        $query = $connection->table($resource->getTable())
            ->where($resource->getKeyName(), $event->resourceId);

        if ($event->inheritanceColumn !== null && $event->inheritanceValue !== null) {
            $query->where($event->inheritanceColumn, $event->inheritanceValue);
        }

        $source = $query->first();

        if ($source === null) {
            return null;
        }

        $deletedAtColumn = method_exists($resource, 'getDeletedAtColumn') ? $resource->getDeletedAtColumn() : null;

        return $deletedAtColumn !== null && $source->{$deletedAtColumn} !== null ? null : $source;
    }

    private function reconcileCurrentState(ResourceEvent $event, ?string $eventId): void
    {
        $resource = $this->resourceForEvent($event);
        $connection = $resource->getConnection();

        if (! $this->tablesExist($connection)) {
            return;
        }

        $connection->transaction(function () use ($connection, $event, $eventId, $resource): void {
            $this->insertCoordinator($connection, $event);

            $coordinator = $connection->table(ReportingProjection::COORDINATORS_TABLE)
                ->where('resource_type', $event->resourceClass)
                ->where('resource_id', $event->resourceId)
                ->lockForUpdate()
                ->first();

            if ($coordinator === null) {
                throw new RuntimeException('The reporting projection coordinator lock row disappeared.');
            }

            $source = $this->readSource($connection, $event, $resource);
            $values = $source === null ? [] : $this->projectedValues($connection, $resource, $event->resourceId, (array) $source);
            $declaredKeys = $this->declaredProjectedKeys($resource);

            $this->replaceValues($connection, $event, $declaredKeys, $values, $source !== null);

            $connection->table(ReportingProjection::COORDINATORS_TABLE)
                ->where('resource_type', $event->resourceClass)
                ->where('resource_id', $event->resourceId)
                ->update([
                    'present' => $source !== null,
                    'last_event_id' => $eventId,
                    'reconciled_at' => now(),
                    'updated_at' => now(),
                ]);
        }, 3);
    }

    private function replaceValues(
        Connection $connection,
        ResourceEvent $event,
        array $declaredKeys,
        array $values,
        bool $sourceExists,
    ): void {
        $query = $connection->table(ReportingProjection::VALUES_TABLE)
            ->where('resource_type', $event->resourceClass)
            ->where('resource_id', $event->resourceId);

        if (! $sourceExists || $declaredKeys === []) {
            $query->delete();

            return;
        }

        $query->whereNotIn('field_key', $declaredKeys)->delete();
        $timestamp = now();
        $rows = [];

        foreach ($declaredKeys as $fieldKey) {
            $rows[] = [
                'resource_type' => $event->resourceClass,
                'resource_id' => $event->resourceId,
                'field_key' => $fieldKey,
                'value_scaled' => $values[$fieldKey] ?? null,
                'contract_version' => ReportingProjection::CONTRACT_VERSION,
                'created_at' => $timestamp,
                'updated_at' => $timestamp,
            ];
        }

        $connection->table(ReportingProjection::VALUES_TABLE)->upsert(
            $rows,
            ['resource_type', 'resource_id', 'field_key'],
            ['value_scaled', 'contract_version', 'updated_at'],
        );
    }

    private function resourceForEvent(ResourceEvent $event): Resource
    {
        if (! is_a($event->resourceClass, Resource::class, true)) {
            throw new RuntimeException('The reporting projection event resource class is invalid.');
        }

        /** @var resource $resource */
        $resource = new $event->resourceClass;
        $resource->setConnection($event->connectionName);
        $connection = $resource->getConnection();

        if (User::connectionCacheIdentity($connection) !== $event->connectionIdentity
            || $resource->getTable() !== $event->table
            || $resource->getKeyName() !== $event->keyName
            || $resource->getMorphClass() !== $event->resourceMorphType
            || $resource::getInheritanceColumn() !== $event->inheritanceColumn
            || $resource::getInheritanceValue() !== $event->inheritanceValue) {
            throw new RuntimeException('The reporting projection event does not match its authoritative resource connection.');
        }

        return $resource;
    }

    private function scaledValue(Number $fieldClass, array $field, mixed $value): ?int
    {
        $normalized = $fieldClass->normalizeForExactQuery($value, $field);

        if ($normalized === null || ! preg_match('/\A(-?)(\d+)(?:\.(\d+))?\z/', (string) $normalized, $matches)) {
            return null;
        }

        $fraction = str_pad($matches[3] ?? '', 6, '0');
        $integer = ltrim($matches[2], '0');
        $integer = $integer === '' ? '0' : $integer;

        if (strlen($integer) > 12) {
            return null;
        }

        $scaled = ((int) $integer * 1_000_000) + (int) $fraction;

        return $matches[1] === '-' && $scaled !== 0 ? -$scaled : $scaled;
    }

    private function tablesExist(Connection $connection): bool
    {
        return $connection->getSchemaBuilder()->hasTable(ReportingProjection::COORDINATORS_TABLE)
            && $connection->getSchemaBuilder()->hasTable(ReportingProjection::VALUES_TABLE);
    }
}
