<?php

namespace Aura\Base\Pipeline;

use Closure;
use InvalidArgumentException;

class MapFields implements Pipe
{
    /**
     * @param  string|null  $resourceClass  Owner of the definitions being
     *                                      mapped, used to name the culprit in
     *                                      validation errors.
     */
    public function __construct(private ?string $resourceClass = null) {}

    public function handle($fields, Closure $next)
    {
        return $next($fields->map(function ($item, $index) {
            $this->assertValidDefinition($item, $index);

            $item['field'] = app($item['type'])->field($item);
            $item['field_type'] = app($item['type'])->type;

            return $item;
        }));
    }

    /**
     * Field definitions are plain arrays, so a missing key used to surface as
     * an "Undefined array key" notice several pipes away from the resource that
     * declared it. Fail here instead, naming the resource, the index and the
     * offending key.
     */
    private function assertValidDefinition($item, $index): void
    {
        $owner = $this->resourceClass ?? 'field definition';

        if (! is_array($item)) {
            throw new InvalidArgumentException(
                "[{$owner}] field #{$index} must be an array, ".get_debug_type($item).' given.'
            );
        }

        foreach (['type', 'slug'] as $key) {
            if (! isset($item[$key]) || $item[$key] === '') {
                throw new InvalidArgumentException(
                    "[{$owner}] field #{$index} is missing the required \"{$key}\" key."
                );
            }
        }

        if (! class_exists($item['type'])) {
            throw new InvalidArgumentException(
                "[{$owner}] field #{$index} (\"{$item['slug']}\") declares the field class [{$item['type']}], which does not exist."
            );
        }
    }
}
