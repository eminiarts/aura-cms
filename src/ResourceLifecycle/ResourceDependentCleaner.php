<?php

namespace Aura\Base\ResourceLifecycle;

use Aura\Base\Models\Meta;
use Aura\Base\Resource;

final class ResourceDependentCleaner
{
    public function cleanup(Resource $resource): void
    {
        $connection = $resource->getConnection();
        $schema = $connection->getSchemaBuilder();
        $morphType = $resource->getMorphClass();
        $resourceId = $resource->getKey();

        $metaTable = (new Meta)->getTable();

        if ($schema->hasTable($metaTable)) {
            $connection->table($metaTable)
                ->where('metable_type', $morphType)
                ->where('metable_id', $resourceId)
                ->delete();
        }

        if ($schema->hasTable('post_relations')) {
            $connection->table('post_relations')
                ->where(function ($query) use ($morphType, $resourceId): void {
                    $query->where(function ($outgoing) use ($morphType, $resourceId): void {
                        $outgoing->where('resource_type', $morphType)
                            ->where('resource_id', $resourceId);
                    })->orWhere(function ($incoming) use ($morphType, $resourceId): void {
                        $incoming->where('related_type', $morphType)
                            ->where('related_id', $resourceId);
                    });
                })
                ->delete();
        }
    }
}
