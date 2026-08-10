<?php

namespace Aura\Base\Table;

use Aura\Base\Livewire\Table\TableMutationModelDescriptor;
use Aura\Base\Resource;
use Closure;
use Illuminate\Contracts\Auth\Access\Gate;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Model;

final class TableRowOrderer
{
    public function __construct(private readonly Gate $gate) {}

    /**
     * @param  Closure(): Builder  $scope
     * @param  list<int|string>  $orderedIds
     */
    public function reorder(
        Closure $scope,
        Resource $resource,
        TableRowOrdering $ordering,
        array $orderedIds,
        int $page,
        int $perPage,
    ): void {
        if ($orderedIds === [] || $page < 1 || $perPage < 1 || $perPage > 500) {
            abort(422, 'The table row ordering request is invalid.');
        }

        $descriptor = new TableMutationModelDescriptor($resource);
        $requested = $this->normalizeIds($orderedIds, $descriptor);

        if (count($requested) !== count($orderedIds)) {
            abort(422, 'The table row ordering permutation contains duplicate records.');
        }

        $connection = $descriptor->connectionInstance();
        $connection->transaction(function () use (
            $connection,
            $descriptor,
            $ordering,
            $page,
            $perPage,
            $requested,
            $scope,
        ): void {
            $firstPage = $this->pageRecords($scope(), $page, $perPage);
            $this->assertExactPermutation($firstPage, $requested, $descriptor);

            $lockIds = array_values($requested);
            usort($lockIds, static fn (int|string $left, int|string $right): int => strcmp((string) $left, (string) $right));

            $connection->table($descriptor->table)
                ->whereIn($descriptor->keyName, $lockIds)
                ->orderBy($descriptor->keyName)
                ->lockForUpdate()
                ->get();

            $records = $this->pageRecords($scope(), $page, $perPage);
            $this->assertExactPermutation($records, $requested, $descriptor);

            foreach ($records as $record) {
                $this->gate->authorize($ordering->ability, $record);
            }

            $currentIds = $records->mapWithKeys(
                fn (Model $record): array => [$descriptor->canonicalIdentity($record->getKey()) => $record->getKey()],
            )->all();

            if (array_keys($currentIds) === array_keys($requested)) {
                return;
            }

            $slots = $records->pluck($ordering->column)->all();

            if (count(array_unique(array_map(
                static fn (mixed $slot): string => get_debug_type($slot).':'.serialize($slot),
                $slots,
            ))) !== count($slots)) {
                abort(409, 'The table row ordering slots are stale or ambiguous.');
            }

            $recordsByIdentity = $records->keyBy(
                fn (Model $record): string => $descriptor->canonicalIdentity($record->getKey()),
            );

            foreach (array_keys($requested) as $index => $identity) {
                $record = $recordsByIdentity->get($identity);

                if (! $record instanceof Resource) {
                    abort(409, 'The table row ordering records changed.');
                }

                if ($record->getAttribute($ordering->column) === $slots[$index]) {
                    continue;
                }

                $record->forceFill([$ordering->column => $slots[$index]]);
                $record->save();
            }
        }, 3);
    }

    /**
     * @param  Collection<int, Model>  $records
     * @param  array<string, int|string>  $requested
     */
    private function assertExactPermutation(
        Collection $records,
        array $requested,
        TableMutationModelDescriptor $descriptor,
    ): void {
        $resolved = $records->mapWithKeys(
            fn (Model $record): array => [$descriptor->canonicalIdentity($record->getKey()) => $record->getKey()],
        )->all();

        if (count($resolved) !== count($records)
            || array_diff_key($requested, $resolved) !== []
            || array_diff_key($resolved, $requested) !== []) {
            abort(409, 'The table row ordering page changed.');
        }
    }

    /**
     * @param  list<int|string>  $ids
     * @return array<string, int|string>
     */
    private function normalizeIds(array $ids, TableMutationModelDescriptor $descriptor): array
    {
        $normalized = [];

        foreach ($ids as $id) {
            if ((! is_int($id) && ! is_string($id)) || (is_string($id) && $id === '')) {
                abort(422, 'The table row ordering record identifiers are invalid.');
            }

            $identity = $descriptor->canonicalIdentity($id);

            if (array_key_exists($identity, $normalized)) {
                continue;
            }

            $normalized[$identity] = $id;
        }

        return $normalized;
    }

    /**
     * @return Collection<int, Model>
     */
    private function pageRecords(Builder $scope, int $page, int $perPage): Collection
    {
        return new Collection($scope->paginate($perPage, ['*'], 'page', $page)->items());
    }
}
