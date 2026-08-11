<?php

namespace Aura\Base\Commands;

use Aura\Base\Aura;
use Aura\Base\Reporting\CurrentStateProjectionReconciler;
use Aura\Base\Reporting\ReportingProjection;
use Aura\Base\Resource;
use Illuminate\Console\Command;
use RuntimeException;

final class ResyncReportingProjections extends Command
{
    protected $description = 'Reconcile reporting projections from authoritative current resource state';

    protected $signature = 'aura:reporting:resync
        {resource? : Registered resource class or Aura slug}
        {--chunk= : Keyset chunk size}';

    public function __construct(private readonly CurrentStateProjectionReconciler $reconciler)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        try {
            $chunkSize = $this->chunkSize();

            foreach ($this->resources() as $resourceClass) {
                $this->resyncResource($resourceClass, $chunkSize);
            }

            return self::SUCCESS;
        } catch (RuntimeException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }
    }

    private function chunkSize(): int
    {
        $configured = $this->option('chunk') ?? config('aura.reporting.projection.resync_chunk_size', 250);

        if (filter_var($configured, FILTER_VALIDATE_INT) === false || (int) $configured < 1 || (int) $configured > 1_000) {
            throw new RuntimeException('The reporting resync chunk size must be between 1 and 1000.');
        }

        return (int) $configured;
    }

    /** @return list<class-string<resource>> */
    private function resources(): array
    {
        $requested = $this->argument('resource');
        $aura = app(Aura::class);
        $resolved = $requested === null ? null : $aura->findResourceBySlug((string) $requested);
        $resources = $requested === null
            ? $aura->getResources()
            : ($resolved instanceof Resource ? [$resolved::class] : []);

        $resources = array_values(array_unique(array_filter($resources, fn (mixed $resource): bool => is_string($resource)
            && is_a($resource, Resource::class, true))));

        if ($resources === []) {
            throw new RuntimeException('No registered Aura Resource matched the reporting resync request.');
        }

        return $resources;
    }

    /** @param class-string<resource> $resourceClass */
    private function resyncResource(string $resourceClass, int $chunkSize): void
    {
        $resource = new $resourceClass;
        $lastId = 0;
        $processed = 0;

        do {
            $ids = $resource->getConnection()
                ->table($resource->getTable())
                ->where($resource->getKeyName(), '>', $lastId)
                ->when(
                    $resource::getInheritanceColumn() !== null && $resource::getInheritanceValue() !== null,
                    fn ($query) => $query->where($resource::getInheritanceColumn(), $resource::getInheritanceValue()),
                )
                ->orderBy($resource->getKeyName())
                ->limit($chunkSize)
                ->pluck($resource->getKeyName());

            foreach ($ids as $id) {
                $current = new $resourceClass;
                $current->setConnection($resource->getConnectionName());
                $current->setAttribute($current->getKeyName(), $id);
                $this->reconciler->resync($current);
                $lastId = (int) $id;
                $processed++;
            }
        } while ($ids->isNotEmpty());

        $lastCoordinatorId = 0;

        do {
            $ids = $resource->getConnection()
                ->table(ReportingProjection::COORDINATORS_TABLE)
                ->where('resource_type', $resourceClass)
                ->where('resource_id', '>', $lastCoordinatorId)
                ->orderBy('resource_id')
                ->limit($chunkSize)
                ->pluck('resource_id');

            foreach ($ids as $id) {
                $lastCoordinatorId = (int) $id;

                if ($this->sourceExists($resource, $id)) {
                    continue;
                }

                $current = new $resourceClass;
                $current->setConnection($resource->getConnectionName());
                $current->setAttribute($current->getKeyName(), $id);
                $this->reconciler->resync($current);
                $processed++;
            }
        } while ($ids->isNotEmpty());

        $this->line("Reconciled {$processed} {$resourceClass} reporting projection(s).");
    }

    private function sourceExists(Resource $resource, int|string $id): bool
    {
        $source = $resource->getConnection()
            ->table($resource->getTable())
            ->where($resource->getKeyName(), $id)
            ->when(
                $resource::getInheritanceColumn() !== null && $resource::getInheritanceValue() !== null,
                fn ($query) => $query->where($resource::getInheritanceColumn(), $resource::getInheritanceValue()),
            )
            ->first();

        if ($source === null) {
            return false;
        }

        $deletedAtColumn = method_exists($resource, 'getDeletedAtColumn') ? $resource->getDeletedAtColumn() : null;

        return $deletedAtColumn === null || $source->{$deletedAtColumn} === null;
    }
}
