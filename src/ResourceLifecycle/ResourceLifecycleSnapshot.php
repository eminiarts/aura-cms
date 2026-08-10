<?php

namespace Aura\Base\ResourceLifecycle;

use Aura\Base\Models\Meta;
use Aura\Base\Resource;

final class ResourceLifecycleSnapshot
{
    /**
     * @return array<string, array{old: bool|float|int|string|null, new: bool|float|int|string|null}>
     */
    public function changes(array $old, array $new): array
    {
        $changes = [];

        foreach (array_unique([...array_keys($old), ...array_keys($new)]) as $key) {
            $oldValue = $old[$key] ?? null;
            $newValue = $new[$key] ?? null;

            if ($oldValue !== $newValue) {
                $changes[$key] = ['old' => $oldValue, 'new' => $newValue];
            }
        }

        ksort($changes);

        return $changes;
    }

    /** @return array<string, bool|float|int|string|null> */
    public function currentMeta(Resource $resource): array
    {
        if (! $resource->usesMeta()) {
            return [];
        }

        $metaTable = (new Meta)->getTable();

        return $resource->getConnection()
            ->table($metaTable)
            ->where('metable_type', $resource->getMorphClass())
            ->where('metable_id', $resource->getKey())
            ->orderBy('id')
            ->get(['key', 'value'])
            ->filter(fn (object $row): bool => is_string($row->key))
            ->mapWithKeys(fn (object $row): array => [$row->key => $this->scalar($row->value)])
            ->all();
    }

    /** @return array<string, bool|float|int|string|null> */
    public function currentPhysical(Resource $resource): array
    {
        return $this->scalarAttributes($resource->getAttributes());
    }

    /** @return array<string, bool|float|int|string|null> */
    public function originalPhysical(Resource $resource): array
    {
        return $this->scalarAttributes($resource->getRawOriginal());
    }

    private function scalar(mixed $value): bool|float|int|string|null
    {
        if ($value === null || is_scalar($value)) {
            return $value;
        }

        return json_encode($value, JSON_THROW_ON_ERROR);
    }

    /**
     * @param  array<string, mixed>  $attributes
     * @return array<string, bool|float|int|string|null>
     */
    private function scalarAttributes(array $attributes): array
    {
        unset($attributes['fields']);

        return collect($attributes)
            ->map(fn (mixed $value): bool|float|int|string|null => $this->scalar($value))
            ->all();
    }
}
